<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

// Vérifier si l'utilisateur est connecté
requireLogin();

// Vérifier si l'utilisateur a le rôle requis
requireRole('admin');

// Accès aux informations de l'utilisateur connecté
$user_id = $_SESSION['user_id'];
$nom = $_SESSION['nom'];
$prenom = $_SESSION['prenom'];

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

// Récupérer les statistiques
// Total des utilisateurs
$query = "SELECT 
    (SELECT COUNT(*) FROM patient) + 
    (SELECT COUNT(*) FROM medecin) + 
    (SELECT COUNT(*) FROM admin) as total_users";
$stmt = $db->query($query);
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

// Nombre de médecins
$query = "SELECT COUNT(*) as total FROM medecin WHERE verification_status = 'verified'";
$stmt = $db->query($query);
$total_doctors = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Nombre de patients
$query = "SELECT COUNT(*) as total FROM patient";
$stmt = $db->query($query);
$total_patients = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Nombre de rendez-vous
$query = "SELECT COUNT(*) as total FROM rendezvous";
$stmt = $db->query($query);
$total_appointments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Récupérer les dernières activités
$query = "SELECT 
    'medecin' as type,
    CONCAT(prenom, ' ', nom) as name,
    'Nouveau médecin inscrit' as action,
    created_at as date
    FROM medecin
    WHERE verification_status = 'pending'
    UNION ALL
    SELECT 
    'patient' as type,
    CONCAT(prenom, ' ', nom) as name,
    'Nouveau patient inscrit' as action,
    created_at as date
    FROM patient
    ORDER BY date DESC
    LIMIT 5";
$stmt = $db->query($query);
$recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fonction pour formater la date
function formatDate($date) {
    $now = new DateTime();
    $date = new DateTime($date);
    $diff = $now->diff($date);
    
    if ($diff->d == 0) {
        if ($diff->h == 0) {
            return "Il y a " . $diff->i . " minutes";
        }
        return "Il y a " . $diff->h . " heures";
    }
    return "Il y a " . $diff->d . " jours";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Administrateur | MedApp</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <a href="dashboard.php" class="admin-sidebar-nav-item active">
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
                <h1 class="admin-header-title">Tableau de bord</h1>
                <div class="admin-header-actions">
                    <div class="admin-header-search">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Rechercher..." class="admin-form-control">
                    </div>
                    <div class="admin-flex admin-items-center admin-gap-4">
                        <label class="admin-flex admin-items-center admin-gap-2">
                            <input type="checkbox" id="dark-mode-toggle" class="admin-form-control" style="width: auto;">
                            <span>Mode sombre</span>
                        </label>
                        <a href="../logout.php" class="admin-btn admin-btn-danger admin-btn-sm">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Déconnexion</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Dashboard Content -->
            <div class="admin-content">
                <!-- Stats Cards -->
                <div class="admin-stats-grid">
                    <div class="admin-stat-card">
                        <div class="admin-stat-icon primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="admin-stat-info">
                            <div class="admin-stat-value"><?php echo number_format($total_users); ?></div>
                            <div class="admin-stat-label">Total Utilisateurs</div>
                        </div>
                    </div>
                    <div class="admin-stat-card">
                        <div class="admin-stat-icon success">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div class="admin-stat-info">
                            <div class="admin-stat-value"><?php echo number_format($total_doctors); ?></div>
                            <div class="admin-stat-label">Médecins</div>
                        </div>
                    </div>
                    <div class="admin-stat-card">
                        <div class="admin-stat-icon warning">
                            <i class="fas fa-procedures"></i>
                        </div>
                        <div class="admin-stat-info">
                            <div class="admin-stat-value"><?php echo number_format($total_patients); ?></div>
                            <div class="admin-stat-label">Patients</div>
                        </div>
                    </div>
                    <div class="admin-stat-card">
                        <div class="admin-stat-icon danger">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="admin-stat-info">
                            <div class="admin-stat-value"><?php echo number_format($total_appointments); ?></div>
                            <div class="admin-stat-label">Rendez-vous</div>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                    <!-- Quick Actions Card -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h2 class="admin-card-title">Actions Rapides</h2>
                        </div>
                        <div class="admin-card-body">
                            <div class="space-y-3">
                                <a href="verify_doctors.php" class="admin-btn admin-btn-success admin-w-full admin-mb-4">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Vérifier les médecins</span>
                                </a>
                                <a href="doctors.php" class="admin-btn admin-btn-primary admin-w-full admin-mb-4">
                                    <i class="fas fa-user-md"></i>
                                    <span>Gérer les médecins</span>
                                </a>
                                <a href="patients.php" class="admin-btn admin-btn-warning admin-w-full admin-mb-4">
                                    <i class="fas fa-procedures"></i>
                                    <span>Gérer les patients</span>
                                </a>
                                <a href="settings.php" class="admin-btn admin-btn-outline admin-w-full">
                                    <i class="fas fa-cog"></i>
                                    <span>Paramètres</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activities Card -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h2 class="admin-card-title">Dernières Activités</h2>
                            <a href="#" class="admin-btn admin-btn-sm admin-btn-outline">Voir tout</a>
                        </div>
                        <div class="admin-card-body">
                            <div class="space-y-4">
                                <?php if (empty($recent_activities)): ?>
                                    <div class="admin-alert admin-alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        <span>Aucune activité récente</span>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recent_activities as $activity): ?>
                                        <div class="admin-flex admin-gap-4">
                                            <div class="admin-stat-icon <?php echo ($activity['type'] == 'medecin') ? 'primary' : 'warning'; ?>" style="width: 2.5rem; height: 2.5rem;">
                                                <?php if ($activity['type'] == 'medecin'): ?>
                                                    <i class="fas fa-user-md"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-user"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <p class="admin-font-bold"><?php echo htmlspecialchars($activity['action']); ?></p>
                                                <p class="admin-text-sm">
                                                    <?php echo htmlspecialchars($activity['name']); ?>
                                                </p>
                                                <p class="admin-text-sm" style="color: var(--text-muted);">
                                                    <?php echo formatDate($activity['date']); ?>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Système</h2>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Version</span>
                                <span class="text-gray-800">1.0.0</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Statut</span>
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-sm">En ligne</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Dernière mise à jour</span>
                                <span class="text-gray-800"><?php echo date('Y-m-d'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Doctors & Patients -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <!-- Pending Doctors Verification -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h2 class="admin-card-title">Médecins en attente de vérification</h2>
                            <a href="verify_doctors.php" class="admin-btn admin-btn-sm admin-btn-primary">Vérifier</a>
                        </div>
                        <div class="admin-table-container">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Médecin</th>
                                        <th>Spécialité</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Récupérer les médecins en attente de vérification
                                    $query = "SELECT m.*, s.nomspecialite 
                                              FROM medecin m 
                                              LEFT JOIN specialite s ON m.idspecialite = s.id 
                                              WHERE m.verification_status = 'pending' 
                                              ORDER BY m.created_at DESC 
                                              LIMIT 5";
                                    $stmt = $db->query($query);
                                    $pending_doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    if (empty($pending_doctors)): ?>
                                        <tr>
                                            <td colspan="4" class="admin-text-center">Aucun médecin en attente</td>
                                        </tr>
                                    <?php else: 
                                        foreach ($pending_doctors as $doctor): ?>
                                            <tr>
                                                <td>
                                                    <div class="admin-flex admin-items-center admin-gap-2">
                                                        <i class="fas fa-user-md"></i>
                                                        <span><?php echo htmlspecialchars($doctor['prenom'] . ' ' . $doctor['nom']); ?></span>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($doctor['nomspecialite'] ?? 'Non spécifié'); ?></td>
                                                <td><?php echo formatDate($doctor['created_at']); ?></td>
                                                <td>
                                                    <a href="verify_doctors.php?id=<?php echo $doctor['id']; ?>" class="admin-btn admin-btn-sm admin-btn-success">Vérifier</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; 
                                    endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Recent Patients -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h2 class="admin-card-title">Patients récemment inscrits</h2>
                            <a href="patients.php" class="admin-btn admin-btn-sm admin-btn-primary">Voir tous</a>
                        </div>
                        <div class="admin-table-container">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Email</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Récupérer les patients récemment inscrits
                                    $query = "SELECT * FROM patient ORDER BY created_at DESC LIMIT 5";
                                    $stmt = $db->query($query);
                                    $recent_patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    if (empty($recent_patients)): ?>
                                        <tr>
                                            <td colspan="3" class="admin-text-center">Aucun patient récent</td>
                                        </tr>
                                    <?php else: 
                                        foreach ($recent_patients as $patient): ?>
                                            <tr>
                                                <td>
                                                    <div class="admin-flex admin-items-center admin-gap-2">
                                                        <i class="fas fa-user"></i>
                                                        <span><?php echo htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']); ?></span>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                                <td><?php echo formatDate($patient['created_at']); ?></td>
                                            </tr>
                                        <?php endforeach; 
                                    endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Données pour les graphiques
    const usersChartData = {
        labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil'],
        data: [15, 20, 25, 30, 35, 40, 45]
    };

    const appointmentsChartData = {
        labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
        data: [10, 15, 20, 25, 15, 5, 2]
    };
    </script>
    <script src="../../assets/js/admin.js"></script>
</body>
</html>