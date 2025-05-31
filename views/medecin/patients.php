<?php
require_once '../../includes/session.php';
require_once '../../config/config.php';

// Vérifier si l'utilisateur est connecté et est un médecin
requireLogin();
requireRole('medecin');

$user_id = $_SESSION['user_id'];

try {
    // Récupérer la liste des patients du médecin
    $stmt = db()->prepare("
        SELECT DISTINCT 
            p.*
        FROM patient p
        WHERE p.id_medecin = ?
        ORDER BY p.nom, p.prenom
    ");
    
    if (!$stmt->execute([$user_id])) {
        throw new PDOException("Erreur lors de l'exécution de la requête");
    }
    
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($patients)) {
        $message = "Vous n'avez pas encore de patients.";
    }
} catch (PDOException $e) {
    error_log("Erreur de base de données : " . $e->getMessage());
    $message = "Une erreur est survenue lors de la récupération des patients.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Patients - MedConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                            950: '#052e16',
                        },
                        secondary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                            950: '#082f49',
                        },
                    },
                    fontFamily: {
                        'sans': ['Poppins', 'sans-serif'],
                        'heading': ['Plus Jakarta Sans', 'sans-serif'],
                    },
                },
            },
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
            background-image: 
                radial-gradient(circle at 20% 35%, rgba(34, 197, 94, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 75% 80%, rgba(14, 165, 233, 0.03) 0%, transparent 50%);
        }
        
        /* Glassmorphism styles */
        .glass {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
            transition: all 0.3s ease;
        }
        
        .glass-card:hover {
            box-shadow: 0 10px 40px 0 rgba(31, 38, 135, 0.1);
            transform: translateY(-5px);
        }
        
        .stat-card {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at center, rgba(34, 197, 94, 0.08) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 0;
        }
        
        .stat-card:hover::before {
            opacity: 1;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .patient-card {
            transition: all 0.3s ease;
        }
        
        .patient-card:hover {
            transform: translateX(5px);
        }
        
        .reminder-card {
            transition: all 0.3s ease;
        }
        
        .reminder-card:hover {
            transform: scale(1.02);
        }
        
        .nav-link {
            transition: all 0.3s ease;
            border-radius: 0.75rem;
            position: relative;
            overflow: hidden;
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, rgba(34, 197, 94, 0.1), transparent);
            transition: width 0.3s ease;
        }
        
        .nav-link:hover::before {
            width: 100%;
        }
        
        .nav-link:hover {
            transform: translateX(5px);
        }
        
        .nav-link.active {
            background: linear-gradient(90deg, rgba(34, 197, 94, 0.15), rgba(34, 197, 94, 0.05));
            border-left: 4px solid #22c55e;
            font-weight: 600;
        }
        
        .section-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: #166534;
            margin-bottom: 1rem;
        }
        
        .icon-gradient {
            background: linear-gradient(135deg, #22c55e, #0ea5e9);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .icon-circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(14, 165, 233, 0.1));
        }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        
        .float-animation {
            animation: float 6s ease-in-out infinite;
        }
        
        .sidebar-logo {
            background: linear-gradient(135deg, #22c55e, #0ea5e9);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
        }
    </style>
</head>
<body class="min-h-screen">
    <div class="min-h-screen flex flex-nowrap">
        <!-- Barre latérale -->
        <aside class="w-72 glass flex flex-col py-8 px-6 relative overflow-hidden h-screen sticky top-0 left-0">
            <!-- Formes décoratives -->
            <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-r from-primary-200/30 to-secondary-200/30 -z-10 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 w-full h-32 bg-gradient-to-l from-primary-200/30 to-secondary-200/30 -z-10 blur-3xl"></div>
            
            <!-- Logo et titre -->
            <div class="flex items-center justify-start mb-12">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary-500 to-secondary-500 flex items-center justify-center shadow-lg shadow-primary-500/20">
                    <i class="fas fa-heartbeat text-white text-xl"></i>
                </div>
                <h1 class="text-2xl sidebar-logo ml-3">MedConnect</h1>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 space-y-2">
                <a href="dashboard.php" class="nav-link flex items-center px-4 py-3 text-slate-700">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3 bg-primary-100">
                        <i class="fas fa-home text-primary-600"></i>
                    </div>
                    <span>Tableau de bord</span>
                </a>
                <a href="patients.php" class="nav-link active flex items-center px-4 py-3 text-slate-700">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3 bg-primary-100/50">
                        <i class="fas fa-users text-primary-600"></i>
                    </div>
                    <span>Mes Patients</span>
                </a>
                <a href="rdv.php" class="nav-link flex items-center px-4 py-3 text-slate-700">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3 bg-primary-100/50">
                        <i class="fas fa-calendar-alt text-primary-600"></i>
                    </div>
                    <span>Agenda</span>
                </a>
                <a href="consultations.php" class="nav-link flex items-center px-4 py-3 text-slate-700">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3 bg-primary-100/50">
                        <i class="fas fa-stethoscope text-primary-600"></i>
                    </div>
                    <span>Consultations</span>
                </a>
                <a href="ordonnances.php" class="nav-link flex items-center px-4 py-3 text-slate-700">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3 bg-primary-100/50">
                        <i class="fas fa-prescription text-primary-600"></i>
                    </div>
                    <span>Ordonnances</span>
                </a>
                <a href="messages.php" class="nav-link flex items-center px-4 py-3 text-slate-700">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3 bg-primary-100/50">
                        <i class="fas fa-envelope text-primary-600"></i>
                    </div>
                    <span>Messages</span>
                </a>
                <a href="profile_medecin.php" class="nav-link flex items-center px-4 py-3 text-slate-700">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3 bg-primary-100/50">
                        <i class="fas fa-user-md text-primary-600"></i>
                    </div>
                    <span>Mon Profil</span>
                </a>
            </nav>
            
            <!-- Bouton de déconnexion -->
            <div class="mt-8">
                <a href="../../views/logout.php" class="flex items-center justify-center bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-4 py-3 rounded-xl transition-all duration-300 shadow-lg shadow-red-500/20 hover:shadow-red-500/30 hover:-translate-y-1">
                    <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                </a>
            </div>
        </aside>

        <!-- Contenu principal -->
        <div class="flex-1 pl-0">
            <!-- En-tête -->
            <header class="bg-white shadow-sm sticky top-0 z-20">
                <div class="container mx-auto px-6 py-4 flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-r from-primary-500 to-secondary-500 flex items-center justify-center shadow-md">
                            <i class="fas fa-users text-white text-xl"></i>
                        </div>
                        <h1 class="text-2xl font-bold text-primary-800 font-heading">Mes Patients</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <input type="text" placeholder="Rechercher un patient..." class="pl-10 pr-4 py-2 rounded-lg focus:outline-none border border-gray-200">
                            <i class="fas fa-search absolute left-3 top-3 text-primary-400"></i>
                        </div>
                        <a href="register_patient.php" class="bg-gradient-to-r from-primary-500 to-secondary-500 hover:from-primary-600 hover:to-secondary-600 text-white px-4 py-2 rounded-lg shadow-md hover:shadow-lg">
                            <i class="fas fa-plus mr-2"></i>Nouveau patient
                        </a>
                    </div>
                </div>
            </header>

            <!-- Contenu principal -->
            <main class="container mx-auto px-6 py-8">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="glass-card border-l-4 border-secondary-500 text-secondary-800 p-4 mb-6 rounded-lg" role="alert">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-secondary-500 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="font-medium"><?php echo $_SESSION['success']; ?></p>
                            </div>
                        </div>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (isset($message)): ?>
                    <div class="glass-card border-l-4 border-yellow-500 text-yellow-800 p-4 mb-6 rounded-lg" role="alert">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-yellow-500 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="font-medium"><?php echo htmlspecialchars($message); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Statistiques des patients -->
                <div class="mb-8">
                    <h2 class="section-title flex items-center mb-6">
                        <span class="icon-circle mr-3 text-xl">
                            <i class="fas fa-chart-pie icon-gradient"></i>
                        </span>
                        Aperçu des patients
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="glass-card p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 text-sm font-medium">Total patients</p>
                                    <h3 class="text-3xl font-bold text-primary-700 mt-1"><?php echo count($patients); ?></h3>
                                </div>
                                <div class="w-14 h-14 rounded-full bg-gradient-to-r from-primary-100 to-primary-200 flex items-center justify-center">
                                    <i class="fas fa-users text-primary-500 text-xl"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="glass-card p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 text-sm font-medium">Consultations ce mois</p>
                                    <h3 class="text-3xl font-bold text-secondary-700 mt-1">--</h3>
                                </div>
                                <div class="w-14 h-14 rounded-full bg-gradient-to-r from-secondary-100 to-secondary-200 flex items-center justify-center">
                                    <i class="fas fa-stethoscope text-secondary-500 text-xl"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="glass-card p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 text-sm font-medium">Patients actifs</p>
                                    <h3 class="text-3xl font-bold text-green-700 mt-1">--</h3>
                                </div>
                                <div class="w-14 h-14 rounded-full bg-gradient-to-r from-green-100 to-green-200 flex items-center justify-center">
                                    <i class="fas fa-user-check text-green-500 text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Liste des patients -->
                <div>
                    <h2 class="section-title flex items-center mb-6">
                        <span class="icon-circle mr-3 text-xl">
                            <i class="fas fa-user-friends icon-gradient"></i>
                        </span>
                        Liste des patients
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php if (empty($patients)): ?>
                            <div class="col-span-3 glass-card p-8 text-center">
                                <div class="w-20 h-20 mx-auto rounded-full bg-gradient-to-r from-gray-100 to-gray-200 flex items-center justify-center mb-4">
                                    <i class="fas fa-user-plus text-gray-400 text-2xl"></i>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-700 mb-2">Aucun patient pour le moment</h3>
                                <p class="text-gray-500 mb-6">Commencez à ajouter des patients pour les voir apparaître ici.</p>
                                <a href="register_patient.php" class="inline-block bg-gradient-to-r from-primary-500 to-secondary-500 hover:from-primary-600 hover:to-secondary-600 text-white px-6 py-3 rounded-lg shadow-md hover:shadow-lg transition-all duration-300">
                                    <i class="fas fa-plus mr-2"></i>Ajouter un patient
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($patients as $index => $patient): ?>
                                <div class="patient-card glass-card p-6">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-14 h-14 rounded-full bg-gradient-to-r from-primary-400 to-secondary-400 flex items-center justify-center shadow-md">
                                                <i class="fas fa-user text-white text-xl"></i>
                                            </div>
                                            <div>
                                                <h3 class="text-lg font-semibold text-primary-800">
                                                    <?php echo htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']); ?>
                                                </h3>
                                                <p class="text-sm text-gray-500">
                                                    <i class="fas fa-envelope mr-2 text-primary-400"></i>
                                                    <?php echo htmlspecialchars($patient['email'] ?? 'Email non renseigné'); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-2 mb-4">
                                        <p class="text-sm text-gray-600">
                                            <i class="fas fa-phone mr-2 text-primary-400"></i>
                                            <?php echo htmlspecialchars($patient['contact'] ?? 'Contact non renseigné'); ?>
                                        </p>
                                        <?php if (isset($patient['datenais'])): ?>
                                        <p class="text-sm text-gray-600">
                                            <i class="fas fa-birthday-cake mr-2 text-primary-400"></i>
                                            <?php echo htmlspecialchars(date('d/m/Y', strtotime($patient['datenais']))); ?>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="flex space-x-2 pt-3 border-t border-gray-100">
                                        <a href="patient_details.php?id=<?php echo $patient['id']; ?>" 
                                           class="flex-1 bg-gradient-to-r from-primary-50 to-primary-100 hover:from-primary-100 hover:to-primary-200 text-primary-700 text-center py-2 px-3 rounded-lg">
                                            <i class="fas fa-eye mr-2"></i>Voir détails
                                        </a>
                                        <a href="nouvelle_consultation.php?patient_id=<?php echo $patient['id']; ?>" 
                                           class="flex-1 bg-gradient-to-r from-secondary-50 to-secondary-100 hover:from-secondary-100 hover:to-secondary-200 text-secondary-700 text-center py-2 px-3 rounded-lg">
                                            <i class="fas fa-plus-circle mr-2"></i>Consultation
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialisation des animations (optionnel)
            if (typeof AOS !== 'undefined') {
                AOS.init({
                    duration: 800,
                    easing: 'ease-in-out',
                    once: true
                });
            }
        });
    </script>
</body>
</html> 