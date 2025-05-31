<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
require_once '../../models/Patient.php';

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
$patient = new Patient($db);

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['patient_id'])) {
        $patient->id = $_POST['patient_id'];
        
        switch ($_POST['action']) {
            case 'delete':
                if ($patient->delete()) {
                    $success = "Le patient a été supprimé avec succès.";
                } else {
                    $error = "Une erreur s'est produite lors de la suppression.";
                }
                break;
        }
    }
}

// Récupérer la liste des patients
$query = "SELECT * FROM patient ORDER BY id DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Patients | MedApp</title>
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
                <a href="patients.php" class="admin-sidebar-nav-item active">
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
                <h1 class="admin-header-title">Gestion des Patients</h1>
                <div class="admin-header-actions">
                    <div class="admin-header-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="search" placeholder="Rechercher un patient..." class="admin-form-control">
                    </div>
                    <div class="admin-flex admin-items-center admin-gap-4">
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
                                <label class="admin-form-label">Genre</label>
                                <select id="gender-filter" class="admin-form-control admin-form-select">
                                    <option value="">Tous les genres</option>
                                    <option value="M">Masculin</option>
                                    <option value="F">Féminin</option>
                                </select>
                            </div>
                            <div class="admin-form-group">
                                <label class="admin-form-label">Tranche d'âge</label>
                                <select id="age-filter" class="admin-form-control admin-form-select">
                                    <option value="">Tous les âges</option>
                                    <option value="0-18">0-18 ans</option>
                                    <option value="19-30">19-30 ans</option>
                                    <option value="31-50">31-50 ans</option>
                                    <option value="51+">51+ ans</option>
                                </select>
                            </div>
                            <div class="admin-form-group">
                                <label class="admin-form-label">Actions</label>
                                <div class="admin-flex admin-gap-2">
                                    <button id="reset-filters" class="admin-btn admin-btn-outline">
                                        <i class="fas fa-redo"></i>
                                        <span>Réinitialiser</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Liste des patients -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Liste des patients</h2>
                        <div class="admin-flex admin-gap-2">
                            <span class="admin-badge admin-badge-primary">
                                Total: <?php echo count($patients); ?>
                            </span>
                        </div>
                    </div>
                    <div class="admin-table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Informations</th>
                                    <th>Contact</th>
                                    <th>Date d'inscription</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($patients)): ?>
                                    <tr>
                                        <td colspan="5" class="admin-text-center">Aucun patient trouvé</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($patients as $patient): ?>
                                        <tr class="patient-row" 
                                            data-gender="<?php echo htmlspecialchars($patient['sexe']); ?>"
                                            data-age="<?php echo calculateAge($patient['datenais']); ?>">
                                            <td>
                                                <div class="admin-flex admin-items-center admin-gap-2">
                                                    <div class="admin-stat-icon warning" style="width: 2.5rem; height: 2.5rem;">
                                                        <?php if ($patient['sexe'] === 'M'): ?>
                                                            <i class="fas fa-male"></i>
                                                        <?php else: ?>
                                                            <i class="fas fa-female"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <div class="admin-font-bold">
                                                            <?php echo htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']); ?>
                                                        </div>
                                                        <div class="admin-text-sm" style="color: var(--text-muted);">
                                                            <?php echo htmlspecialchars($patient['email']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="admin-flex admin-items-center admin-gap-2">
                                                        <span class="admin-badge <?php echo $patient['sexe'] === 'M' ? 'admin-badge-primary' : 'admin-badge-warning'; ?>">
                                                            <?php echo $patient['sexe'] === 'M' ? 'Masculin' : 'Féminin'; ?>
                                                        </span>
                                                        <span class="admin-badge admin-badge-success">
                                                            <?php echo calculateAge($patient['datenais']); ?> ans
                                                        </span>
                                                    </div>
                                                    <div class="admin-text-sm" style="color: var(--text-muted);">
                                                        Né(e) le <?php echo date('d/m/Y', strtotime($patient['datenais'])); ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <?php if (!empty($patient['contact'])): ?>
                                                    <div class="admin-flex admin-items-center admin-gap-2">
                                                        <i class="fas fa-phone"></i>
                                                        <span><?php echo htmlspecialchars($patient['contact']); ?></span>
                                                    </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($patient['adresse'])): ?>
                                                    <div class="admin-flex admin-items-center admin-gap-2 admin-text-sm" style="color: var(--text-muted);">
                                                        <i class="fas fa-map-marker-alt"></i>
                                                        <span><?php echo htmlspecialchars($patient['adresse']); ?></span>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span><?php echo date('d/m/Y', strtotime($patient['created_at'] ?? 'now')); ?></span>
                                            </td>
                                            <td>
                                                <div class="admin-flex admin-gap-2">
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="patient_id" value="<?php echo $patient['id']; ?>">
                                                        
                                                        <button type="submit" name="action" value="delete" 
                                                                class="admin-btn admin-btn-sm admin-btn-danger"
                                                                data-confirm="Êtes-vous sûr de vouloir supprimer ce patient ?" data-tooltip="Supprimer ce patient">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                    <a href="#" class="admin-btn admin-btn-sm admin-btn-primary" data-tooltip="Voir les détails">
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
            document.getElementById('gender-filter').value = '';
            document.getElementById('age-filter').value = '';
            filterTable(); // Cette fonction est définie dans admin.js
        });

        // Filtrage des patients
        function filterTable() {
            const searchTerm = document.getElementById('search').value.toLowerCase();
            const genderFilter = document.getElementById('gender-filter').value;
            const ageFilter = document.getElementById('age-filter').value;
            
            document.querySelectorAll('.patient-row').forEach(row => {
                const patientText = row.textContent.toLowerCase();
                const patientGender = row.getAttribute('data-gender');
                const patientAge = parseInt(row.getAttribute('data-age'));
                
                const matchesSearch = patientText.includes(searchTerm);
                const matchesGender = genderFilter === '' || patientGender === genderFilter;
                
                let matchesAge = true;
                if (ageFilter !== '') {
                    const [minAge, maxAge] = ageFilter.split('-');
                    if (maxAge === '+') {
                        matchesAge = patientAge >= parseInt(minAge);
                    } else {
                        matchesAge = patientAge >= parseInt(minAge) && patientAge <= parseInt(maxAge);
                    }
                }
                
                if (matchesSearch && matchesGender && matchesAge) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Initialiser les écouteurs d'événements
        document.getElementById('search').addEventListener('input', filterTable);
        document.getElementById('gender-filter').addEventListener('change', filterTable);
        document.getElementById('age-filter').addEventListener('change', filterTable);
    </script>
</body>
</html>

<?php
function calculateAge($birthdate) {
    $birth = new DateTime($birthdate);
    $today = new DateTime();
    $age = $today->diff($birth);
    return $age->y;
}
?> 