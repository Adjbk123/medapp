<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

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

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                // Mise à jour du profil administrateur
                $query = "UPDATE admin SET nom = ?, prenom = ?, email = ?, contact = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                if ($stmt->execute([
                    $_POST['nom'],
                    $_POST['prenom'],
                    $_POST['email'],
                    $_POST['contact'],
                    $user_id
                ])) {
                    $success = "Profil mis à jour avec succès.";
                    $_SESSION['nom'] = $_POST['nom'];
                    $_SESSION['prenom'] = $_POST['prenom'];
                } else {
                    $error = "Erreur lors de la mise à jour du profil.";
                }
                break;

            case 'change_password':
                // Vérification de l'ancien mot de passe
                $query = "SELECT password FROM admin WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$user_id]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);

                if (password_verify($_POST['old_password'], $admin['password'])) {
                    if ($_POST['new_password'] === $_POST['confirm_password']) {
                        $query = "UPDATE admin SET password = ? WHERE id = ?";
                        $stmt = $db->prepare($query);
                        if ($stmt->execute([password_hash($_POST['new_password'], PASSWORD_DEFAULT), $user_id])) {
                            $success = "Mot de passe modifié avec succès.";
                        } else {
                            $error = "Erreur lors de la modification du mot de passe.";
                        }
                    } else {
                        $error = "Les nouveaux mots de passe ne correspondent pas.";
                    }
                } else {
                    $error = "Ancien mot de passe incorrect.";
                }
                break;
                
            case 'update_theme':
                // Mise à jour du thème
                $_SESSION['theme'] = $_POST['theme'];
                $success = "Thème mis à jour avec succès.";
                break;
        }
    }
}

// Récupérer les informations de l'administrateur
$query = "SELECT * FROM admin WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer les statistiques du système
$stats = [
    'Médecins' => $db->query("SELECT COUNT(*) FROM medecin")->fetchColumn(),
    'Patients' => $db->query("SELECT COUNT(*) FROM patient")->fetchColumn(),
    'Rendez-vous' => $db->query("SELECT COUNT(*) FROM rendezvous")->fetchColumn(),
    'Consultations' => $db->query("SELECT COUNT(*) FROM consultation")->fetchColumn()
];

// Définir le thème actif
$theme = $_SESSION['theme'] ?? 'light';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres | MedApp</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="<?php echo $theme === 'dark' ? 'admin-dark-mode' : ''; ?>">
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="admin-sidebar-header">
                <a href="dashboard.php" class="admin-sidebar-logo">
                    <i class="fas fa-heartbeat"></i>
                    <span>MedApp Admin</span>
                </a>
                <button class="admin-sidebar-toggle" id="sidebarToggle">
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
                <a href="settings.php" class="admin-sidebar-nav-item active">
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
                <h1 class="admin-header-title">Paramètres</h1>
                <div class="admin-header-actions">
                    <form method="POST" class="admin-flex admin-items-center admin-gap-4" id="themeForm">
                        <input type="hidden" name="action" value="update_theme">
                        <input type="hidden" name="theme" id="themeInput" value="<?php echo $theme; ?>">
                        <label class="admin-flex admin-items-center admin-gap-2 admin-cursor-pointer">
                            <input type="checkbox" id="dark-mode-toggle" class="admin-form-control" style="width: auto;" <?php echo $theme === 'dark' ? 'checked' : ''; ?>>
                            <span>Mode sombre</span>
                        </label>
                    </form>
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

                <div class="admin-grid admin-grid-cols-1 admin-grid-cols-md-2 admin-gap-6">
                    <!-- Profil -->
                    <div class="admin-card admin-card-bordered admin-card-shadow">
                        <div class="admin-card-header admin-border-bottom">
                            <h2 class="admin-card-title"><i class="fas fa-user-circle admin-mr-2"></i>Profil Administrateur</h2>
                        </div>
                        <div class="admin-card-body">
                            <div class="admin-flex admin-flex-col admin-items-center admin-mb-4 admin-p-3 admin-border admin-rounded admin-shadow-sm">
                                <div class="admin-avatar admin-avatar-lg">
                                    <i class="fas fa-user"></i>
                                </div>
                                <h3 class="admin-mt-2 admin-font-semibold"><?php echo htmlspecialchars($prenom . ' ' . $nom); ?></h3>
                                <p class="admin-text-muted"><?php echo htmlspecialchars($admin['email']); ?></p>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="admin-form-group admin-mb-3">
                                    <label class="admin-form-label">Nom</label>
                                    <div class="admin-input-group admin-border admin-rounded admin-shadow-sm">
                                        <span class="admin-input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" name="nom" value="<?php echo htmlspecialchars($admin['nom']); ?>" 
                                               class="admin-form-control">
                                    </div>
                                </div>
                                
                                <div class="admin-form-group admin-mb-3">
                                    <label class="admin-form-label">Prénom</label>
                                    <div class="admin-input-group admin-border admin-rounded admin-shadow-sm">
                                        <span class="admin-input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" name="prenom" value="<?php echo htmlspecialchars($admin['prenom']); ?>" 
                                               class="admin-form-control">
                                    </div>
                                </div>
                                
                                <div class="admin-form-group admin-mb-3">
                                    <label class="admin-form-label">Email</label>
                                    <div class="admin-input-group admin-border admin-rounded admin-shadow-sm">
                                        <span class="admin-input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" 
                                               class="admin-form-control">
                                    </div>
                                </div>
                                
                                <div class="admin-form-group admin-mb-3">
                                    <label class="admin-form-label">Contact</label>
                                    <div class="admin-input-group admin-border admin-rounded admin-shadow-sm">
                                        <span class="admin-input-group-text"><i class="fas fa-phone"></i></span>
                                        <input type="text" name="contact" value="<?php echo htmlspecialchars($admin['contact']); ?>" 
                                               class="admin-form-control">
                                    </div>
                                </div>
                                
                                <button type="submit" class="admin-btn admin-btn-primary admin-w-full admin-border admin-shadow-sm">
                                    <i class="fas fa-save"></i>
                                    <span>Mettre à jour le profil</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Mot de passe -->
                    <div class="admin-card admin-card-bordered admin-card-shadow">
                        <div class="admin-card-header admin-border-bottom">
                            <h2 class="admin-card-title"><i class="fas fa-key admin-mr-2"></i>Changer le mot de passe</h2>
                        </div>
                        <div class="admin-card-body">
                            <div class="admin-alert admin-alert-info admin-mb-4 admin-border admin-shadow-sm">
                                <i class="fas fa-info-circle"></i>
                                <span>Pour des raisons de sécurité, utilisez un mot de passe fort avec des lettres, chiffres et caractères spéciaux.</span>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="admin-form-group admin-mb-3">
                                    <label class="admin-form-label">Ancien mot de passe</label>
                                    <div class="admin-input-group admin-border admin-rounded admin-shadow-sm">
                                        <span class="admin-input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" name="old_password" required class="admin-form-control">
                                    </div>
                                </div>
                                
                                <div class="admin-form-group admin-mb-3">
                                    <label class="admin-form-label">Nouveau mot de passe</label>
                                    <div class="admin-input-group admin-border admin-rounded admin-shadow-sm">
                                        <span class="admin-input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" name="new_password" required class="admin-form-control">
                                    </div>
                                </div>
                                
                                <div class="admin-form-group admin-mb-3">
                                    <label class="admin-form-label">Confirmer le mot de passe</label>
                                    <div class="admin-input-group admin-border admin-rounded admin-shadow-sm">
                                        <span class="admin-input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" name="confirm_password" required class="admin-form-control">
                                    </div>
                                </div>
                                
                                <button type="submit" class="admin-btn admin-btn-warning admin-w-full admin-border admin-shadow-sm">
                                    <i class="fas fa-key"></i>
                                    <span>Changer le mot de passe</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Configuration de l'application -->
                    <div class="admin-card admin-card-bordered admin-card-shadow">
                        <div class="admin-card-header admin-border-bottom">
                            <h2 class="admin-card-title"><i class="fas fa-cogs admin-mr-2"></i>Configuration de l'application</h2>
                        </div>
                        <div class="admin-card-body">
                            <div class="admin-space-y-4">
                                <div class="admin-flex admin-justify-between admin-items-center admin-p-2 admin-border-bottom">
                                    <span class="admin-flex admin-items-center"><i class="fas fa-code-branch admin-mr-2"></i>Version</span>
                                    <span class="admin-badge admin-badge-primary admin-border">1.0.0</span>
                                </div>
                                <div class="admin-flex admin-justify-between admin-items-center admin-p-2 admin-border-bottom">
                                    <span class="admin-flex admin-items-center"><i class="fas fa-signal admin-mr-2"></i>Statut</span>
                                    <span class="admin-badge admin-badge-success admin-border">En ligne</span>
                                </div>
                                <div class="admin-flex admin-justify-between admin-items-center admin-p-2 admin-border-bottom">
                                    <span class="admin-flex admin-items-center"><i class="fas fa-calendar-alt admin-mr-2"></i>Dernière mise à jour</span>
                                    <span class="admin-badge admin-badge-info admin-border"><?php echo date('Y-m-d'); ?></span>
                                </div>
                                <div class="admin-flex admin-justify-between admin-items-center admin-p-2 admin-border-bottom">
                                    <span class="admin-flex admin-items-center"><i class="fas fa-server admin-mr-2"></i>Environnement</span>
                                    <span class="admin-badge admin-badge-warning admin-border">Production</span>
                                </div>
                                <div class="admin-flex admin-justify-between admin-items-center admin-p-2 admin-border-bottom">
                                    <span class="admin-flex admin-items-center"><i class="fas fa-database admin-mr-2"></i>Base de données</span>
                                    <span class="admin-badge admin-badge-success admin-border">Connectée</span>
                                </div>
                                <div class="admin-mt-4 admin-p-2">
                                    <button id="checkUpdatesBtn" class="admin-btn admin-btn-outline admin-w-full admin-border admin-shadow-sm">
                                        <i class="fas fa-sync"></i>
                                        <span>Vérifier les mises à jour</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistiques -->
                    <div class="admin-card admin-card-bordered admin-card-shadow">
                        <div class="admin-card-header admin-border-bottom">
                            <h2 class="admin-card-title"><i class="fas fa-chart-bar admin-mr-2"></i>Statistiques système</h2>
                            <div class="admin-card-tools">
                                <button id="refreshStatsBtn" class="admin-btn admin-btn-sm admin-btn-icon admin-btn-outline admin-border admin-shadow-sm">
                                    <i class="fas fa-sync"></i>
                                </button>
                            </div>
                        </div>
                        <div class="admin-card-body">
                            <div class="admin-grid admin-grid-cols-2 admin-gap-4 admin-mb-4">
                                <?php foreach ($stats as $label => $value): ?>
                                <div class="admin-stat-card admin-border admin-rounded admin-shadow admin-p-3">
                                    <div class="admin-stat-icon">
                                        <i class="<?php 
                                            echo match($label) {
                                                'Médecins' => 'fas fa-user-md',
                                                'Patients' => 'fas fa-procedures',
                                                'Rendez-vous' => 'fas fa-calendar-check',
                                                'Consultations' => 'fas fa-stethoscope',
                                                default => 'fas fa-chart-line'
                                            }; 
                                        ?>"></i>
                                    </div>
                                    <div class="admin-stat-content">
                                        <div class="admin-stat-title"><?php echo $label; ?></div>
                                        <div class="admin-stat-value"><?php echo number_format($value); ?></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="admin-chart-container admin-border admin-rounded admin-p-3 admin-mt-3 admin-shadow-sm">
                                <div class="admin-text-center admin-p-4">
                                    <i class="fas fa-chart-line admin-text-3xl admin-text-primary"></i>
                                    <p class="admin-mt-2">Graphique des statistiques</p>
                                </div>
                                <!-- Le graphique sera inséré ici par JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/admin.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion du mode sombre
        const darkModeToggle = document.getElementById('dark-mode-toggle');
        const themeForm = document.getElementById('themeForm');
        const themeInput = document.getElementById('themeInput');
        
        if (darkModeToggle) {
            darkModeToggle.addEventListener('change', function() {
                document.body.classList.toggle('admin-dark-mode', this.checked);
                themeInput.value = this.checked ? 'dark' : 'light';
                themeForm.submit();
            });
        }
        
        // Fermeture des alertes
        const closeButtons = document.querySelectorAll('.admin-alert-close');
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.admin-alert').style.display = 'none';
            });
        });
        
        // Bouton de vérification des mises à jour
        const checkUpdatesBtn = document.getElementById('checkUpdatesBtn');
        if (checkUpdatesBtn) {
            checkUpdatesBtn.addEventListener('click', function() {
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Vérification en cours...</span>';
                
                // Simuler une vérification
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-check"></i><span>Aucune mise à jour disponible</span>';
                    this.disabled = false;
                    
                    // Créer une alerte pour informer l'utilisateur
                    const alertContainer = document.querySelector('.admin-content');
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'admin-alert admin-alert-info';
                    alertDiv.innerHTML = `
                        <i class="fas fa-info-circle"></i>
                        <span>Votre application est à jour (version 1.0.0)</span>
                        <button class="admin-alert-close admin-ml-auto"><i class="fas fa-times"></i></button>
                    `;
                    
                    alertContainer.insertBefore(alertDiv, alertContainer.firstChild);
                    
                    // Ajouter l'événement de fermeture à la nouvelle alerte
                    alertDiv.querySelector('.admin-alert-close').addEventListener('click', function() {
                        alertDiv.style.display = 'none';
                    });
                    
                    // Restaurer le bouton après quelques secondes
                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-sync"></i><span>Vérifier les mises à jour</span>';
                    }, 3000);
                }, 2000);
            });
        }
        
        // Bouton de rafraîchissement des statistiques
        const refreshStatsBtn = document.getElementById('refreshStatsBtn');
        if (refreshStatsBtn) {
            refreshStatsBtn.addEventListener('click', function() {
                this.classList.add('admin-spin');
                
                // Simuler un rafraîchissement
                setTimeout(() => {
                    this.classList.remove('admin-spin');
                }, 1000);
            });
        }
    });
    </script>
</body>
</html>