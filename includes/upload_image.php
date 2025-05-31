<?php
/**
 * Script de gestion des uploads d'images pour la messagerie
 */

// Fonction pour valider et traiter l'upload d'une image
function uploadMessageImage($file) {
    // Vérifier si un fichier a été uploadé
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'message' => 'Erreur lors de l\'upload du fichier.'
        ];
    }

    // Vérifier le type de fichier
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        return [
            'success' => false,
            'message' => 'Type de fichier non autorisé. Seuls les formats JPEG, PNG, GIF et WEBP sont acceptés.'
        ];
    }

    // Vérifier la taille du fichier (max 5MB)
    $maxFileSize = 5 * 1024 * 1024; // 5MB en octets
    if ($file['size'] > $maxFileSize) {
        return [
            'success' => false,
            'message' => 'Le fichier est trop volumineux. Taille maximale: 5MB.'
        ];
    }

    // Créer le dossier de destination s'il n'existe pas
    $uploadDir = __DIR__ . '/../uploads/messages/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Générer un nom de fichier unique
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFileName = uniqid('msg_') . '_' . time() . '.' . $fileExtension;
    $targetPath = $uploadDir . $newFileName;

    // Déplacer le fichier uploadé vers le dossier de destination
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Retourner l'URL relative du fichier
        return [
            'success' => true,
            'image_url' => '/uploads/messages/' . $newFileName
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Erreur lors de l\'enregistrement du fichier.'
        ];
    }
}
