<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
require_once '../../models/Dashboard.php';

// Vérifier si l'utilisateur est connecté
requireLogin();

// Vérifier si l'utilisateur a le rôle requis
requireRole('medecin');

// Accès aux informations de l'utilisateur connecté
$user_id = $_SESSION['user_id'];
$nom = $_SESSION['nom'];
$prenom = $_SESSION['prenom'];

// Initialiser la connexion à la base de données
$database = new Database();
$db = $database->getConnection();

// Initialiser le dashboard
$dashboard = new Dashboard($db, $user_id);

// Récupérer les données du dashboard
$rdv_aujourdhui = $dashboard->getRendezVousAujourdhui();
$patients_actifs = $dashboard->getPatientsActifs();
$consultations_jour = $dashboard->getConsultationsDuJour();
$messages_non_lus = $dashboard->getMessagesNonLus();
$derniers_patients = $dashboard->getDerniersPatients();
$rdv_du_jour = $dashboard->getRendezVousDuJour();
$rappels = $dashboard->getRappelsImportants();

// Nombre de consultations par jour pour le mois en cours (optimisé)
$days_in_month = date('t');
$current_month = date('m');
$current_year = date('Y');
$stmt = $db->prepare("
    SELECT DATE(date_consultation) as jour, COUNT(*) as total
    FROM consultation
    WHERE id_medecin = ? AND MONTH(date_consultation) = ? AND YEAR(date_consultation) = ?
    GROUP BY jour
    ORDER BY jour
");
$stmt->execute([$user_id, $current_month, $current_year]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Préparer les données pour tous les jours du mois
$consultations_par_jour = [];
$labels = [];
for ($i = 1; $i <= $days_in_month; $i++) {
    $date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $i);
    $labels[] = date('d/m', strtotime($date));
    $consultations_par_jour[$date] = 0;
}
foreach ($rows as $row) {
    $consultations_par_jour[$row['jour']] = (int)$row['total'];
}
$consultations_par_jour = array_values($consultations_par_jour);

// Nombre total de patients suivis (tous les patients affectés à ce médecin)
$stmt = $db->prepare("SELECT COUNT(*) FROM patient WHERE id_medecin = ?");
$stmt->execute([$user_id]);
$total_patients = (int)$stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Médecin</title>
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
    <?php include_once '../../views/components/styles.php'; ?>
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
    <div class="min-h-screen flex">
        <!-- Barre latérale -->
        <aside class="w-72 glass flex flex-col py-8 px-6 relative overflow-hidden">
            <!-- Formes décoratives -->
            <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-r from-primary-200/30 to-secondary-200/30 -z-10 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 w-full h-32 bg-gradient-to-l from-primary-200/30 to-secondary-200/30 -z-10 blur-3xl"></div>
            
            <!-- Logo et titre -->
            <div class="flex items-center justify-start mb-12" data-aos="fade-right">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary-500 to-secondary-500 flex items-center justify-center shadow-lg shadow-primary-500/20">
                    <i class="fas fa-heartbeat text-white text-xl"></i>
                </div>
                <h1 class="text-2xl sidebar-logo ml-3">MedConnect</h1>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 space-y-2" data-aos="fade-right" data-aos-delay="100">
                <a href="dashboard.php" class="nav-link active flex items-center px-4 py-3 text-slate-700">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3 bg-primary-100">
                        <i class="fas fa-home text-primary-600"></i>
                    </div>
                    <span>Tableau de bord</span>
                </a>
                <a href="patients.php" class="nav-link flex items-center px-4 py-3 text-slate-700">
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
            <div class="mt-8" data-aos="fade-right" data-aos-delay="200">
                <a href="../../views/logout.php" class="flex items-center justify-center bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-4 py-3 rounded-xl transition-all duration-300 shadow-lg shadow-red-500/20 hover:shadow-red-500/30 hover:-translate-y-1">
                    <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                </a>
            </div>
        </aside>

        <!-- Contenu principal -->
        <div class="flex-1 relative">
            <!-- Formes décoratives -->
            <div class="absolute top-40 right-10 w-72 h-72 bg-primary-100 rounded-full opacity-20 blur-3xl -z-10"></div>
            <div class="absolute bottom-40 left-10 w-72 h-72 bg-secondary-100 rounded-full opacity-20 blur-3xl -z-10"></div>
            
            <!-- En-tête -->
            <header class="glass sticky top-0 z-30">
                <div class="container mx-auto px-6 py-4 flex justify-between items-center">
                    <div class="flex items-center space-x-4" data-aos="fade-right">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary-600 to-secondary-600 flex items-center justify-center shadow-lg shadow-primary-500/20">
                            <i class="fas fa-user-md text-white text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-slate-800">Dr. <?php echo htmlspecialchars($prenom . ' ' . $nom); ?></h1>
                            <p class="text-sm text-slate-500">Médecin</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-6" data-aos="fade-left">
                        <div class="text-sm text-slate-600 flex items-center">
                            <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center mr-2">
                                <i class="fas fa-calendar-alt text-primary-600"></i>
                            </div>
                            <span><?php echo date('d/m/Y'); ?></span>
                        </div>
                        <div class="relative">
                            <button class="w-10 h-10 rounded-full bg-white flex items-center justify-center border border-slate-200 hover:bg-slate-50 transition-colors duration-300 relative">
                                <i class="fas fa-bell text-slate-600"></i>
                                <span class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-red-500 text-white text-xs flex items-center justify-center">3</span>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Contenu principal -->
            <main class="container mx-auto px-6 py-8">
                <!-- Titre de bienvenue -->
                <div class="mb-10" data-aos="fade-up">
                    <span class="inline-block px-4 py-1.5 rounded-full bg-gradient-to-r from-primary-100 to-secondary-100 text-primary-600 font-medium text-sm mb-4">
                        <i class="fas fa-chart-line mr-2"></i>Tableau de bord
                    </span>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">Bonjour, Dr. <?php echo htmlspecialchars($prenom); ?></h1>
                    <p class="text-slate-500">Voici un aperçu de votre activité et de vos tâches du jour</p>
                </div>
                
                <!-- Statistiques rapides -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                    <!-- Carte RDV -->
                    <div class="glass-card stat-card p-6" data-aos="fade-up" data-aos-delay="100">
                        <div class="flex items-center justify-between relative z-10">
                            <div>
                                <p class="text-sm font-medium text-slate-500 mb-1">Rendez-vous aujourd'hui</p>
                                <h3 class="text-3xl font-bold text-slate-800"><?php echo $rdv_aujourdhui; ?></h3>
                                <div class="mt-2 text-xs text-primary-600 font-medium flex items-center">
                                    <i class="fas fa-arrow-up mr-1"></i>
                                    <span>+12% cette semaine</span>
                                </div>
                            </div>
                            <div class="icon-circle">
                                <i class="fas fa-calendar-check text-xl icon-gradient"></i>
                            </div>
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-primary-400 to-primary-600 rounded-b-xl"></div>
                    </div>
                    
                    <!-- Carte Patients -->
                    <div class="glass-card stat-card p-6" data-aos="fade-up" data-aos-delay="200">
                        <div class="flex items-center justify-between relative z-10">
                            <div>
                                <p class="text-sm font-medium text-slate-500 mb-1">Patients actifs</p>
                                <h3 class="text-3xl font-bold text-slate-800"><?php echo $patients_actifs; ?></h3>
                                <div class="mt-2 text-xs text-primary-600 font-medium flex items-center">
                                    <i class="fas fa-arrow-up mr-1"></i>
                                    <span>+5% ce mois-ci</span>
                                </div>
                            </div>
                            <div class="icon-circle">
                                <i class="fas fa-users text-xl icon-gradient"></i>
                            </div>
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-secondary-400 to-secondary-600 rounded-b-xl"></div>
                    </div>
                    
                    <!-- Carte Consultations -->
                    <div class="glass-card stat-card p-6" data-aos="fade-up" data-aos-delay="300">
                        <div class="flex items-center justify-between relative z-10">
                            <div>
                                <p class="text-sm font-medium text-slate-500 mb-1">Consultations du jour</p>
                                <h3 class="text-3xl font-bold text-slate-800"><?php echo $consultations_jour; ?></h3>
                                <div class="mt-2 text-xs text-primary-600 font-medium flex items-center">
                                    <i class="fas fa-arrow-up mr-1"></i>
                                    <span>+8% cette semaine</span>
                                </div>
                            </div>
                            <div class="icon-circle">
                                <i class="fas fa-stethoscope text-xl icon-gradient"></i>
                            </div>
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-b-xl"></div>
                    </div>
                    
                    <!-- Carte Messages -->
                    <div class="glass-card stat-card p-6" data-aos="fade-up" data-aos-delay="400">
                        <div class="flex items-center justify-between relative z-10">
                            <div>
                                <p class="text-sm font-medium text-slate-500 mb-1">Messages non lus</p>
                                <h3 class="text-3xl font-bold text-slate-800"><?php echo $messages_non_lus; ?></h3>
                                <div class="mt-2 text-xs text-primary-600 font-medium flex items-center">
                                    <i class="fas fa-arrow-down mr-1"></i>
                                    <span>-3% cette semaine</span>
                                </div>
                            </div>
                            <div class="icon-circle">
                                <i class="fas fa-envelope text-xl icon-gradient"></i>
                            </div>
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-secondary-500 to-primary-500 rounded-b-xl"></div>
                    </div>
                </div>

                <!-- Graphique des consultations -->
                <div class="glass-card p-6 my-10" data-aos="fade-up" data-aos-delay="100">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="section-title mb-0">
                            <i class="fas fa-chart-bar mr-2 icon-gradient"></i>Consultations du mois en cours
                        </h2>
                        <div class="flex space-x-2">
                            <button class="px-3 py-1 text-sm rounded-lg bg-primary-100 text-primary-700 font-medium hover:bg-primary-200 transition-colors duration-300">Jour</button>
                            <button class="px-3 py-1 text-sm rounded-lg bg-white text-slate-500 font-medium hover:bg-slate-100 transition-colors duration-300">Semaine</button>
                            <button class="px-3 py-1 text-sm rounded-lg bg-white text-slate-500 font-medium hover:bg-slate-100 transition-colors duration-300">Mois</button>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-xl">
                        <canvas id="consultationsChart" height="120"></canvas>
                    </div>
                    <div class="mt-6 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="w-3 h-3 rounded-full bg-primary-500"></span>
                            <span class="text-sm text-slate-600">Consultations</span>
                        </div>
                        <div class="text-lg">
                            <span class="font-medium text-slate-600">Nombre total de patients suivis : </span>
                            <span class="text-primary-700 font-bold"><?php echo $total_patients; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Sections principales -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
                    <!-- Prochains rendez-vous -->
                    <div class="glass-card p-6" data-aos="fade-up" data-aos-delay="100">
                        <h2 class="section-title mb-6">
                            <i class="fas fa-calendar-day mr-2 icon-gradient"></i>Prochains rendez-vous
                        </h2>
                        <div class="space-y-4">
                            <?php if (count($rdv_du_jour) > 0): ?>
                                <?php foreach($rdv_du_jour as $rdv): ?>
                                    <div class="patient-card flex items-center p-4 bg-white rounded-xl hover:shadow-md transition-all duration-300">
                                        <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center mr-4">
                                            <i class="fas fa-user text-primary-600"></i>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="font-medium text-slate-800"><?php echo htmlspecialchars($rdv['nom_patient']); ?></h3>
                                            <p class="text-sm text-slate-500"><?php echo htmlspecialchars($rdv['motif']); ?></p>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-sm font-medium text-primary-600"><?php echo date('H:i', strtotime($rdv['heure'])); ?></span>
                                            <div class="text-xs text-slate-500"><?php echo date('d/m/Y', strtotime($rdv['date'])); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-6 text-slate-500">
                                    <i class="fas fa-calendar-times text-3xl mb-2 text-slate-300"></i>
                                    <p>Aucun rendez-vous aujourd'hui</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mt-4 text-center">
                            <a href="rdv.php" class="inline-flex items-center text-primary-600 hover:text-primary-700 font-medium">
                                <span>Voir tous les rendez-vous</span>
                                <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Derniers patients -->
                    <div class="glass-card p-6" data-aos="fade-up" data-aos-delay="200">
                        <h2 class="section-title mb-6">
                            <i class="fas fa-user-plus mr-2 icon-gradient"></i>Derniers patients
                        </h2>
                        <div class="space-y-4">
                            <?php if (count($derniers_patients) > 0): ?>
                                <?php foreach($derniers_patients as $patient): ?>
                                    <div class="patient-card flex items-center p-4 bg-white rounded-xl hover:shadow-md transition-all duration-300">
                                        <div class="w-10 h-10 rounded-full bg-secondary-100 flex items-center justify-center mr-4">
                                            <i class="fas fa-user-circle text-secondary-600"></i>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="font-medium text-slate-800"><?php echo htmlspecialchars($patient['nom']); ?></h3>
                                            <p class="text-sm text-slate-500"><?php echo htmlspecialchars($patient['email']); ?></p>
                                        </div>
                                        <div>
                                            <a href="patient_details.php?id=<?php echo $patient['id']; ?>" class="px-3 py-1 text-xs rounded-full bg-secondary-100 text-secondary-700 hover:bg-secondary-200 transition-colors duration-300">
                                                Voir profil
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-6 text-slate-500">
                                    <i class="fas fa-users text-3xl mb-2 text-slate-300"></i>
                                    <p>Aucun patient récent</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mt-4 text-center">
                            <a href="patients.php" class="inline-flex items-center text-primary-600 hover:text-primary-700 font-medium">
                                <span>Voir tous les patients</span>
                                <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Section Rappels -->
                <div class="glass-card p-6 mb-10" data-aos="fade-up" data-aos-delay="100">
                    <h2 class="section-title mb-6">
                        <i class="fas fa-bell mr-2 icon-gradient"></i>Rappels importants
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="reminder-card p-5 bg-gradient-to-br from-amber-50 to-amber-100 rounded-xl border border-amber-200 shadow-sm hover:shadow-md transition-all duration-300">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-amber-400 to-amber-500 flex items-center justify-center shadow-sm shadow-amber-400/20">
                                    <i class="fas fa-syringe text-white text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-amber-800 text-lg"><?php echo $rappels['vaccins']; ?></h3>
                                    <p class="text-sm text-amber-700">Rappels de vaccins à effectuer</p>
                                </div>
                            </div>
                        </div>
                        <div class="reminder-card p-5 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl border border-blue-200 shadow-sm hover:shadow-md transition-all duration-300">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-blue-500 flex items-center justify-center shadow-sm shadow-blue-400/20">
                                    <i class="fas fa-file-medical text-white text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-blue-800 text-lg"><?php echo $rappels['dossiers']; ?></h3>
                                    <p class="text-sm text-blue-700">Dossiers à mettre à jour</p>
                                </div>
                            </div>
                        </div>
                        <div class="reminder-card p-5 bg-gradient-to-br from-red-50 to-red-100 rounded-xl border border-red-200 shadow-sm hover:shadow-md transition-all duration-300">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-red-400 to-red-500 flex items-center justify-center shadow-sm shadow-red-400/20">
                                    <i class="fas fa-clock text-white text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-red-800 text-lg"><?php echo $rappels['rdv_confirmation']; ?></h3>
                                    <p class="text-sm text-red-700">Rendez-vous en attente</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
    // Initialisation des animations AOS
    document.addEventListener('DOMContentLoaded', function() {
        AOS.init({
            duration: 800,
            easing: 'ease-out',
            once: true,
            offset: 50,
            delay: 100
        });
    });
    
    // Configuration du graphique
    const ctx = document.getElementById('consultationsChart').getContext('2d');
    const consultationsData = <?php echo json_encode($consultations_par_jour); ?>;
    const labels = <?php echo json_encode($labels); ?>;

    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(34, 197, 94, 0.8)');
    gradient.addColorStop(1, 'rgba(14, 165, 233, 0.2)');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Consultations',
                data: consultationsData,
                backgroundColor: gradient,
                borderRadius: 6,
                borderWidth: 0,
                hoverBackgroundColor: 'rgba(34, 197, 94, 0.9)',
                barThickness: 12,
                maxBarThickness: 18
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    titleColor: '#1e293b',
                    bodyColor: '#475569',
                    bodyFont: {
                        family: 'Poppins',
                        size: 13
                    },
                    titleFont: {
                        family: 'Poppins',
                        size: 14,
                        weight: 'bold'
                    },
                    padding: 12,
                    borderColor: 'rgba(226, 232, 240, 0.8)',
                    borderWidth: 1,
                    displayColors: false,
                    callbacks: {
                        title: function(tooltipItems) {
                            return 'Date: ' + tooltipItems[0].label;
                        },
                        label: function(context) {
                            return context.parsed.y + ' consultations';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        font: {
                            family: 'Poppins',
                            size: 11
                        },
                        color: '#94a3b8'
                    }
                },
                y: { 
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(226, 232, 240, 0.5)',
                        drawBorder: false
                    },
                    ticks: {
                        font: {
                            family: 'Poppins',
                            size: 11
                        },
                        color: '#94a3b8',
                        padding: 10,
                        stepSize: 1
                    }
                }
            },
            animation: {
                duration: 2000,
                easing: 'easeOutQuart'
            }
        }
    });
    </script>
</body>
</html> 