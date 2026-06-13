<?php
session_start();

// Base de données des symptômes et spécialités
$symptomsDatabase = [
    'maux de tête' => [
        'speciality' => 'Neurologue',
        'urgence' => 'moyenne',
        'questions' => [
            'Depuis combien de temps avez-vous ces maux de tête ?',
            'La douleur est-elle pulsatile ?',
            'Avez-vous des nausées ?'
        ]
    ],
    'mal de tête' => [
        'speciality' => 'Neurologue',
        'urgence' => 'moyenne',
        'questions' => [
            'Depuis combien de temps avez-vous ce mal de tête ?',
            'La douleur est-elle pulsatile ?',
            'Avez-vous des nausées ?'
        ]
    ],
    'migraine' => [
        'speciality' => 'Neurologue',
        'urgence' => 'moyenne',
        'questions' => [
            'Depuis combien de temps dure la migraine ?',
            'Avez-vous des troubles visuels ?',
            'Avez-vous des nausées ou vomissements ?'
        ]
    ],
    'douleur poitrine' => [
        'speciality' => 'Cardiologue',
        'urgence' => 'haute',
        'questions' => [
            'Ressentez-vous une pression ou un serrement ?',
            'La douleur irradie-t-elle dans le bras gauche ?'
        ]
    ],
    'douleur thoracique' => [
        'speciality' => 'Cardiologue',
        'urgence' => 'haute',
        'questions' => [
            'Ressentez-vous un essoufflement ?',
            'La douleur irradie-t-elle dans le bras ou la mâchoire ?'
        ]
    ],
    'problèmes de peau' => [
        'speciality' => 'Dermatologue',
        'urgence' => 'basse',
        'questions' => [
            'Avez-vous des démangeaisons ?',
            'Depuis quand avez-vous ces symptômes ?'
        ]
    ],
    'éruption cutanée' => [
        'speciality' => 'Dermatologue',
        'urgence' => 'basse',
        'questions' => [
            'L\'éruption est-elle localisée ou généralisée ?',
            'Avez-vous de la fièvre en même temps ?'
        ]
    ],
    'mal de ventre' => [
        'speciality' => 'Gastro-entérologue',
        'urgence' => 'moyenne',
        'questions' => [
            'La douleur est-elle localisée ?',
            'Avez-vous des nausées ou vomissements ?'
        ]
    ],
    'maux de ventre' => [
        'speciality' => 'Gastro-entérologue',
        'urgence' => 'moyenne',
        'questions' => [
            'La douleur est-elle localisée ou diffuse ?',
            'Avez-vous de la diarrhée ou de la constipation ?'
        ]
    ],
    'douleur abdominale' => [
        'speciality' => 'Gastro-entérologue',
        'urgence' => 'moyenne',
        'questions' => [
            'La douleur est-elle constante ou par crises ?',
            'Avez-vous des nausées ou vomissements ?'
        ]
    ],
    'toux' => [
        'speciality' => 'Pneumologue',
        'urgence' => 'basse',
        'questions' => [
            'La toux est-elle sèche ou grasse ?',
            'Depuis combien de jours toussez-vous ?',
            'Avez-vous de la fièvre ?'
        ]
    ],
    'essoufflement' => [
        'speciality' => 'Pneumologue',
        'urgence' => 'haute',
        'questions' => [
            'L\'essoufflement survient-il au repos ou à l\'effort ?',
            'Avez-vous de la toux ou des douleurs thoraciques ?'
        ]
    ],
    'fièvre' => [
        'speciality' => 'Médecin généraliste',
        'urgence' => 'moyenne',
        'questions' => [
            'Quelle est votre température approximative ?',
            'Depuis combien de jours avez-vous de la fièvre ?',
            'Avez-vous d\'autres symptômes (toux, douleurs) ?'
        ]
    ],
    'fatigue' => [
        'speciality' => 'Médecin généraliste',
        'urgence' => 'basse',
        'questions' => [
            'La fatigue est-elle présente depuis longtemps ?',
            'Avez-vous des troubles du sommeil ?'
        ]
    ],
    'mal de dos' => [
        'speciality' => 'Rhumatologue',
        'urgence' => 'basse',
        'questions' => [
            'La douleur irradie-t-elle dans les jambes ?',
            'La douleur est-elle constante ou par crises ?'
        ]
    ],
    'douleur articulaire' => [
        'speciality' => 'Rhumatologue',
        'urgence' => 'basse',
        'questions' => [
            'Quelles articulations sont touchées ?',
            'Avez-vous des gonflements ou rougeurs ?'
        ]
    ],
    'nausée' => [
        'speciality' => 'Gastro-entérologue',
        'urgence' => 'basse',
        'questions' => [
            'Avez-vous vomi ?',
            'Les nausées surviennent-elles après les repas ?'
        ]
    ],
    'nausées' => [
        'speciality' => 'Gastro-entérologue',
        'urgence' => 'basse',
        'questions' => [
            'Avez-vous vomi ?',
            'Les nausées surviennent-elles après les repas ?'
        ]
    ],
    'diarrhée' => [
        'speciality' => 'Gastro-entérologue',
        'urgence' => 'moyenne',
        'questions' => [
            'Depuis combien de jours avez-vous la diarrhée ?',
            'Avez-vous de la fièvre ou des douleurs ?'
        ]
    ],
    'mal de gorge' => [
        'speciality' => 'ORL',
        'urgence' => 'basse',
        'questions' => [
            'Avez-vous de la fièvre ?',
            'Avez-vous des difficultés à avaler ?'
        ]
    ],
    'rhume' => [
        'speciality' => 'Médecin généraliste',
        'urgence' => 'basse',
        'questions' => [
            'Avez-vous de la fièvre ?',
            'Depuis combien de jours êtes-vous enrhumé ?'
        ]
    ],
];

// Initialiser la session si nécessaire
if (!isset($_SESSION['conversation_state'])) {
    $_SESSION['conversation_state'] = [
        'step' => 0,
        'current_symptoms' => [],
        'current_specialty' => null,
        'questions_asked' => []
    ];
}

// Recevoir le message de l'utilisateur
$userMessage = mb_strtolower($_POST['message'] ?? '', 'UTF-8');
$state = &$_SESSION['conversation_state'];

function analyzeSymptoms($message) {
    global $symptomsDatabase;
    foreach ($symptomsDatabase as $symptom => $data) {
        if (strpos($message, $symptom) !== false) {
            return [
                'symptom' => $symptom,
                'data' => $data
            ];
        }
    }
    return null;
}

function getResponse() {
    global $state, $symptomsDatabase, $userMessage;

    // Si c'est le début de la conversation
    if ($state['step'] === 0) {
        $symptomMatch = analyzeSymptoms($userMessage);
        
        if ($symptomMatch) {
            $state['current_symptoms'][] = $symptomMatch['symptom'];
            $state['current_specialty'] = $symptomMatch['data']['speciality'];
            $state['step'] = 1;
            
            // Poser la première question de suivi
            if (!empty($symptomMatch['data']['questions'])) {
                $question = $symptomMatch['data']['questions'][0];
                $state['questions_asked'][] = $question;
                return "D'après vos symptômes, je pense que vous devriez consulter un {$symptomMatch['data']['speciality']}. " .
                       "Pour mieux vous orienter, j'ai besoin de quelques précisions. " . $question;
            }
        } else {
            return "Je ne suis pas sûr de comprendre vos symptômes. Pouvez-vous les décrire plus précisément ? " .
                   "Par exemple : maux de tête, douleur poitrine, problèmes de peau, etc.";
        }
    }
    
    // Si nous sommes en train de poser des questions de suivi
    if ($state['step'] === 1) {
        $currentSymptom = $state['current_symptoms'][0];
        $questions = $symptomsDatabase[$currentSymptom]['questions'];
        
        if (count($state['questions_asked']) < count($questions)) {
            // Poser la question suivante
            $nextQuestion = $questions[count($state['questions_asked'])];
            $state['questions_asked'][] = $nextQuestion;
            return $nextQuestion;
        } else {
            // Toutes les questions ont été posées, donner la recommandation finale
            $state['step'] = 2;
            $urgence = $symptomsDatabase[$currentSymptom]['urgence'];
            $speciality = $state['current_specialty'];
            
            // Réinitialiser l'état pour la prochaine conversation
            $_SESSION['conversation_state'] = [
                'step' => 0,
                'current_symptoms' => [],
                'current_specialty' => null,
                'questions_asked' => []
            ];
            
            return "Basé sur vos réponses, je vous recommande de consulter un $speciality. " .
                   ($urgence === 'haute' ? "Cette consultation est urgente, veuillez prendre rendez-vous le plus tôt possible. " : 
                    "Vous pouvez prendre rendez-vous dans les prochains jours. ") .
                   "Voulez-vous que je vous aide à trouver un spécialiste près de chez vous ?";
        }
    }
    
    return "Je suis désolé, je ne comprends pas. Pouvez-vous reformuler vos symptômes ?";
}

// Envoyer la réponse
echo getResponse();
?> 
