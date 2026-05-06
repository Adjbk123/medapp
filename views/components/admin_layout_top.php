<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/database.php';

// Vérifier l'accès
requireLogin();
requireRole('admin');

$user_id = $_SESSION['user_id'];
$nom = $_SESSION['nom'] ?? '';
$prenom = $_SESSION['prenom'] ?? '';

// Initialiser la connexion
$db = db();

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Administration - MedConnect'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        admin: {
                            50: '#f5f7ff', 100: '#ebf0fe', 200: '#ced9fd', 300: '#a1b8fa',
                            400: '#6e8df5', 500: '#4765f0', 600: '#3247e5', 700: '#2736d2',
                            800: '#252eab', 900: '#232c88', 950: '#151953',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .nav-link { transition: all 0.3s ease; }
        .nav-link:hover { background: rgba(71, 101, 240, 0.1); transform: translateX(5px); }
        .nav-link.active { background: rgba(71, 101, 240, 0.15); border-left: 4px solid #4765f0; color: #232c88; font-weight: 600; }
        .fade-in { animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    </style>
</head>
<body class="min-h-screen flex overflow-hidden">
    <!-- Sidebar -->
    <aside class="w-64 bg-slate-900 flex flex-col h-screen sticky top-0 left-0 z-50">
        <div class="p-6 flex items-center gap-3 border-b border-slate-800">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-admin-500 to-admin-700 flex items-center justify-center shadow-lg">
                <i class="fas fa-shield-alt text-white"></i>
            </div>
            <span class="text-xl font-bold text-white">MedAdmin</span>
        </div>
        
        <nav class="flex-1 p-4 space-y-2 overflow-y-auto custom-scrollbar">
            <a href="dashboard.php" class="nav-link flex items-center px-4 py-3 rounded-xl text-slate-400 hover:text-white <?php echo $current_page === 'dashboard.php' ? 'active !text-white' : ''; ?>">
                <i class="fas fa-chart-pie mr-3 w-5 text-sm"></i>Tableau de bord
            </a>
            <a href="doctors.php" class="nav-link flex items-center px-4 py-3 rounded-xl text-slate-400 hover:text-white <?php echo ($current_page === 'doctors.php' || $current_page === 'doctor_details.php') ? 'active !text-white' : ''; ?>">
                <i class="fas fa-user-md mr-3 w-5 text-sm"></i>Médecins
            </a>
            <a href="patients.php" class="nav-link flex items-center px-4 py-3 rounded-xl text-slate-400 hover:text-white <?php echo $current_page === 'patients.php' ? 'active !text-white' : ''; ?>">
                <i class="fas fa-procedures mr-3 w-5 text-sm"></i>Patients
            </a>
            <a href="verify_doctors.php" class="nav-link flex items-center px-4 py-3 rounded-xl text-slate-400 hover:text-white <?php echo ($current_page === 'verify_doctors.php' || $current_page === 'verification_medecins.php') ? 'active !text-white' : ''; ?>">
                <i class="fas fa-check-double mr-3 w-5 text-sm"></i>Vérifications
            </a>
            <a href="settings.php" class="nav-link flex items-center px-4 py-3 rounded-xl text-slate-400 hover:text-white <?php echo $current_page === 'settings.php' ? 'active !text-white' : ''; ?>">
                <i class="fas fa-sliders-h mr-3 w-5 text-sm"></i>Paramètres
            </a>
        </nav>
        
        <div class="p-4 border-t border-slate-800">
            <a href="../logout.php" class="flex items-center px-4 py-3 rounded-xl text-red-400 hover:bg-red-500/10 transition-colors">
                <i class="fas fa-power-off mr-3 text-sm"></i>Déconnexion
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <!-- Header -->
        <header class="h-20 bg-white border-b sticky top-0 z-40 flex items-center justify-between px-8">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-admin-50 flex items-center justify-center text-admin-600">
                    <i class="<?php echo $header_icon ?? 'fas fa-grid-2'; ?> text-xl"></i>
                </div>
                <h2 class="text-xl font-bold text-slate-800"><?php echo $header_title ?? 'Administration'; ?></h2>
            </div>
            
            <div class="flex items-center gap-6">
                <div class="text-right hidden md:block">
                    <p class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($prenom . ' ' . $nom); ?></p>
                    <p class="text-xs text-slate-500 font-medium">Administrateur Principal</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-admin-400 to-admin-600 border-2 border-white shadow-sm flex items-center justify-center text-white">
                    <i class="fas fa-user-shield"></i>
                </div>
            </div>
        </header>

        <!-- Scrollable Body -->
        <main class="flex-1 overflow-y-auto p-8 custom-scrollbar bg-slate-50/50">
