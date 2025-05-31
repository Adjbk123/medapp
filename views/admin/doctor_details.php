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

// Vérifier si un ID a été fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "ID de médecin non spécifié.";
    header('Location: verify_doctors.php');
    exit;
}

$medecin_id = intval($_GET['id']);

// Connexion à la base de données
try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    $_SESSION['error'] = "Erreur de connexion à la base de données: " . $e->getMessage();
    header('Location: verify_doctors.php');
    exit;
}

// Récupérer les détails complets du médecin
$query = "SELECT m.*, s.nomspecialite 
          FROM medecin m 
          LEFT JOIN specialite s ON m.idspecialite = s.id 
          WHERE m.id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $medecin_id);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    $_SESSION['error'] = "Médecin non trouvé.";
    header('Location: verify_doctors.php');
    exit;
}

$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

// Inclure la classe MedecinHistory
require_once '../../models/MedecinHistory.php';

// Récupérer l'historique du médecin
$history = new MedecinHistory($db);
$history_entries = $history->getHistoryByMedecinId($medecin_id, 20); // Récupérer les 20 dernières entrées

// Formater la date de naissance
$date_naissance = !empty($doctor['datenais']) ? date('d/m/Y', strtotime($doctor['datenais'])) : 'Non spécifiée';

// Vérifier si le médecin a un diplôme
$diplome_filename = $doctor['diplome'] ?? '';
$has_diplome = !empty($diplome_filename);

// Créer l'URL pour afficher le diplôme via notre script dédié
$diplome_url = 'view_diploma.php?filename=' . urlencode($diplome_filename);

// Vérifier si le fichier existe physiquement
$diplome_path = __DIR__ . '/../../uploads/diplomes/' . $diplome_filename;
$file_exists = file_exists($diplome_path);

// Déterminer le type de fichier pour afficher l'icône appropriée
$file_extension = pathinfo($diplome_filename, PATHINFO_EXTENSION);
$file_icon = 'fa-file';
switch (strtolower($file_extension)) {
    case 'pdf':
        $file_icon = 'fa-file-pdf';
        break;
    case 'jpg':
    case 'jpeg':
    case 'png':
    case 'gif':
        $file_icon = 'fa-file-image';
        break;
    case 'doc':
    case 'docx':
        $file_icon = 'fa-file-word';
        break;
    default:
        $file_icon = 'fa-file';
}

// Traitement du formulaire de vérification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Erreur de sécurité : token CSRF invalide";
    } else {
        if (isset($_POST['action']) && isset($_POST['medecin_id'])) {
            $action = $_POST['action'];
            $medecin_id = intval($_POST['medecin_id']);
            $medecin = new Medecin($db);
            $medecin->id = $medecin_id;
            
            switch ($action) {
                case 'verify':
                    // Vérifier le médecin
                    if ($medecin->verify()) {
                        // Enregistrer l'action dans l'historique
                        $history->addEntry(
                            $medecin_id,
                            'verification',
                            'Compte vérifié et activé par l\'administrateur.'
                        );
                        
                        $_SESSION['success'] = "Le médecin a été vérifié avec succès.";
                        header("Location: verify_doctors.php");
                        exit;
                    } else {
                        $_SESSION['error'] = "Erreur lors de la vérification du médecin.";
                    }
                    break;
                    
                case 'reject':
                    // Rejeter le médecin
                    $reason = isset($_POST['rejection_reason']) ? $_POST['rejection_reason'] : '';
                    if ($medecin->reject($reason)) {
                        // Enregistrer l'action dans l'historique
                        $history->addEntry(
                            $medecin_id,
                            'rejection',
                            'Compte rejeté par l\'administrateur. Raison : ' . ($reason ? $reason : 'Non spécifiée')
                        );
                        
                        $_SESSION['success'] = "Le médecin a été rejeté avec succès.";
                        header("Location: verify_doctors.php");
                        exit;
                    } else {
                        $_SESSION['error'] = "Erreur lors du rejet du médecin.";
                    }
                    break;
                    
                default:
                    $_SESSION['error'] = "Action non reconnue.";
                    break;
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
    <title>Détails du Médecin | MedApp</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/admin-timeline.css">
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
                <a href="verify_doctors.php" class="admin-sidebar-nav-item active">
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
                <h1 class="admin-header-title">Détails du Médecin</h1>
                <div class="admin-header-actions">
                    <div class="admin-flex admin-items-center admin-gap-4">
                        <a href="verify_doctors.php" class="admin-btn admin-btn-primary">
                            <i class="fas fa-arrow-left"></i>
                            <span>Retour à la liste</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="admin-content">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="admin-alert admin-alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                        <button class="admin-alert-close admin-ml-auto"><i class="fas fa-times"></i></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="admin-alert admin-alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
                        <button class="admin-alert-close admin-ml-auto"><i class="fas fa-times"></i></button>
                    </div>
                <?php endif; ?>
                
                <div class="admin-grid admin-grid-cols-1 admin-md:grid-cols-2 admin-gap-6">
                    <!-- Informations personnelles -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h4 class="admin-card-title">Informations personnelles</h4>
                        </div>
                        <div class="admin-card-body">
                            <div class="admin-flex admin-items-center admin-gap-4 admin-mb-4">
                                <div class="admin-stat-icon warning" style="width: 4rem; height: 4rem;">
                                    <i class="fas fa-user-md" style="font-size: 2rem;"></i>
                                </div>
                                <div>
                                    <h3 class="admin-text-xl admin-font-bold"><?php echo htmlspecialchars($doctor['prenom'] . ' ' . $doctor['nom']); ?></h3>
                                    <p class="admin-text-muted"><?php echo htmlspecialchars($doctor['nomspecialite'] ?? 'Spécialité non spécifiée'); ?></p>
                                </div>
                            </div>
                            
                            <div class="admin-grid admin-grid-cols-2 admin-gap-4">
                                <div class="admin-form-group">
                                    <label class="admin-form-label">Nom</label>
                                    <p class="admin-form-control-static"><?php echo htmlspecialchars($doctor['nom']); ?></p>
                                </div>
                                <div class="admin-form-group">
                                    <label class="admin-form-label">Prénom</label>
                                    <p class="admin-form-control-static"><?php echo htmlspecialchars($doctor['prenom']); ?></p>
                                </div>
                                <div class="admin-form-group">
                                    <label class="admin-form-label">Date de naissance</label>
                                    <p class="admin-form-control-static"><?php echo $date_naissance; ?></p>
                                </div>
                                <div class="admin-form-group">
                                    <label class="admin-form-label">Email</label>
                                    <p class="admin-form-control-static"><?php echo htmlspecialchars($doctor['email']); ?></p>
                                </div>
                                <div class="admin-form-group">
                                    <label class="admin-form-label">Téléphone</label>
                                    <p class="admin-form-control-static"><?php echo htmlspecialchars($doctor['contact'] ?? 'Non spécifié'); ?></p>
                                </div>
                                <div class="admin-form-group">
                                    <label class="admin-form-label">Date d'inscription</label>
                                    <p class="admin-form-control-static"><?php echo !empty($doctor['created_at']) ? date('d/m/Y H:i', strtotime($doctor['created_at'])) : 'Non spécifiée'; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informations professionnelles -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h4 class="admin-card-title">Informations professionnelles</h4>
                        </div>
                        <div class="admin-card-body">
                            <div class="admin-grid admin-grid-cols-2 admin-gap-4">
                                <div class="admin-form-group">
                                    <label class="admin-form-label">Spécialité</label>
                                    <p class="admin-form-control-static"><?php echo htmlspecialchars($doctor['nomspecialite'] ?? 'Non spécifiée'); ?></p>
                                </div>
                                <div class="admin-form-group">
                                    <label class="admin-form-label">Numéro RPPS</label>
                                    <p class="admin-form-control-static">
                                        <span class="admin-badge admin-badge-primary">
                                            <?php echo htmlspecialchars($doctor['num'] ?? 'Non spécifié'); ?>
                                        </span>
                                    </p>
                                </div>
                                <?php if (isset($doctor['experience'])): ?>
                                <div class="admin-form-group">
                                    <label class="admin-form-label">Années d'expérience</label>
                                    <p class="admin-form-control-static"><?php echo htmlspecialchars($doctor['experience']); ?> ans</p>
                                </div>
                                <?php endif; ?>
                                <?php if (isset($doctor['hopital'])): ?>
                                <div class="admin-form-group">
                                    <label class="admin-form-label">Hôpital/Clinique</label>
                                    <p class="admin-form-control-static"><?php echo htmlspecialchars($doctor['hopital']); ?></p>
                                </div>
                                <?php endif; ?>
                                <div class="admin-form-group admin-col-span-2">
                                    <label class="admin-form-label">Diplôme</label>
                                    <?php if ($has_diplome): ?>
                                        <?php if ($file_exists): ?>
                                        <div class="admin-flex admin-items-center admin-gap-2">
                                            <a href="<?php echo $diplome_url; ?>" target="_blank" class="admin-btn admin-btn-sm admin-btn-primary">
                                                <i class="fas <?php echo $file_icon; ?>"></i>
                                                <span>Voir le diplôme</span>
                                            </a>
                                            <span class="admin-badge admin-badge-success">Fichier disponible</span>
                                            <span class="admin-text-sm admin-text-muted"><?php echo htmlspecialchars($diplome_filename); ?></span>
                                        </div>
                                        <?php else: ?>
                                        <div class="admin-flex admin-flex-col admin-gap-2">
                                            <div class="admin-flex admin-items-center admin-gap-2">
                                                <span class="admin-badge admin-badge-warning">Fichier non trouvé</span>
                                                <span class="admin-text-sm admin-text-muted"><?php echo htmlspecialchars($diplome_filename); ?></span>
                                            </div>
                                            <p class="admin-text-sm admin-text-warning">
                                                Le fichier est référencé dans la base de données mais n'existe pas physiquement sur le serveur.
                                            </p>
                                        </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                    <p class="admin-form-control-static">
                                        <span class="admin-badge admin-badge-danger">Aucun diplôme fourni</span>
                                    </p>
                                    <?php endif; ?>
                                    
                                    <!-- <div class="admin-mt-4">
                                        <a href="update_diploma.php?doctor_id=<?php echo $doctor['id']; ?>" class="admin-btn admin-btn-sm admin-btn-secondary">
                                            <i class="fas fa-sync"></i>
                                            <span>Gérer les diplômes</span>
                                        </a>
                                        <p class="admin-text-sm admin-text-muted admin-mt-1">
                                            Si le diplôme ne s'affiche pas correctement, utilisez cette page pour mettre à jour les associations entre médecins et fichiers de diplômes.
                                        </p>
                                    </div> -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statut de vérification -->
                <div class="admin-card admin-mt-6">
                    <div class="admin-card-header">
                        <h4 class="admin-card-title">Statut de vérification</h4>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-flex admin-items-center admin-gap-4">
                            <div class="admin-stat-icon warning">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <h5 class="admin-text-xl admin-font-semibold"><?php echo ucfirst($doctor['verification_status']); ?></h5>
                                <p class="admin-text-gray-600">Statut actuel du compte</p>
                            </div>
                        </div>
                        
                        <?php if ($doctor['verification_status'] === 'pending'): ?>
                        <div class="admin-mt-6">
                            <form method="POST" class="admin-flex admin-gap-2">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <button type="submit" name="action" value="verify" class="admin-btn admin-btn-success">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Vérifier ce médecin</span>
                                </button>
                                <button type="submit" name="action" value="reject" class="admin-btn admin-btn-danger">
                                    <i class="fas fa-times-circle"></i>
                                    <span>Rejeter ce médecin</span>
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Historique des actions -->
                <div class="admin-card admin-mt-6">
                    <div class="admin-card-header">
                        <h4 class="admin-card-title">Historique</h4>
                    </div>
                    <div class="admin-card-body">
                        <?php if (empty($history_entries)): ?>
                            <div class="admin-alert admin-alert-info">
                                <i class="fas fa-info-circle"></i>
                                <span>Aucune action n'a encore été enregistrée pour ce médecin.</span>
                            </div>
                        <?php else: ?>
                            <div class="admin-timeline">
                                <?php foreach ($history_entries as $entry): ?>
                                    <div class="admin-timeline-item">
                                        <div class="admin-timeline-marker">
                                            <?php 
                                            $icon_class = 'fa-info-circle';
                                            $color_class = 'admin-bg-blue-500';
                                            
                                            switch ($entry['action']) {
                                                case 'verification':
                                                    $icon_class = 'fa-check-circle';
                                                    $color_class = 'admin-bg-green-500';
                                                    break;
                                                case 'rejection':
                                                    $icon_class = 'fa-times-circle';
                                                    $color_class = 'admin-bg-red-500';
                                                    break;
                                                case 'update':
                                                    $icon_class = 'fa-edit';
                                                    $color_class = 'admin-bg-blue-500';
                                                    break;
                                                case 'diploma_update':
                                                    $icon_class = 'fa-file-alt';
                                                    $color_class = 'admin-bg-purple-500';
                                                    break;
                                            }
                                            ?>
                                            <div class="admin-timeline-marker-icon <?php echo $color_class; ?>">
                                                <i class="fas <?php echo $icon_class; ?>"></i>
                                            </div>
                                        </div>
                                        <div class="admin-timeline-content">
                                            <div class="admin-timeline-time">
                                                <?php echo date('d/m/Y H:i', strtotime($entry['created_at'])); ?>
                                            </div>
                                            <h5 class="admin-timeline-title">
                                                <?php 
                                                $action_text = 'Action';
                                                switch ($entry['action']) {
                                                    case 'verification':
                                                        $action_text = 'Vérification du compte';
                                                        break;
                                                    case 'rejection':
                                                        $action_text = 'Rejet du compte';
                                                        break;
                                                    case 'update':
                                                        $action_text = 'Mise à jour des informations';
                                                        break;
                                                    case 'diploma_update':
                                                        $action_text = 'Mise à jour du diplôme';
                                                        break;
                                                    default:
                                                        $action_text = ucfirst($entry['action']);
                                                }
                                                echo $action_text;
                                                ?>
                                            </h5>
                                            <?php if (!empty($entry['details'])): ?>
                                                <p class="admin-timeline-description">
                                                    <?php echo htmlspecialchars($entry['details']); ?>
                                                </p>
                                            <?php endif; ?>
                                            <div class="admin-timeline-meta">
                                                <span class="admin-text-sm admin-text-gray-600">
                                                    Par <?php echo htmlspecialchars($entry['admin_name']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Formulaire de vérification -->
                <div class="admin-card admin-mt-6">
                    <div class="admin-card-header">
                        <h4 class="admin-card-title">Vérification</h4>
                    </div>
                    <div class="admin-card-body">
                        <form method="POST" class="admin-mt-4">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="medecin_id" value="<?php echo $doctor['id']; ?>">
                            
                            <div class="admin-form-group">
                                <label class="admin-form-label">Commentaire (optionnel)</label>
                                <textarea name="commentaire" class="admin-form-control" rows="3" placeholder="Ajoutez un commentaire concernant la vérification..."></textarea>
                                <p class="admin-form-text">Ce commentaire sera enregistré dans l'historique de vérification.</p>
                            </div>
                            
                            <div class="admin-flex admin-justify-end admin-gap-4 admin-mt-6">
                                <button type="submit" name="action" value="reject" class="admin-btn admin-btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir rejeter ce médecin ?')">
                                    <i class="fas fa-times"></i>
                                    <span>Rejeter ce médecin</span>
                                </button>
                                <button type="submit" name="action" value="verify" class="admin-btn admin-btn-success">
                                    <i class="fas fa-check"></i>
                                    <span>Valider ce médecin</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/admin.js"></script>
    <script>
        // Fermer les alertes lorsqu'on clique sur le bouton de fermeture
        document.querySelectorAll('.admin-alert-close').forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.admin-alert').remove();
            });
        });
    </script>
</body>
</html>
