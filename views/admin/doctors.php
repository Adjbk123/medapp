<?php
require_once '../../includes/session.php';
require_once '../../includes/security.php';
require_once '../../config/database.php';
require_once '../../models/Medecin.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
requireLogin();
requireRole('admin');

// Récupérer les informations de l'administrateur connecté
$user_id = $_SESSION['user_id'];
$nom = $_SESSION['nom'] ?? '';
$prenom = $_SESSION['prenom'] ?? '';

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();
$medecin = new Medecin($db);

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Erreur de sécurité : token CSRF invalide";
    } else {
        if (isset($_POST['action']) && isset($_POST['medecin_id'])) {
            $medecin->id = $_POST['medecin_id'];
            
            switch ($_POST['action']) {
                case 'verify':
                    if ($medecin->updateVerificationStatus('verified')) {
                        $medecin->sendVerificationConfirmationEmail();
                        $success = "Le médecin a été vérifié avec succès.";
                    } else {
                        $error = "Une erreur s'est produite lors de la vérification.";
                    }
                    break;
                    
                case 'reject':
                    if ($medecin->updateVerificationStatus('rejected')) {
                        $medecin->sendRejectionEmail();
                        $success = "Le médecin a été rejeté.";
                    } else {
                        $error = "Une erreur s'est produite lors du rejet.";
                    }
                    break;
                    
                case 'delete':
                    if ($medecin->delete()) {
                        $success = "Le médecin a été supprimé avec succès.";
                    } else {
                        $error = "Une erreur s'est produite lors de la suppression.";
                    }
                    break;
            }
        }
    }
}

// Récupérer la liste des médecins avec leurs spécialités
$query = "SELECT m.*, s.nomspecialite 
          FROM medecin m 
          LEFT JOIN specialite s ON m.idspecialite = s.id 
          ORDER BY m.id DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Médecins | MedApp</title>
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
                <a href="doctors.php" class="admin-sidebar-nav-item active">
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
                        <div class="admin-sidebar-user-name"><?php echo htmlspecialchars($prenom . ' ' . $nom); ?></div>
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
                <h1 class="admin-header-title">Gestion des Médecins</h1>
                <div class="admin-header-actions">
                    <div class="admin-header-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="search" placeholder="Rechercher un médecin..." class="admin-form-control">
                    </div>
                    <div class="admin-flex admin-items-center admin-gap-4">
                        <a href="verify_doctors.php" class="admin-btn admin-btn-success">
                            <i class="fas fa-check-circle"></i>
                            <span>Vérifier les médecins</span>
                        </a>
                        <label class="admin-flex admin-items-center admin-gap-2">
                            <input type="checkbox" id="dark-mode-toggle" class="admin-form-control" style="width: auto;">
                            <span>Mode sombre</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="admin-content">
                <?php if (isset($success)): ?>
                    <div class="admin-alert admin-alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo $success; ?></span>
                        <button class="admin-alert-close admin-ml-auto"><i class="fas fa-times"></i></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="admin-alert admin-alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $error; ?></span>
                        <button class="admin-alert-close admin-ml-auto"><i class="fas fa-times"></i></button>
                    </div>
                <?php endif; ?>

                <!-- Filtres et recherche -->
                <div class="admin-card admin-mb-4">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Filtres</h2>
                    </div>
                    <div class="admin-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="admin-form-group">
                                <label class="admin-form-label">Statut</label>
                                <select id="status-filter" class="admin-form-control admin-form-select">
                                    <option value="">Tous les statuts</option>
                                    <option value="pending">En attente</option>
                                    <option value="verified">Vérifié</option>
                                    <option value="rejected">Rejeté</option>
                                </select>
                            </div>
                            <div class="admin-form-group">
                                <label class="admin-form-label">Spécialité</label>
                                <select id="specialty-filter" class="admin-form-control admin-form-select">
                                    <option value="">Toutes les spécialités</option>
                                    <?php
                                    $query = "SELECT DISTINCT s.nomspecialite FROM specialite s JOIN medecin m ON s.id = m.idspecialite";
                                    $stmt = $db->query($query);
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<option value="' . htmlspecialchars($row['nomspecialite']) . '">' . 
                                             htmlspecialchars($row['nomspecialite']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="admin-form-group">
                                <label class="admin-form-label">Actions</label>
                                <div class="admin-flex admin-gap-2">
                                    <button id="reset-filters" class="admin-btn admin-btn-outline">
                                        <i class="fas fa-redo"></i>
                                        <span>Réinitialiser</span>
                                    </button>
                                    <a href="verify_doctors.php" class="admin-btn admin-btn-success">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Vérification</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Liste des médecins -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Liste des médecins</h2>
                        <div class="admin-flex admin-gap-2">
                            <span class="admin-badge admin-badge-primary">
                                Total: <?php echo count($doctors); ?>
                            </span>
                        </div>
                    </div>
                    <div class="admin-table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Médecin</th>
                                    <th>Spécialité</th>
                                    <th>Contact</th>
                                    <th>Statut</th>
                                    <th>Date d'inscription</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($doctors)): ?>
                                    <tr>
                                        <td colspan="6" class="admin-text-center">Aucun médecin trouvé</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($doctors as $doctor): ?>
                                        <tr class="doctor-row" 
                                            data-status="<?php echo htmlspecialchars($doctor['verification_status']); ?>"
                                            data-specialty="<?php echo htmlspecialchars($doctor['nomspecialite']); ?>">
                                            <td>
                                                <div class="admin-flex admin-items-center admin-gap-2">
                                                    <div class="admin-stat-icon primary" style="width: 2.5rem; height: 2.5rem;">
                                                        <i class="fas fa-user-md"></i>
                                                    </div>
                                                    <div>
                                                        <div class="admin-font-bold">
                                                            <?php echo htmlspecialchars($doctor['prenom'] . ' ' . $doctor['nom']); ?>
                                                        </div>
                                                        <div class="admin-text-sm" style="color: var(--text-muted);">
                                                            <?php echo htmlspecialchars($doctor['num'] ?? 'RPPS non spécifié'); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span><?php echo htmlspecialchars($doctor['nomspecialite'] ?? 'Non spécifié'); ?></span>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="admin-flex admin-items-center admin-gap-2">
                                                        <i class="fas fa-envelope"></i>
                                                        <span><?php echo htmlspecialchars($doctor['email']); ?></span>
                                                    </div>
                                                    <?php if (!empty($doctor['telephone'])): ?>
                                                    <div class="admin-flex admin-items-center admin-gap-2 admin-text-sm" style="color: var(--text-muted);">
                                                        <i class="fas fa-phone"></i>
                                                        <span><?php echo htmlspecialchars($doctor['telephone']); ?></span>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($doctor['verification_status'] === 'verified'): ?>
                                                    <span class="admin-badge admin-badge-success">
                                                        <i class="fas fa-check-circle"></i>
                                                        <span>Vérifié</span>
                                                    </span>
                                                <?php elseif ($doctor['verification_status'] === 'pending'): ?>
                                                    <span class="admin-badge admin-badge-warning">
                                                        <i class="fas fa-clock"></i>
                                                        <span>En attente</span>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="admin-badge admin-badge-danger">
                                                        <i class="fas fa-times-circle"></i>
                                                        <span>Rejeté</span>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span><?php echo date('d/m/Y', strtotime($doctor['created_at'])); ?></span>
                                            </td>
                                            <td>
                                                <div class="admin-flex admin-gap-2">
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                        <input type="hidden" name="medecin_id" value="<?php echo $doctor['id']; ?>">
                                                        
                                                        <?php if ($doctor['verification_status'] === 'pending'): ?>
                                                            <button type="submit" name="action" value="verify" class="admin-btn admin-btn-sm admin-btn-success" data-tooltip="Vérifier ce médecin">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button type="submit" name="action" value="reject" class="admin-btn admin-btn-sm admin-btn-warning" data-tooltip="Rejeter ce médecin">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        
                                                        <button type="submit" name="action" value="delete" class="admin-btn admin-btn-sm admin-btn-danger" data-confirm="Êtes-vous sûr de vouloir supprimer ce médecin ?" data-tooltip="Supprimer ce médecin">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                    <a href="doctor_details.php?id=<?php echo $doctor['id']; ?>" class="admin-btn admin-btn-sm admin-btn-primary" data-tooltip="Voir les détails">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/admin.js"></script>
    <script>
        // Reset filters button
        document.getElementById('reset-filters').addEventListener('click', function() {
            document.getElementById('search').value = '';
            document.getElementById('status-filter').value = '';
            document.getElementById('specialty-filter').value = '';
            filterTable(); // Cette fonction est définie dans admin.js
        });
    </script>
</body>
</html>