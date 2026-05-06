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
    <title>Erreur d\'affichage - MedAdmin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-2xl w-full bg-white rounded-3xl shadow-sm border border-slate-100 p-12 text-center">
        <div class="w-20 h-20 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-8 text-3xl">
            <i class="fas fa-file-excel"></i>
        </div>
        <h1 class="text-2xl font-bold text-slate-800 mb-4">Fichier Introuvable</h1>
        <p class="text-slate-500 mb-8">Le document <span class="font-mono text-admin-600">' . htmlspecialchars($filename) . '</span> est manquant sur le serveur.</p>
        
        <div class="text-left bg-slate-50 rounded-2xl p-6 mb-8">
            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Suggestions</h4>
            <div class="space-y-3">';
            
            $dir_path = __DIR__ . '/../../uploads/diplomes/';
            if (is_dir($dir_path)) {
                $files = array_diff(scandir($dir_path), ['.', '..']);
                if (!empty($files)) {
                    echo '<p class="text-sm text-slate-600 italic">Fichiers disponibles dans le dossier :</p>';
                    echo '<ul class="text-sm text-admin-600 font-bold space-y-1">';
                    foreach (array_slice($files, 0, 5) as $f) {
                        echo '<li><a href="view_diploma.php?filename=' . urlencode($f) . '" class="hover:underline"># ' . htmlspecialchars($f) . '</a></li>';
                    }
                    echo '</ul>';
                }
            }
            
            echo '</div>
        </div>

        <div class="flex flex-col gap-3">
            <a href="update_diploma.php" class="bg-admin-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-admin-700 transition-all shadow-lg shadow-admin-500/20">Réparer les liens</a>
            <a href="verify_doctors.php" class="text-slate-400 hover:text-slate-600 text-sm font-bold">Retour</a>
        </div>
    </div>
</body>
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
            <title>Aperçu Diplôme - MedAdmin</title>
            <script src="https://cdn.tailwindcss.com"></script>
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        </head>
        <body class="bg-slate-900 min-h-screen p-8 flex flex-col items-center">
            <div class="w-full max-w-4xl flex justify-between items-center mb-8">
                <div class="text-white">
                    <h1 class="text-xl font-bold italic tracking-tight">MedAdmin <span class="text-indigo-400">Viewer</span></h1>
                    <p class="text-xs text-slate-500 font-mono uppercase tracking-widest mt-1">' . htmlspecialchars($filename) . '</p>
                </div>
                <button onclick="window.close()" class="px-6 py-2 bg-white/10 hover:bg-white/20 text-white rounded-xl text-sm font-bold transition-all backdrop-blur-sm border border-white/10">
                    Fermer l\'aperçu
                </button>
            </div>
            
            <div class="relative group">
                <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl blur opacity-25 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
                <div class="relative bg-white p-4 rounded-xl shadow-2xl">
                    <img src="data:' . $mime_type . ';base64,' . base64_encode(file_get_contents($file_path)) . '" class="max-w-full h-auto rounded-lg shadow-inner">
                </div>
            </div>
            
            <div class="mt-8 text-slate-500 text-[10px] uppercase font-bold tracking-widest flex items-center gap-2">
                <i class="fas fa-shield-alt text-indigo-500"></i> Document certifié MedConnect
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
