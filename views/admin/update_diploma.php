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

// Identifier les médecins dont le fichier de diplôme est manquant
$doctors_with_missing_files = [];

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

// Identifier les médecins dont le fichier de diplôme est manquant
foreach ($doctors as $key => $doctor) {
    if (!in_array($doctor['diplome'], $available_files)) {
        $doctors_with_missing_files[$doctor['id']] = $doctor;
        
        // Essayer de trouver un fichier avec un nom similaire
        $suggested_file = null;
        foreach ($available_files as $file) {
            if (stripos($file, pathinfo($doctor['diplome'], PATHINFO_FILENAME)) !== false) {
                $suggested_file = $file;
                break;
            }
        }
        
        // Si aucun fichier similaire n'est trouvé et qu'il n'y a qu'un seul fichier disponible, le suggérer
        if ($suggested_file === null && count($available_files) === 1) {
            $suggested_file = $available_files[0];
        }
        
        $doctors[$key]['suggested_file'] = $suggested_file;
    }
}

// Correction automatique si demandée
if (isset($_GET['auto_fix']) && $_GET['auto_fix'] === 'true') {
    $fixed_count = 0;
    
    foreach ($doctors_with_missing_files as $doctor) {
        if (isset($doctor['suggested_file']) && $doctor['suggested_file'] !== null) {
            // Mettre à jour le diplôme dans la base de données
            $update_query = "UPDATE medecin SET diplome = ? WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(1, $doctor['suggested_file']);
            $update_stmt->bindParam(2, $doctor['id']);
            
            if ($update_stmt->execute()) {
                $fixed_count++;
            }
        }
    }
    
    if ($fixed_count > 0) {
        showMessage('success', "$fixed_count association(s) de diplôme ont été corrigées automatiquement.");
        
        // Recharger les données après la correction
        $query = "SELECT id, nom, prenom, diplome FROM medecin WHERE diplome IS NOT NULL AND diplome != ''";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $doctors_with_missing_files = [];
    } else {
        showMessage('error', "Aucune association n'a pu être corrigée automatiquement.");
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        showMessage('error', "Erreur de sécurité : token CSRF invalide");
    } else {
        if (isset($_POST['doctor_id']) && isset($_POST['new_diploma'])) {
            $doctor_id = intval($_POST['doctor_id']);
            $new_diploma = $_POST['new_diploma'];
            
            // Vérifier que le fichier existe
            if (!in_array($new_diploma, $available_files)) {
                showMessage('error', "Le fichier sélectionné n'existe pas dans le répertoire des diplômes.");
            } else {
                // Mettre à jour le diplôme dans la base de données
                $update_query = "UPDATE medecin SET diplome = ? WHERE id = ?";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(1, $new_diploma);
                $update_stmt->bindParam(2, $doctor_id);
                
                if ($update_stmt->execute()) {
                    showMessage('success', "Le diplôme a été mis à jour avec succès.");
                } else {
                    showMessage('error', "Erreur lors de la mise à jour du diplôme.");
                }
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
    <title>Mise à jour des Diplômes | MedApp</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="admin-sidebar-header">
                <a href="dashboard.php" class="admin-sidebar-logo">
                    <i class="fas fa-heartbeat"></i>
                    <span>MedApp Admin</span>
                </a>
                <button class="admin-sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <div class="admin-sidebar-nav">
                <a href="dashboard.php" class="admin-sidebar-nav-item">
                    <i class="fas fa-home"></i>
                    <span>Tableau de bord</span>
                </a>
                <a href="doctors.php" class="admin-sidebar-nav-item">
                    <i class="fas fa-user-md"></i>
                    <span>Médecins</span>
                </a>
                <a href="patients.php" class="admin-sidebar-nav-item">
                    <i class="fas fa-procedures"></i>
                    <span>Patients</span>
                </a>
                <a href="verify_doctors.php" class="admin-sidebar-nav-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Vérification</span>
                </a>
                <a href="settings.php" class="admin-sidebar-nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Paramètres</span>
                </a>
            </div>
            <div class="admin-sidebar-footer">
                <div class="admin-sidebar-user">
                    <div class="admin-sidebar-user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="admin-sidebar-user-info">
                        <div class="admin-sidebar-user-name"><?php echo isset($_SESSION['prenom'], $_SESSION['nom']) ? htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) : 'Administrateur'; ?></div>
                        <div class="admin-sidebar-user-role">Administrateur</div>
                    </div>
                </div>
                <a href="../logout.php" class="admin-sidebar-nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Déconnexion</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="admin-main">
            <!-- Top Bar -->
            <div class="admin-header">
                <h1 class="admin-header-title">Mise à jour des Diplômes</h1>
                <div class="admin-header-actions">
                    <div class="admin-flex admin-items-center admin-gap-4">
                        <a href="verify_doctors.php" class="admin-btn admin-btn-primary">
                            <i class="fas fa-arrow-left"></i>
                            <span>Retour à la vérification</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="admin-content">
                <?php if (!empty($doctors_with_missing_files)): ?>
                <div class="admin-card admin-mb-6">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Problèmes détectés</h2>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-alert admin-alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span><?php echo count($doctors_with_missing_files); ?> médecin(s) ont des fichiers de diplôme manquants.</span>
                        </div>
                        
                        <div class="admin-table-responsive admin-mt-4">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Médecin</th>
                                        <th>Diplôme actuel</th>
                                        <th>Suggestion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($doctors_with_missing_files as $doctor): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($doctor['prenom'] . ' ' . $doctor['nom']); ?></td>
                                            <td>
                                                <span class="admin-badge admin-badge-danger"><?php echo htmlspecialchars($doctor['diplome']); ?></span>
                                            </td>
                                            <td>
                                                <?php if (isset($doctor['suggested_file']) && $doctor['suggested_file'] !== null): ?>
                                                    <span class="admin-badge admin-badge-success"><?php echo htmlspecialchars($doctor['suggested_file']); ?></span>
                                                <?php else: ?>
                                                    <span class="admin-badge admin-badge-warning">Aucune suggestion</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="admin-mt-4">
                            <a href="update_diploma.php?auto_fix=true" class="admin-btn admin-btn-primary">
                                <i class="fas fa-magic"></i>
                                <span>Corriger automatiquement</span>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Fichiers disponibles dans le répertoire des diplômes</h2>
                    </div>
                    <div class="admin-card-body">
                        <?php if (empty($available_files)): ?>
                            <div class="admin-alert admin-alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>Aucun fichier trouvé dans le répertoire des diplômes.</span>
                            </div>
                        <?php else: ?>
                            <ul class="admin-list">
                                <?php foreach ($available_files as $file): ?>
                                    <li class="admin-list-item">
                                        <div class="admin-flex admin-items-center admin-gap-2">
                                            <i class="fas fa-file-pdf"></i>
                                            <span><?php echo htmlspecialchars($file); ?></span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="admin-card admin-mt-6">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Médecins avec diplômes</h2>
                    </div>
                    <div class="admin-card-body">
                        <?php if (empty($doctors)): ?>
                            <div class="admin-alert admin-alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>Aucun médecin avec diplôme trouvé dans la base de données.</span>
                            </div>
                        <?php else: ?>
                            <div class="admin-table-responsive">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Médecin</th>
                                            <th>Diplôme actuel</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($doctors as $doctor): ?>
                                            <tr>
                                                <td><?php echo $doctor['id']; ?></td>
                                                <td><?php echo htmlspecialchars($doctor['prenom'] . ' ' . $doctor['nom']); ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($doctor['diplome']); ?>
                                                    <?php if (in_array($doctor['diplome'], $available_files)): ?>
                                                        <span class="admin-badge admin-badge-success">Fichier existant</span>
                                                    <?php else: ?>
                                                        <span class="admin-badge admin-badge-danger">Fichier manquant</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <form method="POST" class="admin-flex admin-gap-2">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                        <input type="hidden" name="doctor_id" value="<?php echo $doctor['id']; ?>">
                                                        <select name="new_diploma" class="admin-form-control admin-form-control-sm">
                                                            <?php foreach ($available_files as $file): ?>
                                                                <option value="<?php echo htmlspecialchars($file); ?>" <?php echo $file === $doctor['diplome'] ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($file); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <button type="submit" class="admin-btn admin-btn-sm admin-btn-primary">
                                                            <i class="fas fa-save"></i>
                                                            <span>Mettre à jour</span>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/admin.js"></script>
</body>
</html>
