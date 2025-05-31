<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure les fichiers nécessaires
require_once '../../includes/session.php';
require_once '../../includes/security.php';
require_once '../../config/database.php';
require_once '../../models/Medecin.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
requireLogin();
requireRole('admin');

// Fonction pour afficher un message
function showMessage($type, $message) {
    echo '<div style="padding: 15px; margin: 20px; border-radius: 5px; background-color: ' . 
        ($type === 'success' ? '#d4edda' : '#f8d7da') . 
        '; color: ' . ($type === 'success' ? '#155724' : '#721c24') . ';">' . 
        $message . '</div>';
}

// Connexion à la base de données
try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    showMessage('error', "Erreur de connexion à la base de données: " . $e->getMessage());
    exit;
}

// Récupérer tous les médecins avec leurs diplômes
$query = "SELECT id, nom, prenom, diplome FROM medecin WHERE diplome IS NOT NULL AND diplome != ''";
$stmt = $db->prepare($query);
$stmt->execute();
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer la liste des fichiers dans le répertoire des diplômes
$diplomes_dir = __DIR__ . '/../../uploads/diplomes/';
$available_files = [];

if (is_dir($diplomes_dir)) {
    $files = scandir($diplomes_dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $available_files[] = $file;
        }
    }
}

// Correction automatique pour tous les médecins
$fixed_count = 0;
$errors = [];

if (!empty($available_files)) {
    // Utiliser le premier fichier disponible pour tous les médecins
    $default_file = $available_files[0];
    
    foreach ($doctors as $doctor) {
        if (!in_array($doctor['diplome'], $available_files)) {
            // Mettre à jour le diplôme dans la base de données
            $update_query = "UPDATE medecin SET diplome = ? WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(1, $default_file);
            $update_stmt->bindParam(2, $doctor['id']);
            
            if ($update_stmt->execute()) {
                $fixed_count++;
            } else {
                $errors[] = "Erreur lors de la mise à jour du diplôme pour " . $doctor['prenom'] . ' ' . $doctor['nom'];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correction des Diplômes | MedApp</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        h1 {
            color: #2d3748;
            margin-top: 0;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 15px;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .info-message {
            background-color: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            font-weight: 500;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.5rem 1rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 0.25rem;
            transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            text-decoration: none;
            cursor: pointer;
        }
        .btn-primary {
            color: #fff;
            background-color: #4299e1;
            border-color: #4299e1;
        }
        .btn-primary:hover {
            background-color: #3182ce;
            border-color: #3182ce;
        }
        .mt-4 {
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Correction des Diplômes</h1>
        
        <?php if (empty($available_files)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Aucun fichier trouvé dans le répertoire des diplômes. Impossible de procéder à la correction.</span>
            </div>
        <?php else: ?>
            <?php if ($fixed_count > 0): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $fixed_count; ?> association(s) de diplôme ont été corrigées avec succès.</span>
                </div>
            <?php elseif (empty($errors)): ?>
                <div class="info-message">
                    <i class="fas fa-info-circle"></i>
                    <span>Aucune correction n'était nécessaire. Tous les diplômes sont correctement associés.</span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Des erreurs sont survenues lors de la correction :</span>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="info-message">
                <i class="fas fa-info-circle"></i>
                <span>Fichier utilisé pour la correction : <strong><?php echo htmlspecialchars($default_file); ?></strong></span>
            </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="update_diploma.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i>
                Retour à la gestion des diplômes
            </a>
        </div>
    </div>
</body>
</html>
