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

    // Vérification des paramètres
    if (!isset($_GET['user_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID utilisateur manquant']);
        exit;
    }

    $db = (new Database())->getConnection();

    // Détermination du type d'expéditeur (patient ou médecin)
    $receiver_id = $_SESSION['user_id'];
    $sender_type = isset($_SESSION['role']) && $_SESSION['role'] === 'medecin' ? 'medecin' : 'patient';
    $other_type = $sender_type === 'medecin' ? 'patient' : 'medecin';
    
    // Vérification du statut de frappe
    $stmt = $db->prepare("
        SELECT is_typing 
        FROM typing_status 
        WHERE user_id = ? 
        AND receiver_id = ? 
        AND sender_type = ?
        AND last_updated > DATE_SUB(NOW(), INTERVAL 10 SECOND)
    ");
    
    $stmt->execute([
        $_GET['user_id'],
        $receiver_id,
        $other_type
    ]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'is_typing' => $result && $result['is_typing'] == 1
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur: ' . $e->getMessage()]);
}