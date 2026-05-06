<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/database.php';

// Vérifier l'accès
requireLogin();
requireRole('medecin');

$user_id = $_SESSION['user_id'];
$nom = $_SESSION['nom'] ?? '';
$prenom = $_SESSION['prenom'] ?? '';

// Initialiser la connexion
$db = db();

// Données globales pour le médecin si nécessaire
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Espace Médecin - MedConnect'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0fdf4', 100: '#dcfce7', 200: '#bbf7d0', 300: '#86efac',
                            400: '#4ade80', 500: '#22c55e', 600: '#16a34a', 700: '#15803d',
                            800: '#166534', 900: '#14532d',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8fafc; }
        .glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .nav-link { transition: all 0.3s ease; }
        .nav-link:hover { background: rgba(34, 197, 94, 0.1); transform: translateX(5px); }
        .nav-link.active { background: rgba(34, 197, 94, 0.15); border-left: 4px solid #22c55e; color: #166534; font-weight: 600; }
        .fade-in { animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="min-h-screen flex overflow-hidden">
    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-xl flex flex-col h-screen sticky top-0 left-0 z-50">
        <div class="p-6 flex items-center gap-3 border-b">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center shadow-lg">
                <i class="fas fa-heartbeat text-white"></i>
            </div>
            <span class="text-xl font-bold text-gray-800">MedConnect</span>
        </div>
        
        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            <a href="dashboard.php" class="nav-link flex items-center px-4 py-3 rounded-xl text-gray-600 <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home mr-3 w-5"></i>Tableau de bord
            </a>
            <a href="patients.php" class="nav-link flex items-center px-4 py-3 rounded-xl text-gray-600 <?php echo ($current_page === 'patients.php' || $current_page === 'patient_details.php') ? 'active' : ''; ?>">
                <i class="fas fa-users mr-3 w-5"></i>Mes Patients
            </a>
            <a href="rdv.php" class="nav-link flex items-center px-4 py-3 rounded-xl text-gray-600 <?php echo $current_page === 'rdv.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt mr-3 w-5"></i>Agenda
            </a>
            <a href="consultations.php" class="nav-link flex items-center px-4 py-3 rounded-xl text-gray-600 <?php echo in_array($current_page, ['consultations.php', 'voir_consultation.php', 'modifier_consultation.php', 'nouvelle_consultation.php']) ? 'active' : ''; ?>">
                <i class="fas fa-stethoscope mr-3 w-5"></i>Consultations
            </a>
            <a href="ordonnances.php" class="nav-link flex items-center px-4 py-3 rounded-xl text-gray-600 <?php echo in_array($current_page, ['ordonnances.php', 'voir_ordonnance.php', 'modifier_ordonnance.php', 'nouvelle_ordonnance.php']) ? 'active' : ''; ?>">
                <i class="fas fa-prescription mr-3 w-5"></i>Ordonnances
            </a>
            <a href="messages.php" class="nav-link flex items-center px-4 py-3 rounded-xl text-gray-600 <?php echo $current_page === 'messages.php' ? 'active' : ''; ?>">
                <i class="fas fa-envelope mr-3 w-5"></i>Messages
            </a>
            <a href="profile_medecin.php" class="nav-link flex items-center px-4 py-3 rounded-xl text-gray-600 <?php echo ($current_page === 'profile_medecin.php' || $current_page === 'profil.php') ? 'active' : ''; ?>">
                <i class="fas fa-user-md mr-3 w-5"></i>Mon Profil
            </a>
        </nav>
        
        <div class="p-4 border-t">
            <a href="<?php echo url('views/logout.php'); ?>" class="flex items-center px-4 py-3 rounded-xl text-red-600 hover:bg-red-50 transition-colors">
                <i class="fas fa-sign-out-alt mr-3"></i>Déconnexion
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <!-- Header -->
        <header class="h-20 bg-white/80 backdrop-blur-md border-b sticky top-0 z-40 flex items-center justify-between px-8">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                    <i class="<?php echo $header_icon ?? 'fas fa-user-md'; ?> text-xl"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-800"><?php echo $header_title ?? 'Espace Médecin'; ?></h2>
            </div>
            
            <div class="flex items-center gap-6">
                <div class="text-right hidden md:block">
                    <p class="text-sm font-bold text-gray-800">Dr. <?php echo htmlspecialchars($prenom . ' ' . $nom); ?></p>
                    <p class="text-xs text-gray-500">Médecin</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-400 to-green-600 border-2 border-white shadow-sm"></div>
            </div>
        </header>

        <!-- Scrollable Body -->
        <main class="flex-1 overflow-y-auto p-8 custom-scrollbar">
