<?php
require_once '../../includes/session.php';
require_once '../../includes/security.php';
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../models/Medecin.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
requireLogin();
requireRole('admin');

$database = new Database();
$db = $database->getConnection();
$medecin = new Medecin($db);

// Traitement des actions de vérification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Erreur de sécurité : token CSRF invalide";
    } else {
        if (isset($_POST['action']) && isset($_POST['medecin_id'])) {
            $medecin->id = $_POST['medecin_id'];
            
            if ($_POST['action'] === 'verify') {
                if ($medecin->updateVerificationStatus('verified')) {
                    // Envoyer un email de confirmation
                    $medecin->sendVerificationConfirmationEmail();
                    $success = "Le médecin a été vérifié avec succès.";
                } else {
                    $error = "Une erreur s'est produite lors de la vérification.";
                }
            } elseif ($_POST['action'] === 'reject') {
                if ($medecin->updateVerificationStatus('rejected')) {
                    // Envoyer un email de rejet
                    $medecin->sendRejectionEmail();
                    $success = "Le médecin a été rejeté.";
                } else {
                    $error = "Une erreur s'est produite lors du rejet.";
                }
            }
        }
    }
}

// Récupérer la liste des médecins en attente de vérification
$query = "SELECT m.*, s.nomspecialite 
          FROM medecin m 
          LEFT JOIN specialite s ON m.idspecialite = s.id 
          WHERE m.verification_status = 'pending' 
          ORDER BY m.id DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$pending_doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <h1 class="admin-header-title">Vérification des Médecins</h1>
                <div class="admin-header-actions">
                    <div class="admin-header-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="search" placeholder="Rechercher un médecin..." class="admin-form-control">
                    </div>
                    <div class="admin-flex admin-items-center admin-gap-4">
                        <a href="doctors.php" class="admin-btn admin-btn-primary">
                            <i class="fas fa-arrow-left"></i>
                            <span>Retour à la liste</span>
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
            
                <!-- Liste des médecins en attente -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Médecins en attente de vérification</h2>
                        <div class="admin-flex admin-gap-2">
                            <span class="admin-badge admin-badge-warning">
                                <?php echo count($pending_doctors); ?> en attente
                            </span>
                        </div>
                    </div>
                    <div class="admin-card-body">
                        <?php if (empty($pending_doctors)): ?>
                            <div class="admin-alert admin-alert-success admin-text-center">
                                <i class="fas fa-check-circle"></i>
                                <span>Aucun médecin en attente de vérification.</span>
                            </div>
                        <?php else: ?>
                            <div class="admin-table-container">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Médecin</th>
                                            <th>Spécialité</th>
                                            <th>Numéro RPPS</th>
                                            <th>Contact</th>
                                            <th>Date d'inscription</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_doctors as $doctor): ?>
                                            <tr class="doctor-row">
                                                <td>
                                                    <div class="admin-flex admin-items-center admin-gap-2">
                                                        <div class="admin-stat-icon warning" style="width: 2.5rem; height: 2.5rem;">
                                                            <i class="fas fa-user-md"></i>
                                                        </div>
                                                        <div>
                                                            <div class="admin-font-bold">
                                                                <?php echo htmlspecialchars($doctor['prenom'] . ' ' . $doctor['nom']); ?>
                                                            </div>
                                                            <div class="admin-text-sm" style="color: var(--text-muted);">
                                                                Né(e) le <?php echo date('d/m/Y', strtotime($doctor['datenais'] ?? 'now')); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span><?php echo htmlspecialchars($doctor['nomspecialite'] ?? 'Non spécifié'); ?></span>
                                                </td>
                                                <td>
                                                    <span class="admin-badge admin-badge-primary">
                                                        <?php echo htmlspecialchars($doctor['num'] ?? 'Non spécifié'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div>
                                                        <div class="admin-flex admin-items-center admin-gap-2">
                                                            <i class="fas fa-envelope"></i>
                                                            <span><?php echo htmlspecialchars($doctor['email']); ?></span>
                                                        </div>
                                                        <?php if (!empty($doctor['contact'])): ?>
                                                        <div class="admin-flex admin-items-center admin-gap-2 admin-text-sm" style="color: var(--text-muted);">
                                                            <i class="fas fa-phone"></i>
                                                            <span><?php echo htmlspecialchars($doctor['contact']); ?></span>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span><?php echo !empty($doctor['created_at']) ? date('d/m/Y', strtotime($doctor['created_at'])) : date('d/m/Y'); ?></span>
                                                </td>
                                                <td>
                                                    <div class="admin-flex admin-gap-2">
                                                        <a href="doctor_details.php?id=<?php echo $doctor['id']; ?>" class="admin-btn admin-btn-sm admin-btn-info" data-tooltip="Voir les détails">
                                                            <i class="fas fa-eye"></i>
                                                            <span>Détails</span>
                                                        </a>
                                                        <form method="POST" class="admin-flex admin-gap-2">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                            <input type="hidden" name="medecin_id" value="<?php echo $doctor['id']; ?>">
                                                            <button type="submit" name="action" value="verify" class="admin-btn admin-btn-sm admin-btn-success" data-tooltip="Vérifier ce médecin">
                                                                <i class="fas fa-check"></i>
                                                                <span>Valider</span>
                                                            </button>
                                                            <button type="submit" name="action" value="reject" class="admin-btn admin-btn-sm admin-btn-danger" data-confirm="Êtes-vous sûr de vouloir rejeter ce médecin ?" data-tooltip="Rejeter ce médecin">
                                                                <i class="fas fa-times"></i>
                                                                <span>Rejeter</span>
                                                            </button>
                                                        </form>
                                                    </div>
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

    <!-- Modal de détails du médecin -->
    <div id="doctorDetailsModal" class="admin-modal" style="display: none;">
        <div class="admin-modal-overlay"></div>
        <div class="admin-modal-container" style="max-width: 800px;">
            <div class="admin-modal-header">
                <h3 class="admin-modal-title">Détails du médecin</h3>
                <button type="button" onclick="hideDoctorDetailsModal()" class="admin-modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="admin-modal-body" id="doctorDetailsContent">
                <!-- Le contenu sera chargé dynamiquement -->
                <div class="admin-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>Chargement des détails...</span>
                </div>
            </div>
            <div class="admin-modal-footer">
                <div class="admin-flex admin-justify-between admin-w-full">
                    <button type="button" onclick="hideDoctorDetailsModal()" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-times"></i>
                        <span>Fermer</span>
                    </button>
                    <div class="admin-flex admin-gap-2">
                        <form method="POST" id="actionFormInModal">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="medecin_id" id="modalMedecinId" value="">
                            <button type="submit" name="action" value="verify" class="admin-btn admin-btn-success">
                                <i class="fas fa-check"></i>
                                <span>Valider ce médecin</span>
                            </button>
                            <button type="submit" name="action" value="reject" class="admin-btn admin-btn-danger">
                                <i class="fas fa-times"></i>
                                <span>Rejeter ce médecin</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script pour gérer le modal de détails -->
    <script>
        // Fonction pour afficher le modal avec les détails du médecin
        function showDoctorDetails(doctorId) {
            console.log('Fonction showDoctorDetails appelée avec ID:', doctorId);
            
            // Mettre à jour l'ID du médecin dans le formulaire du modal
            document.getElementById('modalMedecinId').value = doctorId;
            console.log('ID mis à jour dans le formulaire modal');
            
            // Afficher le modal d'abord pour montrer le chargement
            document.getElementById('doctorDetailsModal').style.display = 'block';
            document.body.classList.add('admin-modal-open');
            console.log('Modal affiché');
            
            // Afficher l'indicateur de chargement
            document.getElementById('doctorDetailsContent').innerHTML = '
                <div class="admin-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>Chargement des détails...</span>
                </div>';
            
            // Récupérer les détails du médecin via AJAX
            const xhr = new XMLHttpRequest();
            
            // Utiliser le chemin relatif correct
            const url = 'get_doctor_details.php?id=' + doctorId;
            console.log('URL de la requête AJAX:', url);
            
            xhr.open('GET', url, true);
            
            xhr.onload = function() {
                console.log('Réponse reçue, statut:', this.status);
                if (this.status === 200) {
                    console.log('Réponse 200 OK');
                    document.getElementById('doctorDetailsContent').innerHTML = this.responseText;
                } else {
                    console.error('Erreur HTTP:', this.status, this.statusText);
                    document.getElementById('doctorDetailsContent').innerHTML = 
                        '<div class="admin-alert admin-alert-danger">Erreur lors du chargement des détails. Statut: ' + this.status + '</div>';
                }
            };
            
            xhr.onerror = function(e) {
                console.error('Erreur de connexion AJAX:', e);
                document.getElementById('doctorDetailsContent').innerHTML = 
                    '<div class="admin-alert admin-alert-danger">Erreur de connexion au serveur.</div>';
            };
            
            console.log('Envoi de la requête AJAX...');
            xhr.send();
        }
        
        // Fonction pour masquer le modal
        function hideDoctorDetailsModal() {
            document.getElementById('doctorDetailsModal').style.display = 'none';
            document.body.classList.remove('admin-modal-open');
        }
        
        // Fermer le modal lorsqu'on clique sur l'overlay
        document.querySelector('#doctorDetailsModal .admin-modal-overlay').addEventListener('click', hideDoctorDetailsModal);
        
        // Fermer les alertes lorsqu'on clique sur le bouton de fermeture
        document.querySelectorAll('.admin-alert-close').forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.admin-alert').remove();
            });
        });
    </script>

    <script src="../../assets/js/admin.js"></script>
</body>
</html>