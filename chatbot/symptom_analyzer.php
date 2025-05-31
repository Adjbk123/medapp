<?php
/**
 * Système d'analyse de symptômes avancé pour le chatbot médical
 * Ce fichier contient les fonctions d'analyse de symptômes et de recommandations médicales
 */

// Base de données étendue des symptômes avec plus de détails et de catégories
$symptomsDatabase = [
    // Symptômes neurologiques
    'les maux de tete' => [
        'speciality' => 'Neurologue',
        'urgence' => 'moyenne',
        'category' => 'neurologique',
        'questions' => [
            'Depuis combien de temps avez-vous ces maux de tête ?',
            'La douleur est-elle pulsatile ou constante ?',
            'Avez-vous des nausées ou vomissements associés ?',
            'La douleur est-elle localisée ou généralisée ?',
            'Les maux de tête s\'aggravent-ils avec l\'activité physique ?'
        ],
        'conditions_possibles' => [
            'Migraine' => ['pulsatile', 'nausées', 'aggravation'],
            'Céphalée de tension' => ['constante', 'généralisée', 'stress'],
            'Sinusite' => ['localisée', 'front', 'pression']
        ],
        'conseils' => [
            'Reposez-vous dans un endroit calme et sombre',
            'Évitez les écrans et les sources de lumière vive',
            'Hydratez-vous régulièrement',
            'Consultez si les symptômes persistent plus de 3 jours'
        ]
    ],
    'les vertiges' => [
        'speciality' => 'Neurologue',
        'urgence' => 'moyenne',
        'category' => 'neurologique',
        'questions' => [
            'Les vertiges apparaissent-ils lors des changements de position ?',
            'Avez-vous des nausées associées ?',
            'Ressentez-vous une perte d\'équilibre ?',
            'Avez-vous des acouphènes (bourdonnements d\'oreille) ?'
        ],
        'conditions_possibles' => [
            'Vertige positionnel paroxystique bénin' => ['position', 'bref'],
            'Maladie de Ménière' => ['acouphènes', 'perte auditive'],
            'Neuronite vestibulaire' => ['soudain', 'infection']
        ],
        'conseils' => [
            'Évitez les mouvements brusques',
            'Asseyez-vous ou allongez-vous si les vertiges surviennent',
            'Consultez rapidement si les vertiges sont intenses ou persistants'
        ]
    ],
    
    // Symptômes cardiovasculaires
    'douleur poitrine' => [
        'speciality' => 'Cardiologue',
        'urgence' => 'haute',
        'category' => 'cardiovasculaire',
        'questions' => [
            'Ressentez-vous une pression, un serrement ou une brûlure ?',
            'La douleur irradie-t-elle dans le bras gauche, la mâchoire ou le dos ?',
            'Avez-vous des difficultés à respirer ?',
            'Avez-vous des sueurs froides ou des nausées ?',
            'La douleur est-elle apparue pendant un effort ?'
        ],
        'conditions_possibles' => [
            'Angine de poitrine' => ['effort', 'pression', 'irradiation'],
            'Infarctus du myocarde' => ['intense', 'irradiation', 'sueurs', 'nausées'],
            'Péricardite' => ['position', 'respiration', 'fièvre']
        ],
        'conseils' => [
            'En cas de douleur thoracique intense, appelez immédiatement les urgences (15)',
            'Asseyez-vous et restez calme',
            'Si prescrit, prenez votre nitroglycérine'
        ],
        'urgence_immediate' => true
    ],
    'palpitations' => [
        'speciality' => 'Cardiologue',
        'urgence' => 'moyenne',
        'category' => 'cardiovasculaire',
        'questions' => [
            'Les palpitations sont-elles régulières ou irrégulières ?',
            'Combien de temps durent-elles ?',
            'Avez-vous des étourdissements pendant les épisodes ?',
            'Consommez-vous de la caféine, de l\'alcool ou des stimulants ?'
        ],
        'conditions_possibles' => [
            'Arythmie' => ['irrégulière', 'étourdissements'],
            'Tachycardie' => ['rapide', 'régulière', 'anxiété'],
            'Extrasystoles' => ['occasionnelle', 'pause']
        ],
        'conseils' => [
            'Limitez la consommation de caféine et d\'alcool',
            'Pratiquez des techniques de relaxation',
            'Consultez si les palpitations sont fréquentes ou accompagnées de malaises'
        ]
    ],
    
    // Symptômes dermatologiques
    'problèmes de peau' => [
        'speciality' => 'Dermatologue',
        'urgence' => 'basse',
        'category' => 'dermatologique',
        'questions' => [
            'Avez-vous des démangeaisons ?',
            'La zone affectée est-elle rouge, squameuse ou présente-t-elle des boutons ?',
            'Depuis quand avez-vous ces symptômes ?',
            'Avez-vous changé récemment de produits d\'hygiène ou de lessive ?'
        ],
        'conditions_possibles' => [
            'Eczéma' => ['démangeaisons', 'rougeur', 'squames'],
            'Psoriasis' => ['plaques', 'squames', 'chronique'],
            'Dermatite de contact' => ['produits', 'localisée', 'rougeur']
        ],
        'conseils' => [
            'Évitez de gratter les zones affectées',
            'Utilisez des produits d\'hygiène doux et sans parfum',
            'Hydratez votre peau régulièrement'
        ]
    ],
    
    // Symptômes gastro-intestinaux
    'mal de ventre' => [
        'speciality' => 'Gastro-entérologue',
        'urgence' => 'moyenne',
        'category' => 'gastro-intestinal',
        'questions' => [
            'Où est localisée la douleur (haut, bas, côté droit, côté gauche) ?',
            'Avez-vous des nausées, vomissements ou diarrhées ?',
            'La douleur est-elle constante ou intermittente ?',
            'Avez-vous remarqué des changements dans vos selles ?',
            'Avez-vous de la fièvre ?'
        ],
        'conditions_possibles' => [
            'Gastrite' => ['haut', 'brûlure', 'repas'],
            'Syndrome du côlon irritable' => ['bas', 'intermittente', 'selles'],
            'Appendicite' => ['bas droite', 'intense', 'fièvre', 'nausées']
        ],
        'conseils' => [
            'Mangez de petits repas légers',
            'Évitez les aliments épicés, gras ou acides',
            'Consultez rapidement si la douleur est intense ou localisée en bas à droite'
        ]
    ],
    'nausées' => [
        'speciality' => 'Gastro-entérologue',
        'urgence' => 'basse',
        'category' => 'gastro-intestinal',
        'questions' => [
            'Avez-vous également des vomissements ?',
            'Ressentez-vous des vertiges ou maux de tête ?',
            'Avez-vous mangé quelque chose d\'inhabituel récemment ?',
            'Êtes-vous enceinte ou pourriez-vous l\'être ?'
        ],
        'conditions_possibles' => [
            'Gastro-entérite' => ['vomissements', 'diarrhée', 'fièvre'],
            'Intoxication alimentaire' => ['vomissements', 'repas', 'soudain'],
            'Grossesse' => ['matin', 'femme', 'retard']
        ],
        'conseils' => [
            'Hydratez-vous régulièrement par petites gorgées',
            'Évitez les aliments solides jusqu\'à amélioration',
            'Reposez-vous et surveillez l\'évolution des symptômes'
        ]
    ],
    
    // Symptômes respiratoires
    'toux' => [
        'speciality' => 'Pneumologue',
        'urgence' => 'basse',
        'category' => 'respiratoire',
        'questions' => [
            'La toux est-elle sèche ou grasse ?',
            'Depuis combien de temps toussez-vous ?',
            'Avez-vous de la fièvre ou des difficultés à respirer ?',
            'Avez-vous des antécédents d\'asthme ou d\'allergie ?'
        ],
        'conditions_possibles' => [
            'Bronchite' => ['grasse', 'fièvre', 'fatigue'],
            'Asthme' => ['sèche', 'sifflements', 'effort'],
            'Infection virale' => ['sèche', 'récente', 'gorge']
        ],
        'conseils' => [
            'Buvez beaucoup d\'eau pour fluidifier les sécrétions',
            'Évitez de fumer et les environnements enfumés',
            'Consultez si la toux persiste plus de 2 semaines ou s\'accompagne de fièvre'
        ]
    ],
    'difficulté à respirer' => [
        'speciality' => 'Pneumologue',
        'urgence' => 'haute',
        'category' => 'respiratoire',
        'questions' => [
            'La difficulté est-elle apparue soudainement ou progressivement ?',
            'Ressentez-vous une douleur thoracique ?',
            'Avez-vous des sifflements quand vous respirez ?',
            'Avez-vous des antécédents cardiaques ou pulmonaires ?'
        ],
        'conditions_possibles' => [
            'Asthme' => ['sifflements', 'toux', 'effort'],
            'Embolie pulmonaire' => ['soudaine', 'douleur', 'immobilisation'],
            'Insuffisance cardiaque' => ['progressive', 'œdème', 'fatigue']
        ],
        'conseils' => [
            'En cas de difficulté respiratoire sévère, appelez immédiatement les urgences (15)',
            'Asseyez-vous droit pour faciliter la respiration',
            'Si vous avez un inhalateur prescrit, utilisez-le selon les recommandations'
        ],
        'urgence_immediate' => true
    ]
];

/**
 * Analyse un message pour détecter les symptômes mentionnés
 * 
 * @param string $message Le message de l'utilisateur
 * @return array|null Les informations sur le symptôme détecté ou null
 */
function analyzeSymptoms($message) {
    global $symptomsDatabase;
    
    // Convertir le message en minuscules pour ne pas être sensible à la casse
    $message = strtolower($message);
    
    // Normaliser le texte (enlever les caractères spéciaux, les apostrophes, etc.)
    $message = preg_replace('/[^a-z0-9\s]/', ' ', $message);
    
    // Liste des mots-clés essentiels à détecter dans le message
    $keywordMap = [
        'mal de tete' => 'les maux de tete',
        'maux de tete' => 'les maux de tete',
        'migraine' => 'les maux de tete',
        'cephalee' => 'les maux de tete',
        'tete qui tourne' => 'les vertiges',
        'vertige' => 'les vertiges',
        'etourdissement' => 'les vertiges',
        'mal au ventre' => 'mal de ventre',
        'douleur abdominale' => 'mal de ventre',
        'estomac' => 'mal de ventre',
        'douleur thoracique' => 'douleur poitrine',
        'mal a la poitrine' => 'douleur poitrine',
        'coeur' => 'douleur poitrine',
        'peau' => 'problemes de peau',
        'eruption' => 'problemes de peau',
        'bouton' => 'problemes de peau',
        'eczema' => 'problemes de peau',
        'respirer' => 'difficulte a respirer',
        'essoufflement' => 'difficulte a respirer',
        'souffle court' => 'difficulte a respirer',
        'tousser' => 'toux',
        'nausee' => 'nausees',
        'vomissement' => 'nausees',
        'envie de vomir' => 'nausees',
        'coeur qui bat' => 'palpitations',
        'palpitation' => 'palpitations'
    ];
    
    $detectedKeywords = [];
    
    // Rechercher les mots-clés dans le message
    foreach ($keywordMap as $keyword => $symptomKey) {
        if (strpos($message, $keyword) !== false) {
            $detectedKeywords[$symptomKey] = true;
        }
    }
    
    // Rechercher directement les symptômes dans le message
    foreach ($symptomsDatabase as $symptom => $data) {
        // Normaliser le symptôme pour la comparaison
        $normalizedSymptom = strtolower(preg_replace('/[^a-z0-9\s]/', ' ', $symptom));
        
        if (strpos($message, $normalizedSymptom) !== false) {
            $detectedKeywords[$symptom] = true;
        }
    }
    
    // Convertir les mots-clés détectés en symptômes
    $detectedSymptoms = [];
    foreach (array_keys($detectedKeywords) as $symptomKey) {
        if (isset($symptomsDatabase[$symptomKey])) {
            $detectedSymptoms[] = [
                'symptom' => $symptomKey,
                'data' => $symptomsDatabase[$symptomKey]
            ];
        }
    }
    
    // Si plusieurs symptômes sont détectés, prioriser celui avec l'urgence la plus élevée
    if (count($detectedSymptoms) > 1) {
        usort($detectedSymptoms, function($a, $b) {
            $urgenceLevel = [
                'basse' => 1,
                'moyenne' => 2,
                'haute' => 3
            ];
            
            return $urgenceLevel[$b['data']['urgence']] - $urgenceLevel[$a['data']['urgence']];
        });
    }
    
    return !empty($detectedSymptoms) ? $detectedSymptoms[0] : null;
}

/**
 * Analyse les réponses de l'utilisateur pour affiner le diagnostic
 * 
 * @param array $symptomData Les données du symptôme
 * @param array $userResponses Les réponses de l'utilisateur aux questions
 * @return array Les conditions possibles avec leurs scores
 */
function analyzeResponses($symptomData, $userResponses) {
    $conditions = $symptomData['conditions_possibles'];
    $scores = [];
    
    // Initialiser les scores à 0
    foreach ($conditions as $condition => $keywords) {
        $scores[$condition] = 0;
    }
    
    // Analyser chaque réponse pour les mots-clés associés aux conditions
    foreach ($userResponses as $response) {
        $response = strtolower($response);
        
        foreach ($conditions as $condition => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($response, strtolower($keyword)) !== false) {
                    $scores[$condition]++;
                }
            }
        }
    }
    
    // Trier les conditions par score décroissant
    arsort($scores);
    
    return $scores;
}

/**
 * Génère une recommandation basée sur l'analyse des symptômes et des réponses
 * 
 * @param array $symptomData Les données du symptôme
 * @param array $conditionScores Les scores des conditions possibles
 * @return array La recommandation générée
 */
function generateRecommendation($symptomData, $conditionScores) {
    $topConditions = array_slice($conditionScores, 0, 2, true);
    $speciality = $symptomData['speciality'];
    $urgence = $symptomData['urgence'];
    $conseils = $symptomData['conseils'];
    
    $recommendation = [
        'speciality' => $speciality,
        'urgence' => $urgence,
        'conditions' => array_keys($topConditions),
        'conseils' => $conseils,
        'message' => ''
    ];
    
    // Construire le message de recommandation
    $recommendation['message'] = "D'après mon analyse, vous pourriez présenter ";
    
    if (count($topConditions) > 1) {
        $conditions = array_keys($topConditions);
        $recommendation['message'] .= "l'une des conditions suivantes : " . implode(" ou ", $conditions) . ". ";
    } else {
        $condition = key($topConditions);
        $recommendation['message'] .= "des symptômes compatibles avec " . $condition . ". ";
    }
    
    $recommendation['message'] .= "Je vous recommande de consulter un " . $speciality . ". ";
    
    // Ajouter l'urgence
    switch ($urgence) {
        case 'haute':
            $recommendation['message'] .= "Cette consultation est urgente, veuillez prendre rendez-vous le plus tôt possible. ";
            break;
        case 'moyenne':
            $recommendation['message'] .= "Vous devriez consulter dans les prochains jours. ";
            break;
        case 'basse':
            $recommendation['message'] .= "Vous pouvez prendre rendez-vous dans les prochaines semaines. ";
            break;
    }
    
    // Ajouter des conseils
    $recommendation['message'] .= "\n\nEn attendant, voici quelques conseils :\n";
    foreach ($conseils as $conseil) {
        $recommendation['message'] .= "- " . $conseil . "\n";
    }
    
    return $recommendation;
}

/**
 * Détermine si une condition nécessite une attention médicale immédiate
 * 
 * @param array $symptomData Les données du symptôme
 * @return bool True si une attention médicale immédiate est requise
 */
function requiresImmediateAttention($symptomData) {
    return isset($symptomData['urgence_immediate']) && $symptomData['urgence_immediate'] === true;
}
?>
