<?php
session_start();
require_once 'symptom_analyzer.php';

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Initialiser la session si nécessaire
if (!isset($_SESSION['conversation_state'])) {
    $_SESSION['conversation_state'] = [
        'step' => 0,
        'current_symptom' => null,
        'symptom_data' => null,
        'questions_asked' => [],
        'user_responses' => [],
        'analysis_complete' => false
    ];
}

// Recevoir le message de l'utilisateur
$userMessage = isset($_POST['message']) ? trim($_POST['message']) : '';

if (empty($userMessage)) {
    echo json_encode(['response' => 'Je n\'ai pas compris votre message. Pouvez-vous reformuler?']);
    exit;
}

$state = &$_SESSION['conversation_state'];
$response = processMessage($userMessage, $state);

echo json_encode(['response' => $response]);

/**
 * Traite le message de l'utilisateur en fonction de l'état de la conversation
 * 
 * @param string $message Le message de l'utilisateur
 * @param array &$state L'état actuel de la conversation
 * @return string La réponse du chatbot
 */
function processMessage($message, &$state) {
    // Étape 0: Identification initiale des symptômes
    if ($state['step'] === 0) {
        $symptomMatch = analyzeSymptoms($message);
        
        if ($symptomMatch) {
            $state['current_symptom'] = $symptomMatch['symptom'];
            $state['symptom_data'] = $symptomMatch['data'];
            $state['step'] = 1;
            
            // Vérifier si le symptôme nécessite une attention immédiate
            if (requiresImmediateAttention($symptomMatch['data'])) {
                $state['analysis_complete'] = true;
                $state['step'] = 3;
                
                return "ATTENTION: Vos symptômes peuvent indiquer une condition nécessitant une attention médicale immédiate. " .
                       "Veuillez contacter les services d'urgence (15) ou vous rendre aux urgences les plus proches. " .
                       "En attendant les secours, restez calme et évitez tout effort.";
            }
            
            // Poser la première question de suivi
            if (!empty($symptomMatch['data']['questions'])) {
                $question = $symptomMatch['data']['questions'][0];
                $state['questions_asked'][] = $question;
                
                return "J'ai identifié que vous avez des symptômes de '{$symptomMatch['symptom']}'. " .
                       "Pour mieux vous orienter, j'ai besoin de quelques précisions. " . $question;
            }
        } else {
            return "Je ne suis pas sûr de comprendre vos symptômes. Pouvez-vous les décrire plus précisément ? " .
                   "Par exemple : maux de tête, douleur poitrine, problèmes de peau, toux, etc.";
        }
    }
    
    // Étape 1: Poser des questions de suivi pour affiner l'analyse
    if ($state['step'] === 1) {
        // Définir les réponses valides pour chaque type de question
        $validResponses = [
            // Questions de type oui/non
            'oui_non' => ['oui', 'non', 'yes', 'no'],
            
            // Questions sur le type de douleur
            'type_douleur' => ['pulsatile', 'constante', 'intermittente', 'lancinante', 'sourde'],
            
            // Questions sur la localisation
            'localisation' => ['localisée', 'généralisée', 'front', 'tempes', 'arrière', 'côté'],
            
            // Questions sur la durée
            'durée' => ['heures', 'jours', 'semaines', 'mois', 'récent', 'chronique'],
            
            // Questions sur l'intensité
            'intensité' => ['légère', 'modérée', 'intense', 'insupportable', 'faible', 'forte']
        ];
        
        // Obtenir la question actuelle
        $currentQuestionIndex = count($state['questions_asked']) - 1;
        $currentQuestion = $state['questions_asked'][$currentQuestionIndex];
        
        // Déterminer le type de question
        $questionType = 'oui_non'; // Type par défaut
        
        if (strpos($currentQuestion, 'pulsatile ou constante') !== false) {
            $questionType = 'type_douleur';
        } else if (strpos($currentQuestion, 'localisée ou généralisée') !== false) {
            $questionType = 'localisation';
        } else if (strpos($currentQuestion, 'combien de temps') !== false) {
            $questionType = 'durée';
        } else if (strpos($currentQuestion, 'intensité') !== false || strpos($currentQuestion, 'intense') !== false) {
            $questionType = 'intensité';
        }
        
        // Normaliser la réponse de l'utilisateur
        $userResponse = strtolower(trim($message));
        $userResponse = preg_replace('/[^a-z0-9\sà-ÿ]/', '', $userResponse);
        
        // Vérifier si la réponse est valide pour ce type de question
        $validResponse = false;
        foreach ($validResponses[$questionType] as $validOption) {
            if (strpos($userResponse, $validOption) !== false) {
                $validResponse = true;
                // Stocker la réponse valide plutôt que la réponse complète
                $state['user_responses'][] = $validOption;
                break;
            }
        }
        
        // Si la réponse n'est pas valide, demander une clarification
        if (!$validResponse) {
            $options = implode(', ', $validResponses[$questionType]);
            return "Je n'ai pas bien compris votre réponse. Pour cette question, veuillez répondre avec l'une des options suivantes : $options";
        }
        
        $questions = $state['symptom_data']['questions'];
        
        // S'il reste des questions à poser
        if (count($state['questions_asked']) < count($questions)) {
            $nextQuestion = $questions[count($state['questions_asked'])];
            $state['questions_asked'][] = $nextQuestion;
            return $nextQuestion;
        } else {
            // Toutes les questions ont été posées, passer à l'analyse
            $state['step'] = 2;
            return "Merci pour ces informations. Je vais maintenant analyser vos symptômes...";
        }
    }
    
    // Étape 2: Analyser les réponses et générer une recommandation
    if ($state['step'] === 2) {
        $conditionScores = analyzeResponses($state['symptom_data'], $state['user_responses']);
        $recommendation = generateRecommendation($state['symptom_data'], $conditionScores);
        
        $state['analysis_complete'] = true;
        $state['step'] = 3;
        
        return $recommendation['message'] . "\n\nSouhaitez-vous prendre rendez-vous avec un " . 
               $recommendation['speciality'] . " ? (Répondez par Oui ou Non)";
    }
    
    // Étape 3: Proposer une action après l'analyse (rendez-vous, etc.)
    if ($state['step'] === 3) {
        $lowerMessage = strtolower($message);
        
        if (strpos($lowerMessage, 'oui') !== false) {
            // Réinitialiser l'état pour la prochaine conversation
            $_SESSION['conversation_state'] = [
                'step' => 0,
                'current_symptom' => null,
                'symptom_data' => null,
                'questions_asked' => [],
                'user_responses' => [],
                'analysis_complete' => false
            ];
            
            return "Très bien ! Je vous redirige vers notre système de prise de rendez-vous. " .
                   "Vous pouvez y accéder en cliquant sur 'Prendre rendez-vous' dans le menu principal. " .
                   "N'hésitez pas à me contacter si vous avez d'autres questions.";
        } 
        elseif (strpos($lowerMessage, 'non') !== false) {
            // Réinitialiser l'état pour la prochaine conversation
            $_SESSION['conversation_state'] = [
                'step' => 0,
                'current_symptom' => null,
                'symptom_data' => null,
                'questions_asked' => [],
                'user_responses' => [],
                'analysis_complete' => false
            ];
            
            return "D'accord. N'hésitez pas à me contacter si vous changez d'avis ou si vous avez d'autres questions. " .
                   "Prenez soin de vous et consultez un médecin si vos symptômes persistent ou s'aggravent.";
        }
        else {
            return "Je n'ai pas compris votre réponse. Souhaitez-vous prendre rendez-vous avec un spécialiste ? " .
                   "Veuillez répondre par Oui ou Non.";
        }
    }
    
    return "Je suis désolé, je ne comprends pas. Pouvez-vous reformuler vos symptômes ?";
}
?>
