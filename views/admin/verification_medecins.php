<?php
require_once '../../includes/session.php';
require_once '../../includes/security.php';
requireRole('admin');
require_once '../config/database.php';
require_once '../models/ProfilMedecin.php';

$db = new Database();
$profilMedecin = new ProfilMedecin($db->getConnection());

// Traiter les actions de vérification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_medecin = $_POST['id_medecin'];
    $action = $_POST['action'];
    $commentaire = $_POST['commentaire'] ?? null;

    if ($action === 'verify') {
        $profilMedecin->updateVerificationStatus($id_medecin, 'verified', $commentaire);
    } elseif ($action === 'reject') {
        $profilMedecin->updateVerificationStatus($id_medecin, 'rejected', $commentaire);
    }

    header('Location: verification_medecins.php?success=1');
    exit();
}

// Récupérer tous les profils médecins en attente de vérification
$query = "SELECT m.*, p.* FROM medecin m 
          LEFT JOIN profilmedecin p ON m.id = p.id_medecin 
          WHERE m.verification_status = 'pending' 
          ORDER BY p.created_at DESC";
$stmt = $db->getConnection()->prepare($query);
$stmt->execute();
$medecins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification des Médecins | MedApp</title>
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
                        <div class="admin-sidebar-user-name"><?php echo isset($_SESSION['nom']) && isset($_SESSION['prenom']) ? htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) : 'Admin'; ?></div>
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
                <h1 class="admin-header-title">Vérification des Médecins</h1>
                <div class="admin-header-actions">
                    <div class="admin-flex admin-items-center admin-gap-4">
                        <label class="admin-flex admin-items-center admin-gap-2">
                            <input type="checkbox" id="dark-mode-toggle" class="admin-form-control" style="width: auto;">
                            <span>Mode sombre</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Message de succès -->
            <?php if (isset($_GET['success'])): ?>
            <div class="admin-content">
                <div class="admin-alert admin-alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span>L'action a été effectuée avec succès.</span>
                    <button class="admin-alert-close admin-ml-auto"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>

            <!-- Liste des médecins -->
            <div class="admin-content<?php echo !isset($_GET['success']) ? '' : ' admin-mt-0'; ?>">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Médecins en attente de vérification</h2>
                        <p class="admin-card-subtitle">Vérifiez les informations et les diplômes des médecins</p>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Médecin</th>
                                        <th>Spécialité</th>
                                        <th>Expérience</th>
                                        <th>Hôpital</th>
                                        <th>Diplôme</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($medecins as $medecin): ?>
                                    <tr>
                                        <td>
                                            <div class="admin-flex admin-items-center admin-gap-3">
                                                <div class="admin-avatar admin-avatar-sm">
                                                    <i class="fas fa-user-md"></i>
                                                </div>
                                                <div>
                                                    <div class="admin-font-semibold"><?php echo htmlspecialchars($medecin['nom'] . ' ' . $medecin['prenom']); ?></div>
                                                    <div class="admin-text-sm admin-text-muted"><?php echo htmlspecialchars($medecin['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($medecin['specialite'] ?? 'Non spécifiée'); ?></td>
                                        <td><?php echo htmlspecialchars($medecin['annees_experience'] ?? 'Non spécifiée'); ?> ans</td>
                                        <td><?php echo htmlspecialchars($medecin['hopital_actuel'] ?? 'Non spécifié'); ?></td>
                                        <td>
                                            <?php if (isset($medecin['diplome'])): ?>
                                                <a href="../uploads/diplomes/<?php echo htmlspecialchars($medecin['diplome']); ?>" target="_blank" class="admin-btn admin-btn-sm admin-btn-outline">
                                                    <i class="fas fa-file-pdf"></i>
                                                    <span>Voir</span>
                                                </a>
                                            <?php else: ?>
                                                <span class="admin-badge admin-badge-danger">Non fourni</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="admin-flex admin-gap-2">
                                                <button onclick="showVerificationModal(<?php echo $medecin['id']; ?>, 'verify')" class="admin-btn admin-btn-sm admin-btn-success">
                                                    <i class="fas fa-check"></i>
                                                    <span>Vérifier</span>
                                                </button>
                                                <button onclick="showVerificationModal(<?php echo $medecin['id']; ?>, 'reject')" class="admin-btn admin-btn-sm admin-btn-danger">
                                                    <i class="fas fa-times"></i>
                                                    <span>Rejeter</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de vérification -->
    <div id="verificationModal" class="admin-modal" style="display: none;">
        <div class="admin-modal-overlay"></div>
        <div class="admin-modal-container">
            <div class="admin-modal-header">
                <h3 class="admin-modal-title" id="modal-title">Confirmation de vérification</h3>
                <button type="button" onclick="hideVerificationModal()" class="admin-modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="verificationForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="id_medecin" id="medecinId">
                <input type="hidden" name="action" id="actionType">
                <div class="admin-modal-body">
                    <p class="admin-mb-4" id="modalDescription">
                        Êtes-vous sûr de vouloir <span id="actionText" class="admin-font-semibold"></span> ce médecin ?
                    </p>
                    <div class="admin-form-group">
                        <label for="commentaire" class="admin-form-label">Commentaire (optionnel)</label>
                        <textarea name="commentaire" id="commentaire" rows="3" class="admin-form-control"></textarea>
                    </div>
                </div>
                <div class="admin-modal-footer">
                    <button type="button" onclick="hideVerificationModal()" class="admin-btn admin-btn-outline">
                        <i class="fas fa-times"></i>
                        <span>Annuler</span>
                    </button>
                    <button type="submit" class="admin-btn admin-btn-primary" id="confirmButton">
                        <i class="fas fa-check"></i>
                        <span>Confirmer</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showVerificationModal(medecinId, action) {
            document.getElementById('medecinId').value = medecinId;
            document.getElementById('actionType').value = action;
            document.getElementById('actionText').textContent = action === 'verify' ? 'vérifier' : 'rejeter';
            
            // Changer la couleur du bouton de confirmation selon l'action
            const confirmButton = document.getElementById('confirmButton');
            if (action === 'verify') {
                confirmButton.className = 'admin-btn admin-btn-success';
                confirmButton.innerHTML = '<i class="fas fa-check"></i><span>Confirmer</span>';
            } else {
                confirmButton.className = 'admin-btn admin-btn-danger';
                confirmButton.innerHTML = '<i class="fas fa-times"></i><span>Confirmer</span>';
            }
            
            document.getElementById('verificationModal').style.display = 'block';
        }

        function hideVerificationModal() {
            document.getElementById('verificationModal').style.display = 'none';
        }
        
        // Fermer les alertes
        document.addEventListener('DOMContentLoaded', function() {
            const closeButtons = document.querySelectorAll('.admin-alert-close');
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    this.closest('.admin-alert').style.display = 'none';
                });
            });
            
            // Activer le mode sombre
            const darkModeToggle = document.getElementById('dark-mode-toggle');
            if (darkModeToggle) {
                darkModeToggle.addEventListener('change', function() {
                    document.body.classList.toggle('admin-dark-mode', this.checked);
                    localStorage.setItem('admin-dark-mode', this.checked ? 'enabled' : 'disabled');
                });
                
                // Vérifier le mode sombre enregistré
                const darkModeStatus = localStorage.getItem('admin-dark-mode');
                if (darkModeStatus === 'enabled') {
                    darkModeToggle.checked = true;
                    document.body.classList.add('admin-dark-mode');
                }
            }
        });
    </script>
    <script src="../../assets/js/admin.js"></script>
</body>
</html>