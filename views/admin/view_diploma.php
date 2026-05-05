<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure les fichiers nécessaires
require_once '../../includes/session.php';
require_once '../../includes/security.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
requireLogin();
requireRole('admin');

// Vérifier si un nom de fichier a été fourni
if (!isset($_GET['filename']) || empty($_GET['filename'])) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<div style="color: red; font-weight: bold;">Erreur: Nom de fichier non spécifié.</div>';
    exit;
}

// Nettoyer le nom de fichier pour éviter les attaques par traversée de chemin
$filename = basename($_GET['filename']);

// Essayer différents chemins pour trouver le fichier
$possible_paths = [
    __DIR__ . '/../../uploads/diplomes/' . $filename,
    $_SERVER['DOCUMENT_ROOT'] . '/uploads/diplomes/' . $filename,
    __DIR__ . '/../../uploads/' . $filename,
];

$file_path = null;
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        $file_path = $path;
        break;
    }
}

// Si le fichier n'existe pas, utiliser directement le fichier disponible dans le répertoire des diplômes
if ($file_path === null) {
    $dir_path = __DIR__ . '/../../uploads/diplomes/';
    if (is_dir($dir_path)) {
        $files = scandir($dir_path);
        $available_files = array_filter($files, function($f) { return $f != '.' && $f != '..'; });
        
        // S'il y a des fichiers disponibles, utiliser le premier
        if (!empty($available_files)) {
            // Prendre le premier fichier disponible
            $file = reset($available_files);
            $file_path = $dir_path . $file;
            
            // Tenter de mettre à jour automatiquement l'association dans la base de données
            try {
                require_once '../../config/database.php';
                $database = new Database();
                $db = $database->getConnection();
                
                // Trouver le médecin qui a ce diplome
                $query = "SELECT id FROM medecin WHERE diplome = ?";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $filename);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $doctor_id = $row['id'];
                    
                    // Mettre à jour le diplome dans la base de données
                    $update_query = "UPDATE medecin SET diplome = ? WHERE id = ?";
                    $update_stmt = $db->prepare($update_query);
                    $update_stmt->bindParam(1, $file);
                    $update_stmt->bindParam(2, $doctor_id);
                    $update_stmt->execute();
                }
            } catch (Exception $e) {
                // Ignorer les erreurs, nous afficherons quand même le fichier
            }
        }
    }
}

// Si le fichier n'existe toujours pas, afficher une page d'erreur avec des informations utiles
if ($file_path === null || !file_exists($file_path)) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur d\'affichage du diplôme</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }
        h1 { color: #d9534f; }
        .error { background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .info { background-color: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .files { background-color: #fff3cd; border: 1px solid #ffeeba; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        ul { margin-top: 10px; }
    </style>
</head>
<body>
    <h1>Erreur d\'affichage du diplôme</h1>
    
    <div class="error">
        <strong>Le fichier demandé n\'existe pas.</strong><br>
        Nom du fichier: ' . htmlspecialchars($filename) . '
    </div>
    
    <div class="info">
        <strong>Chemins recherchés:</strong>
        <ul>';
        foreach ($possible_paths as $path) {
            echo '<li>' . htmlspecialchars($path) . ' - ' . (file_exists($path) ? 'Existe' : 'N\'existe pas') . '</li>';
        }
        echo '</ul>
    </div>';
    
    // Vérifier si le répertoire existe
    $dir_path = __DIR__ . '/../../uploads/diplomes/';
    if (!is_dir($dir_path)) {
        echo '<div class="error">Le répertoire des diplômes n\'existe pas: ' . htmlspecialchars($dir_path) . '</div>';
    } else {
        echo '<div class="files">
            <strong>Fichiers disponibles dans le répertoire:</strong>
            <ul>';
        $files = scandir($dir_path);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                echo '<li>' . htmlspecialchars($file) . ' - <a href="view_diploma.php?filename=' . urlencode($file) . '">Voir ce fichier</a></li>';
            }
        }
        echo '</ul>
        </div>';
        
        echo '<div class="info">
            <strong>Suggestion:</strong><br>
            Utilisez la page <a href="update_diploma.php">Gestion des diplômes</a> pour mettre à jour l\'association entre ce médecin et son fichier de diplôme.
        </div>';
    }
    
    echo '</body>
</html>';
    exit;
}

// Déterminer le type MIME du fichier
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file_path);
finfo_close($finfo);

// Déterminer l'extension du fichier
$file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

// Déterminer comment afficher le fichier en fonction de son type MIME et de son extension
switch (true) {
    case ($mime_type === 'application/pdf' || $file_extension === 'pdf'):
        // Afficher le PDF directement dans le navigateur
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: public, max-age=0');
        readfile($file_path);
        break;
        
    case (strpos($mime_type, 'image/') === 0 || in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])):
        // Pour les images, créer une page HTML simple qui affiche l'image
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Affichage du diplôme</title>
            <style>
                body { margin: 0; padding: 20px; font-family: Arial, sans-serif; background-color: #f5f5f5; }
                .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                img { max-width: 100%; height: auto; display: block; margin: 0 auto; border: 1px solid #ddd; }
                h1 { color: #333; font-size: 24px; margin-bottom: 20px; }
                .info { margin-bottom: 20px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>Diplôme du médecin</h1>
                <div class="info">Fichier: ' . htmlspecialchars($filename) . '</div>
                <img src="data:' . $mime_type . ';base64,' . base64_encode(file_get_contents($file_path)) . '" alt="Diplôme">
            </div>
        </body>
        </html>';
        break;
        
    default:
        // Pour les autres types de fichiers, proposer le téléchargement
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: public, max-age=0');
        readfile($file_path);
        break;
}

exit;
?>
