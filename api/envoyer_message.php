<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../models/Message.php';
require_once '../includes/upload_image.php';

// Vérification de la session
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérification de la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Vérification des données POST
if (!isset($_POST['receiver_id']) || (empty($_POST['contenu']) && !isset($_FILES['image']))) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$message = new Message($db);

try {
    // Détermination du type d'expéditeur (patient ou médecin)
    $message->sender_id = $_SESSION['user_id'];
    $message->sender_type = isset($_SESSION['role']) && $_SESSION['role'] === 'medecin' ? 'medecin' : 'patient';
    $message->receiver_id = (int)$_POST['receiver_id'];
    $message->contenu = isset($_POST['contenu']) ? trim($_POST['contenu']) : '';
    $message->image_url = null;

    // Vérification que le sender_type correspond au rôle de l'utilisateur
    if (($message->sender_type === 'medecin' && $_SESSION['role'] !== 'medecin') ||
        ($message->sender_type === 'patient' && $_SESSION['role'] !== 'patient')) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Type d\'expéditeur invalide']);
        exit;
    }

    // Traitement de l'image si présente
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = uploadMessageImage($_FILES['image']);
        
        if ($uploadResult['success']) {
            $message->image_url = $uploadResult['image_url'];
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $uploadResult['message']]);
            exit;
        }
    }

    // Envoi du message
    if ($message->envoyer()) {
        // Récupérer l'ID du message inséré
        $message_id = $db->lastInsertId();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message_id' => $message_id,
            'image_url' => $message->image_url
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'envoi du message']);
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}