<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

requireLogin();
requireRole('admin');

$user_id = $_SESSION['user_id'];
$nom     = $_SESSION['nom'] ?? '';
$prenom  = $_SESSION['prenom'] ?? '';

$database = new Database();
$db = $database->getConnection();

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'update_profile':
            $stmt = $db->prepare("UPDATE admin SET nom=?, prenom=?, email=?, contact=? WHERE id=?");
            if ($stmt->execute([$_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['contact'], $user_id])) {
                $success = "Profil mis à jour avec succès.";
                $_SESSION['nom']    = $_POST['nom'];
                $_SESSION['prenom'] = $_POST['prenom'];
                $nom    = $_POST['nom'];
                $prenom = $_POST['prenom'];
            } else {
                $error = "Erreur lors de la mise à jour du profil.";
            }
            break;

        case 'change_password':
            $stmt = $db->prepare("SELECT password FROM admin WHERE id=?");
            $stmt->execute([$user_id]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($_POST['old_password'], $admin['password'])) {
                if ($_POST['new_password'] === $_POST['confirm_password']) {
                    $stmt = $db->prepare("UPDATE admin SET password=? WHERE id=?");
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
    }
}

$stmt = $db->prepare("SELECT * FROM admin WHERE id=?");
$stmt->execute([$user_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

$stats = [
    ['label' => 'Médecins',       'icon' => 'fa-user-md',        'color' => 'primary', 'value' => $db->query("SELECT COUNT(*) FROM medecin")->fetchColumn()],
    ['label' => 'Patients',       'icon' => 'fa-procedures',     'color' => 'success', 'value' => $db->query("SELECT COUNT(*) FROM patient")->fetchColumn()],
    ['label' => 'Rendez-vous',    'icon' => 'fa-calendar-check', 'color' => 'warning', 'value' => $db->query("SELECT COUNT(*) FROM rendezvous")->fetchColumn()],
    ['label' => 'Consultations',  'icon' => 'fa-stethoscope',    'color' => 'danger',  'value' => $db->query("SELECT COUNT(*) FROM consultation")->fetchColumn()],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres | MedApp Admin</title>
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
        </div>
        <div class="admin-sidebar-nav">
            <a href="dashboard.php" class="admin-sidebar-nav-item">
                <i class="fas fa-home"></i><span>Tableau de bord</span>
            </a>
            <a href="doctors.php" class="admin-sidebar-nav-item">
                <i class="fas fa-user-md"></i><span>Médecins</span>
            </a>
            <a href="patients.php" class="admin-sidebar-nav-item">
                <i class="fas fa-procedures"></i><span>Patients</span>
            </a>
            <a href="verify_doctors.php" class="admin-sidebar-nav-item">
                <i class="fas fa-check-circle"></i><span>Vérification</span>
            </a>
            <a href="settings.php" class="admin-sidebar-nav-item active">
                <i class="fas fa-cog"></i><span>Paramètres</span>
            </a>
        </div>
        <div class="admin-sidebar-footer">
            <div class="admin-sidebar-user">
                <div class="admin-sidebar-user-avatar"><i class="fas fa-user"></i></div>
                <div class="admin-sidebar-user-info">
                    <div class="admin-sidebar-user-name"><?php echo htmlspecialchars($prenom . ' ' . $nom); ?></div>
                    <div class="admin-sidebar-user-role">Administrateur</div>
                </div>
            </div>
            <a href="../logout.php" class="admin-sidebar-nav-item">
                <i class="fas fa-sign-out-alt"></i><span>Déconnexion</span>
            </a>
        </div>
    </div>

    <!-- Main -->
    <div class="admin-main">
        <div class="admin-header">
            <h1 class="admin-header-title">Paramètres</h1>
        </div>

        <div class="admin-content">

            <?php if ($success): ?>
            <div class="admin-alert admin-alert-success" style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                <button onclick="this.parentElement.remove()" style="margin-left:auto;background:none;border:none;cursor:pointer;"><i class="fas fa-times"></i></button>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="admin-alert admin-alert-danger" style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                <button onclick="this.parentElement.remove()" style="margin-left:auto;background:none;border:none;cursor:pointer;"><i class="fas fa-times"></i></button>
            </div>
            <?php endif; ?>

            <!-- Stats rapides -->
            <div class="admin-stats-grid" style="margin-bottom:28px;">
                <?php foreach ($stats as $s): ?>
                <div class="admin-stat-card">
                    <div class="admin-stat-icon <?php echo $s['color']; ?>">
                        <i class="fas <?php echo $s['icon']; ?>"></i>
                    </div>
                    <div class="admin-stat-info">
                        <div class="admin-stat-value"><?php echo number_format($s['value']); ?></div>
                        <div class="admin-stat-label"><?php echo $s['label']; ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Deux colonnes -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">

                <!-- Profil -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title"><i class="fas fa-user-circle" style="margin-right:8px;"></i>Profil Administrateur</h2>
                    </div>
                    <div class="admin-card-body">
                        <!-- Avatar -->
                        <div style="display:flex;align-items:center;gap:16px;padding:16px;background:var(--bg-light);border-radius:var(--radius);margin-bottom:20px;">
                            <div style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,var(--primary-color),#818cf8);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fas fa-user" style="color:white;font-size:22px;"></i>
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:15px;"><?php echo htmlspecialchars($prenom . ' ' . $nom); ?></div>
                                <div style="color:var(--text-muted);font-size:13px;"><?php echo htmlspecialchars($admin['email']); ?></div>
                            </div>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            <div style="margin-bottom:14px;">
                                <label style="display:block;font-size:13px;font-weight:500;margin-bottom:6px;color:var(--text-secondary);"><i class="fas fa-user" style="margin-right:6px;color:var(--primary-color);"></i>Nom</label>
                                <input type="text" name="nom" value="<?php echo htmlspecialchars($admin['nom']); ?>" class="admin-form-control" required>
                            </div>
                            <div style="margin-bottom:14px;">
                                <label style="display:block;font-size:13px;font-weight:500;margin-bottom:6px;color:var(--text-secondary);"><i class="fas fa-user" style="margin-right:6px;color:var(--primary-color);"></i>Prénom</label>
                                <input type="text" name="prenom" value="<?php echo htmlspecialchars($admin['prenom']); ?>" class="admin-form-control" required>
                            </div>
                            <div style="margin-bottom:14px;">
                                <label style="display:block;font-size:13px;font-weight:500;margin-bottom:6px;color:var(--text-secondary);"><i class="fas fa-envelope" style="margin-right:6px;color:var(--primary-color);"></i>Email</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" class="admin-form-control" required>
                            </div>
                            <div style="margin-bottom:20px;">
                                <label style="display:block;font-size:13px;font-weight:500;margin-bottom:6px;color:var(--text-secondary);"><i class="fas fa-phone" style="margin-right:6px;color:var(--primary-color);"></i>Contact</label>
                                <input type="text" name="contact" value="<?php echo htmlspecialchars($admin['contact']); ?>" class="admin-form-control">
                            </div>
                            <button type="submit" class="admin-btn admin-btn-primary" style="width:100%;">
                                <i class="fas fa-save"></i> Enregistrer les modifications
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Mot de passe + Infos système -->
                <div style="display:flex;flex-direction:column;gap:24px;">

                    <!-- Mot de passe -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h2 class="admin-card-title"><i class="fas fa-key" style="margin-right:8px;"></i>Changer le mot de passe</h2>
                        </div>
                        <div class="admin-card-body">
                            <div style="display:flex;align-items:center;gap:10px;padding:12px;background:#eff6ff;border-radius:var(--radius);margin-bottom:16px;font-size:13px;color:#3b82f6;">
                                <i class="fas fa-info-circle"></i>
                                Utilisez un mot de passe fort (lettres, chiffres, caractères spéciaux).
                            </div>
                            <form method="POST">
                                <input type="hidden" name="action" value="change_password">
                                <div style="margin-bottom:14px;">
                                    <label style="display:block;font-size:13px;font-weight:500;margin-bottom:6px;color:var(--text-secondary);"><i class="fas fa-lock" style="margin-right:6px;color:var(--primary-color);"></i>Ancien mot de passe</label>
                                    <input type="password" name="old_password" class="admin-form-control" required>
                                </div>
                                <div style="margin-bottom:14px;">
                                    <label style="display:block;font-size:13px;font-weight:500;margin-bottom:6px;color:var(--text-secondary);"><i class="fas fa-lock" style="margin-right:6px;color:var(--primary-color);"></i>Nouveau mot de passe</label>
                                    <input type="password" name="new_password" class="admin-form-control" required>
                                </div>
                                <div style="margin-bottom:20px;">
                                    <label style="display:block;font-size:13px;font-weight:500;margin-bottom:6px;color:var(--text-secondary);"><i class="fas fa-lock" style="margin-right:6px;color:var(--primary-color);"></i>Confirmer le mot de passe</label>
                                    <input type="password" name="confirm_password" class="admin-form-control" required>
                                </div>
                                <button type="submit" class="admin-btn admin-btn-warning" style="width:100%;">
                                    <i class="fas fa-key"></i> Changer le mot de passe
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Infos système -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h2 class="admin-card-title"><i class="fas fa-server" style="margin-right:8px;"></i>Informations système</h2>
                        </div>
                        <div class="admin-card-body">
                            <?php
                            $infos = [
                                ['icon' => 'fa-code-branch',   'label' => 'Version',          'value' => '1.0.0',                   'badge' => 'primary'],
                                ['icon' => 'fa-signal',        'label' => 'Statut',            'value' => 'En ligne',                'badge' => 'success'],
                                ['icon' => 'fa-calendar-alt',  'label' => 'Date du jour',      'value' => date('d/m/Y'),             'badge' => 'primary'],
                                ['icon' => 'fa-php',           'label' => 'PHP',               'value' => phpversion(),              'badge' => 'warning'],
                                ['icon' => 'fa-database',      'label' => 'Base de données',   'value' => 'Connectée',               'badge' => 'success'],
                                ['icon' => 'fa-globe',         'label' => 'Environnement',     'value' => env('APP_ENV','production'),'badge' => 'warning'],
                            ];
                            ?>
                            <div style="display:flex;flex-direction:column;gap:10px;">
                                <?php foreach ($infos as $info): ?>
                                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--border-color);">
                                    <span style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-secondary);">
                                        <i class="fas <?php echo $info['icon']; ?>" style="width:16px;text-align:center;color:var(--primary-color);"></i>
                                        <?php echo $info['label']; ?>
                                    </span>
                                    <span class="admin-badge admin-badge-<?php echo $info['badge']; ?>"><?php echo htmlspecialchars($info['value']); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="../../assets/js/admin.js"></script>
</body>
</html>
