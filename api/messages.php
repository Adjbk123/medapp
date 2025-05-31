<?php
// Désactiver l'affichage des erreurs pour éviter que du HTML soit renvoyé au lieu du JSON
error_reporting(0);
ini_set('display_errors', 0);

// S'assurer que la réponse sera toujours en JSON
header('Content-Type: application/json');

try {
    require_once '../includes/session.php';
    require_once '../config/database.php';

    // Vérification de la session
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Non autorisé']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'];
    $db = (new Database())->getConnection();

    // Vérification des paramètres
    if (!isset($_GET['other_id']) || !is_numeric($_GET['other_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID manquant']);
        exit;
    }

    $other_id = (int)$_GET['other_id'];

    // Récupération des messages selon le rôle
    $stmt = $db->prepare("
        SELECT m.id, m.contenu, m.image_url, m.date_envoi, m.sender_id, m.receiver_id, m.sender_type, m.lu, 
               CASE 
                   WHEN m.sender_type = 'patient' THEN CONCAT(p.prenom, ' ', p.nom)
                   ELSE CONCAT(med.prenom, ' ', med.nom)
               END as sender_name
        FROM messages m
        LEFT JOIN patient p ON m.sender_id = p.id AND m.sender_type = 'patient'
        LEFT JOIN medecin med ON m.sender_id = med.id AND m.sender_type = 'medecin'
        WHERE (m.sender_id = :user_id AND m.receiver_id = :other_id)
           OR (m.sender_id = :other_id AND m.receiver_id = :user_id)
        ORDER BY m.date_envoi ASC
    ");

    $stmt->execute([
        ':user_id' => $user_id,
        ':other_id' => $other_id
    ]);

    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Marquer les messages comme lus
    $update_stmt = $db->prepare("
        UPDATE messages 
        SET lu = TRUE 
        WHERE receiver_id = :user_id 
        AND sender_id = :other_id 
        AND lu = FALSE
    ");

    $update_stmt->execute([
        ':user_id' => $user_id,
        ':other_id' => $other_id
    ]);

    echo json_encode(['success' => true, 'data' => $messages]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur: ' . $e->getMessage()]);
}