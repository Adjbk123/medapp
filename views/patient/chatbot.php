<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');

if (!$message) {
    echo json_encode(['reply' => "Je n'ai pas reçu de message."]);
    exit;
}

// Tentative via Ollama (optionnel, timeout court)
$ollamaAvailable = false;
try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost:11434/api/generate");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        "model" => "mistral:latest",
        "prompt" => "Tu es un assistant médical bienveillant. Réponds en français de façon concise à: $message",
        "stream" => false
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response && $httpCode === 200) {
        $json = json_decode($response, true);
        if (!empty($json['response'])) {
            echo json_encode(['reply' => $json['response']]);
            exit;
        }
    }
} catch (Exception $e) {}

// Fallback : analyse locale par mots-clés
$msg = mb_strtolower($message, 'UTF-8');

$symptoms = [
    ['keywords' => ['maux de tête', 'mal de tête', 'céphalée', 'migraine', 'tête qui fait mal'],
     'reply' => "Les maux de tête peuvent avoir plusieurs causes : stress, fatigue, déshydratation ou tension artérielle. Reposez-vous dans un endroit calme, hydratez-vous bien. Si la douleur est intense ou persiste plus de 48h, consultez un médecin."],

    ['keywords' => ['fièvre', 'température', 'chaud', 'frisson'],
     'reply' => "La fièvre est souvent le signe d'une infection. Hydratez-vous abondamment, reposez-vous et prenez du paracétamol si nécessaire. Si la fièvre dépasse 39,5°C ou dure plus de 3 jours, consultez un médecin rapidement."],

    ['keywords' => ['mal de ventre', 'maux de ventre', 'douleur abdominale', 'ventre', 'estomac'],
     'reply' => "Les douleurs abdominales peuvent indiquer une indigestion, une gastrite ou d'autres pathologies. Évitez les repas lourds, buvez de l'eau. En cas de douleur intense, persistante ou accompagnée de vomissements, consultez un médecin."],

    ['keywords' => ['toux', 'toussé', 'gorge', 'mal de gorge', 'enrhumé', 'rhume', 'nez bouché'],
     'reply' => "Les symptômes ORL (toux, rhume, mal de gorge) sont souvent d'origine virale. Reposez-vous, hydratez-vous bien et inhalez de la vapeur. Si les symptômes persistent plus d'une semaine ou s'aggravent, consultez un médecin."],

    ['keywords' => ['essoufflement', 'respiration', 'souffle', 'poitrine', 'thorax'],
     'reply' => "Les difficultés respiratoires ou douleurs thoraciques doivent être prises au sérieux. En cas d'essoufflement soudain ou de douleur dans la poitrine, consultez un médecin ou appelez les urgences immédiatement."],

    ['keywords' => ['fatigue', 'fatigué', 'épuisé', 'sans énergie'],
     'reply' => "La fatigue persistante peut être liée au manque de sommeil, au stress ou à une carence (fer, vitamines). Veillez à dormir 7-8h, mangez équilibré. Si la fatigue est intense et inexpliquée, un bilan sanguin peut être utile."],

    ['keywords' => ['diarrhée', 'selles liquides', 'ventre mou'],
     'reply' => "En cas de diarrhée, il est essentiel de bien s'hydrater avec de l'eau et des sels de réhydratation. Évitez les aliments gras. Si la diarrhée dure plus de 2 jours ou s'accompagne de sang, consultez un médecin."],

    ['keywords' => ['vomissement', 'nausée', 'nausées', 'vomi', 'envie de vomir'],
     'reply' => "Les nausées et vomissements peuvent être liés à une intoxication alimentaire, un virus ou le stress. Hydratez-vous par petites gorgées, reposez-vous. Consultez si les symptômes persistent ou s'aggravent."],

    ['keywords' => ['dos', 'lombaire', 'colonne', 'mal de dos', 'rein'],
     'reply' => "Le mal de dos est très fréquent. Évitez de porter des charges lourdes, faites des étirements légers. En cas de douleur intense irradiant vers les jambes ou accompagnée de fièvre, consultez un médecin."],

    ['keywords' => ['diabète', 'glycémie', 'sucre'],
     'reply' => "Si vous suspectez un problème de glycémie (soif excessive, fatigue, vision floue), un bilan sanguin avec dosage de la glycémie à jeun est recommandé. Consultez votre médecin traitant."],
];

$reply = null;
foreach ($symptoms as $symptom) {
    foreach ($symptom['keywords'] as $keyword) {
        if (str_contains($msg, $keyword)) {
            $reply = $symptom['reply'];
            break 2;
        }
    }
}

if (!$reply) {
    $reply = "Je comprends votre préoccupation concernant \"$message\". Pour un diagnostic précis, je vous recommande de consulter un médecin. N'hésitez pas à prendre rendez-vous via l'onglet « Rendez-vous » de votre espace patient.";
}

echo json_encode(['reply' => $reply]);
