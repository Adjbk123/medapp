<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

requireLogin();
requireRole('patient');

$user_id = $_SESSION['user_id'];
$nom = $_SESSION['nom'];
$prenom = $_SESSION['prenom'];

// Récupérer le prochain rendez-vous
$stmt = db()->prepare("
    SELECT r.dateheure, m.nom as medecin_nom, m.prenom as medecin_prenom, r.statut
    FROM rendezvous r
    JOIN medecin m ON r.idmedecin = m.id
    WHERE r.idpatient = ? AND DATE(r.dateheure) >= CURDATE()
    ORDER BY r.dateheure ASC
    LIMIT 1
");
$stmt->execute([$user_id]);
$prochain_rdv = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer le médecin traitant
$stmt = db()->prepare("
    SELECT m.nom, m.prenom
    FROM medecin m
    JOIN patient p ON p.id_medecin = m.id
    WHERE p.id = ?
");
$stmt->execute([$user_id]);
$medecin = $stmt->fetch(PDO::FETCH_ASSOC);

// Compter les ordonnances
$stmt = db()->prepare("
    SELECT COUNT(*) as total
    FROM ordonnance
    WHERE idpatient = ?
");
$stmt->execute([$user_id]);
$ordonnances = $stmt->fetch(PDO::FETCH_ASSOC);

// Compter les messages non lus
$stmt = db()->prepare("
    SELECT COUNT(*) as total
    FROM messages
    WHERE receiver_id = ? AND lu = 0
");
$stmt->execute([$user_id]);
$messages = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer les rendez-vous récents
$stmt = db()->prepare("
    SELECT r.dateheure, m.nom as medecin_nom, m.prenom as medecin_prenom, r.statut
    FROM rendezvous r
    JOIN medecin m ON r.idmedecin = m.id
    WHERE r.idpatient = ? AND DATE(r.dateheure) >= CURDATE()
    ORDER BY r.dateheure ASC
    LIMIT 3
");
$stmt->execute([$user_id]);
$rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les rappels de médicaments
$stmt = db()->prepare("
    SELECT o.medicaments, o.posologie, o.date_validite
    FROM ordonnance o
    WHERE o.idpatient = ? AND o.date_validite >= CURDATE()
    ORDER BY o.date_validite ASC
    LIMIT 3
");
$stmt->execute([$user_id]);
$medicaments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les documents médicaux
$stmt = db()->prepare("
    SELECT o.id, o.date_creation, o.medicaments, m.nom as medecin_nom, m.prenom as medecin_prenom
    FROM ordonnance o
    JOIN medecin m ON o.idmedecin = m.id
    WHERE o.idpatient = ?
    ORDER BY o.date_creation DESC
    LIMIT 2
");
$stmt->execute([$user_id]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les rappels importants
$stmt = db()->prepare("
    SELECT o.id, o.date_validite, o.renouvellement, o.nombre_renouvellements
    FROM ordonnance o
    WHERE o.idpatient = ? 
    AND o.date_validite >= CURDATE()
    AND (o.renouvellement = 1 OR o.date_validite <= DATE_ADD(CURDATE(), INTERVAL 7 DAY))
    ORDER BY o.date_validite ASC
    LIMIT 3
");
$stmt->execute([$user_id]);
$rappels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les derniers messages
$stmt = db()->prepare("
    SELECT m.id, m.contenu, m.date_envoi, med.nom as medecin_nom, med.prenom as medecin_prenom
    FROM messages m
    JOIN medecin med ON m.sender_id = med.id
    WHERE m.receiver_id = ? AND m.sender_type = 'medecin'
    ORDER BY m.date_envoi DESC
    LIMIT 1
");
$stmt->execute([$user_id]);
$dernier_message = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Patient - MedConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9f1',
                            100: '#dcf1e0',
                            200: '#bbe3c2',
                            300: '#92ce9a',
                            400: '#68b56f',
                            500: '#4d9c52',
                            600: '#3a7e3f',
                            700: '#2e6632',
                            800: '#27512a',
                            900: '#214425',
                        },
                        secondary: {
                            50: '#f0f7f9',
                            100: '#dcecf1',
                            200: '#bbd7e3',
                            300: '#92bace',
                            400: '#6898b5',
                            500: '#4d7e9c',
                            600: '#3a647e',
                            700: '#2e5166',
                            800: '#274151',
                            900: '#213644',
                        }
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f9ff;
            overflow-x: hidden;
        }
        .stat-card {
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .rdv-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .rdv-card:hover {
            transform: translateX(5px);
            border-left-color: #3b82f6;
        }
        .reminder-card {
            transition: all 0.3s ease;
            border-bottom: 2px solid transparent;
        }
        .reminder-card:hover {
            transform: scale(1.02);
            border-bottom-color: #3b82f6;
        }
        .nav-link {
            transition: all 0.3s ease;
            border-radius: 8px;
            margin-bottom: 5px;
            position: relative;
            overflow: hidden;
        }
        .nav-link:hover {
            background-color: rgba(59, 130, 246, 0.1);
            transform: translateX(5px);
        }
        .nav-link.active {
            background-color: rgba(59, 130, 246, 0.15);
            border-left: 4px solid #3b82f6;
            font-weight: 500;
        }
        .nav-link.active::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: #3b82f6;
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.05);
        }
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(59, 130, 246, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);
            }
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .status-confirmed {
            background-color: #DCFCE7;
            color: #10b981;
        }
        .status-pending {
            background-color: #FEF3C7;
            color: #f59e0b;
        }
        .status-cancelled {
            background-color: #FEE2E2;
            color: #ef4444;
        }
        /* Responsive sidebar */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: -100%;
                top: 0;
                height: 100%;
                z-index: 50;
                transition: all 0.3s ease;
            }
            .sidebar.active {
                left: 0;
            }
            .overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 40;
            }
            .overlay.active {
                display: block;
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-[#EFF6FF] to-[#DBEAFE] min-h-screen">
    <!-- Overlay pour mobile -->
    <div class="overlay" id="overlay"></div>

    <div class="min-h-screen flex">
        <!-- Barre latérale -->
        <aside class="sidebar w-64 bg-white shadow-lg flex flex-col py-6 px-4 z-50">
            <div class="flex items-center justify-center mb-10">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#3b82f6] to-[#60a5fa] flex items-center justify-center">
                    <i class="fas fa-heartbeat text-white text-xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-[#1e40af] ml-3">MedConnect</h1>
            </div>
            <div class="overflow-y-auto flex-1">
                <nav class="flex-1 space-y-1">
                    <a href="dashboard.php" class="nav-link active block px-4 py-3 rounded-lg text-[#1e40af]">
                        <i class="fas fa-home mr-3"></i>Tableau de bord
                    </a>
                    <a href="carnet.php" class="nav-link block px-4 py-3 rounded-lg text-[#1e40af]">
                        <i class="fas fa-book-medical mr-3"></i>Mon Carnet de Santé
                    </a>
                    <a href="rdv.php" class="nav-link block px-4 py-3 rounded-lg text-[#1e40af]">
                        <i class="fas fa-calendar-alt mr-3"></i>Mes Rendez-vous
                    </a>
                    <a href="ordonnace.php" class="nav-link block px-4 py-3 rounded-lg text-[#1e40af]">
                        <i class="fas fa-prescription mr-3"></i>Mes Ordonnances
                    </a>
                    <a href="consultations.php" class="nav-link block px-4 py-3 rounded-lg text-[#1e40af]">
                        <i class="fas fa-stethoscope mr-3"></i>Mes Consultations
                    </a>
                    <a href="listes_pharmacie.php" class="nav-link block px-4 py-3 rounded-lg text-[#1e40af]">
                        <i class="fas fa-pills mr-3"></i>Ma Pharmacie
                    </a>
                    <a href="messages.php" class="nav-link block px-4 py-3 rounded-lg text-[#1e40af] relative">
                        <i class="fas fa-envelope mr-3"></i>Messages
                        <?php if ($messages['total'] > 0): ?>
                            <span class="absolute right-3 top-1/2 transform -translate-y-1/2 bg-[#3b82f6] text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">
                                <?php echo $messages['total']; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <a href="profile_patient.php" class="nav-link block px-4 py-3 rounded-lg text-[#1e40af]">
                        <i class="fas fa-user mr-3"></i>Mon Profil
                    </a>
                </nav>
            </div>
            <div class="mt-6">
                <a href="./../logout.php" class="block bg-[#FF5252] hover:bg-[#D32F2F] text-white text-center px-4 py-3 rounded-lg transition-colors duration-300">
                    <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                </a>
            </div>
        </aside>

        <!-- Contenu principal -->
        <div class="flex-1">
            <!-- En-tête -->
            <header class="bg-white shadow-md sticky top-0 z-30">
                <div class="container mx-auto px-4 py-4 flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <!-- Bouton menu mobile -->
                        <button id="menuToggle" class="md:hidden text-[#3b82f6] focus:outline-none">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#3b82f6] to-[#60a5fa] flex items-center justify-center shadow-md">
                                <i class="fas fa-user text-white text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <h1 class="text-xl font-bold text-[#1e40af]"><?php echo htmlspecialchars($prenom . ' ' . $nom); ?></h1>
                                <?php if ($medecin): ?>
                                <p class="text-xs text-[#3b82f6]">Médecin traitant: Dr. <?php echo htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <?php if ($prochain_rdv): ?>
                        <div class="hidden md:flex items-center bg-[#EFF6FF] px-3 py-1 rounded-lg">
                            <i class="fas fa-calendar-check text-[#3b82f6] mr-2"></i>
                            <div>
                                <p class="text-xs text-[#3b82f6]">Prochain RDV</p>
                                <p class="text-sm font-medium text-[#1e40af]"><?php echo date('d M Y H:i', strtotime($prochain_rdv['dateheure'])); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex items-center bg-[#EFF6FF] px-3 py-2 rounded-lg">
                            <i class="fas fa-calendar-alt text-[#3b82f6] mr-2"></i>
                            <span class="text-sm font-medium text-[#1e40af]"><?php echo date('d/m/Y'); ?></span>
                        </div>
                        
                        <a href="messages.php" class="relative">
                            <i class="fas fa-bell text-[#3b82f6] text-xl"></i>
                            <?php if ($messages['total'] > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-[#FF5252] text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">
                                <?php echo $messages['total']; ?>
                            </span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </header>

            <!-- Contenu principal -->
            <main class="container mx-auto px-4 py-8">
                <!-- Bienvenue -->
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-[#1e40af] mb-2">Bonjour, <?php echo htmlspecialchars($prenom); ?> 👋</h2>
                    <p class="text-gray-600">Voici un aperçu de votre santé et de vos prochains rendez-vous</p>
                </div>
                
                <!-- Statistiques rapides -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="stat-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-[#3b82f6] glass-effect">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-[#3b82f6] font-medium">Prochain RDV</p>
                                <?php if ($prochain_rdv): ?>
                                    <h3 class="text-xl font-bold text-[#1e40af] mt-1">
                                        <?php echo date('d M Y', strtotime($prochain_rdv['dateheure'])); ?>
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <?php echo date('H:i', strtotime($prochain_rdv['dateheure'])); ?> - 
                                        Dr. <?php echo htmlspecialchars($prochain_rdv['medecin_prenom'] . ' ' . $prochain_rdv['medecin_nom']); ?>
                                    </p>

                                <?php else: ?>
                                    <h3 class="text-xl font-bold text-[#1e40af] mt-1">Aucun RDV</h3>
                                    <p class="text-xs text-gray-500 mt-1">Prenez rendez-vous</p>
                                <?php endif; ?>
                            </div>
                            <div class="w-14 h-14 rounded-full bg-[#EFF6FF] flex items-center justify-center">
                                <i class="fas fa-calendar-check text-xl text-[#3b82f6]"></i>
                            </div>
                        </div>
                        <div class="mt-4 text-right">
                            <a href="rdv.php" class="text-xs text-[#3b82f6] hover:underline">Voir tous les RDV →</a>
                        </div>
                    </div>
                    
                    <div class="stat-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-[#10b981] glass-effect">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-[#10b981] font-medium">Médecin traitant</p>
                                <?php if ($medecin): ?>
                                    <h3 class="text-xl font-bold text-[#1e40af] mt-1">
                                        Dr. <?php echo htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']); ?>
                                    </h3>

                                <?php else: ?>
                                    <h3 class="text-xl font-bold text-[#1e40af] mt-1">Non assigné</h3>
                                    <p class="text-xs text-gray-500 mt-1">Contactez votre assurance</p>
                                <?php endif; ?>
                            </div>
                            <div class="w-14 h-14 rounded-full bg-[#ECFDF5] flex items-center justify-center">
                                <i class="fas fa-user-md text-xl text-[#10b981]"></i>
                            </div>
                        </div>
                        <div class="mt-4 text-right">
                            <a href="profile_patient.php" class="text-xs text-[#10b981] hover:underline">Voir mon profil →</a>
                        </div>
                    </div>
                    
                    <div class="stat-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-[#f59e0b] glass-effect">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-[#f59e0b] font-medium">Ordonnances</p>
                                <h3 class="text-xl font-bold text-[#1e40af] mt-1"><?php echo $ordonnances['total']; ?></h3>
                                <p class="text-xs text-gray-500 mt-1">
                                    <?php if ($ordonnances['total'] > 0): ?>
                                        Ordonnances actives
                                    <?php else: ?>
                                        Aucune ordonnance
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="w-14 h-14 rounded-full bg-[#FFFBEB] flex items-center justify-center">
                                <i class="fas fa-prescription text-xl text-[#f59e0b]"></i>
                            </div>
                        </div>
                        <div class="mt-4 text-right">
                            <a href="ordonnace.php" class="text-xs text-[#f59e0b] hover:underline">Voir mes ordonnances →</a>
                        </div>
                    </div>
                    
                    <div class="stat-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-[#8b5cf6] glass-effect">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-[#8b5cf6] font-medium">Messages</p>
                                <h3 class="text-xl font-bold text-[#1e40af] mt-1"><?php echo $messages['total']; ?></h3>
                                <p class="text-xs text-gray-500 mt-1">
                                    <?php if ($messages['total'] > 0): ?>
                                        Message<?php echo $messages['total'] > 1 ? 's' : ''; ?> non lu<?php echo $messages['total'] > 1 ? 's' : ''; ?>
                                    <?php else: ?>
                                        Aucun message non lu
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="w-14 h-14 rounded-full bg-[#F5F3FF] flex items-center justify-center">
                                <i class="fas fa-envelope text-xl text-[#8b5cf6]"></i>
                            </div>
                        </div>
                        <div class="mt-4 text-right">
                            <a href="messages.php" class="text-xs text-[#8b5cf6] hover:underline">Voir mes messages →</a>
                        </div>
                    </div>
                </div>

                <!-- Sections principales -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Section Rendez-vous -->
                    <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-semibold text-[#1e40af]">
                                <i class="fas fa-calendar-alt mr-2"></i>Mes Rendez-vous
                            </h2>
                            <a href="/medapp/views/patient/rdv.php" class="bg-[#3b82f6] hover:bg-[#2563eb] text-white px-4 py-2 rounded-lg transition-colors duration-300 flex items-center">
                                <i class="fas fa-plus-circle mr-2"></i>Nouveau RDV
                            </a>
                        </div>
                        
                        <div class="space-y-4">
                            <?php if (empty($rdvs)): ?>
                                <div class="flex flex-col items-center justify-center py-8 bg-[#F9FAFB] rounded-lg">
                                    <div class="w-16 h-16 rounded-full bg-[#EFF6FF] flex items-center justify-center mb-3">
                                        <i class="fas fa-calendar-alt text-2xl text-[#3b82f6]"></i>
                                    </div>
                                    <p class="text-gray-500 font-medium">Aucun rendez-vous à venir</p>
                                    <p class="text-gray-400 text-sm mt-1">Planifiez votre prochain rendez-vous</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($rdvs as $rdv): ?>
                                    <div class="rdv-card p-4 bg-[#F9FAFB] rounded-lg hover:bg-[#EFF6FF] transition-all duration-300">
                                        <div class="flex items-start">
                                            <div class="mr-4 bg-[#EFF6FF] rounded-lg p-2 text-center min-w-[60px]">
                                                <p class="text-lg font-bold text-[#3b82f6]"><?php echo date('d', strtotime($rdv['dateheure'])); ?></p>
                                                <p class="text-xs text-[#3b82f6]"><?php echo date('M', strtotime($rdv['dateheure'])); ?></p>
                                            </div>
                                            <div class="flex-1">
                                                <div class="flex justify-between items-start">
                                                    <div>
                                                        <p class="font-medium text-[#1e40af]">Dr. <?php echo htmlspecialchars($rdv['medecin_prenom'] . ' ' . $rdv['medecin_nom']); ?></p>
                                                        <p class="text-sm text-[#3b82f6] flex items-center mt-1">
                                                            <i class="fas fa-clock mr-2 text-xs"></i>
                                                            <?php echo date('H:i', strtotime($rdv['dateheure'])); ?>
                                                        </p>
                                                    </div>
                                                    <span class="status-badge <?php 
                                                        echo $rdv['statut'] === 'confirmé' ? 'status-confirmed' : 
                                                            ($rdv['statut'] === 'en attente' ? 'status-pending' : 
                                                            'status-cancelled'); 
                                                    ?>">
                                                        <?php echo ucfirst($rdv['statut']); ?>
                                                    </span>
                                                </div>
                                                <div class="flex justify-between items-center mt-3">
                                                    <a href="rdv.php?id=<?php echo $rdv['id'] ?? ''; ?>" class="text-xs text-[#3b82f6] hover:underline">Détails</a>
                                                    <a href="https://calendar.google.com/calendar/render?action=TEMPLATE&text=RDV+Dr.+<?php echo urlencode($rdv['medecin_prenom'] . '+' . $rdv['medecin_nom']); ?>&dates=<?php echo date('Ymd\THi00', strtotime($rdv['dateheure'])); ?>/<?php echo date('Ymd\THi00', strtotime($rdv['dateheure'] . ' +1 hour')); ?>" target="_blank" class="text-xs text-gray-500 hover:text-[#3b82f6]">
                                                        <i class="far fa-calendar-plus mr-1"></i> Ajouter au calendrier
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <div class="text-center mt-4">
                                    <a href="rdv.php" class="text-sm text-[#3b82f6] hover:underline">Voir tous mes rendez-vous</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Section Rappels -->
                    <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-semibold text-[#1e40af]">
                                <i class="fas fa-pills mr-2"></i>Mes Médicaments
                            </h2>
                            <a href="listes_pharmacie.php" class="text-[#3b82f6] hover:text-[#2563eb] px-4 py-2 rounded-lg transition-colors duration-300 flex items-center">
                                <i class="fas fa-clipboard-list mr-2"></i>Ma pharmacie
                            </a>
                        </div>
                        
                        <div class="space-y-4">
                            <?php if (empty($medicaments)): ?>
                                <div class="flex flex-col items-center justify-center py-8 bg-[#F9FAFB] rounded-lg">
                                    <div class="w-16 h-16 rounded-full bg-[#ECFDF5] flex items-center justify-center mb-3">
                                        <i class="fas fa-pills text-2xl text-[#10b981]"></i>
                                    </div>
                                    <p class="text-gray-500 font-medium">Aucun médicament actif</p>
                                    <p class="text-gray-400 text-sm mt-1">Vos ordonnances apparaîtront ici</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($medicaments as $index => $med): ?>
                                    <?php 
                                        $days_left = (strtotime($med['date_validite']) - time()) / (60 * 60 * 24);
                                        $bg_color = $days_left < 7 ? 'bg-[#FEF2F2]' : 'bg-[#F9FAFB]';
                                        $icon_bg = $days_left < 7 ? 'bg-[#FEE2E2]' : 'bg-[#ECFDF5]';
                                        $icon_color = $days_left < 7 ? 'text-[#ef4444]' : 'text-[#10b981]';
                                        
                                        // Déterminer une icône spécifique en fonction du nom du médicament
                                        $med_icons = [
                                            'antibiotique' => 'fa-capsules',
                                            'anti-inflammatoire' => 'fa-tablets',
                                            'antidouleur' => 'fa-pills',
                                            'sirop' => 'fa-prescription-bottle',
                                            'comprimé' => 'fa-tablets',
                                            'gélule' => 'fa-capsules',
                                            'spray' => 'fa-spray-can',
                                            'crème' => 'fa-prescription-bottle-alt',
                                            'pommade' => 'fa-prescription-bottle-alt'
                                        ];
                                        
                                        $icon = 'fa-pills'; // icône par défaut
                                        foreach ($med_icons as $keyword => $specific_icon) {
                                            if (stripos($med['medicaments'] . ' ' . $med['posologie'], $keyword) !== false) {
                                                $icon = $specific_icon;
                                                break;
                                            }
                                        }
                                    ?>
                                    <div class="reminder-card p-4 <?php echo $bg_color; ?> rounded-lg transition-all duration-300">
                                        <div class="flex items-start">
                                            <div class="mr-4 <?php echo $icon_bg; ?> rounded-full w-10 h-10 flex items-center justify-center">
                                                <i class="fas <?php echo $icon; ?> <?php echo $icon_color; ?>"></i>
                                            </div>
                                            <div class="flex-1">
                                                <div class="flex justify-between items-start">
                                                    <div>
                                                        <p class="font-medium text-[#1e40af]"><?php echo htmlspecialchars($med['medicaments']); ?></p>
                                                        <p class="text-sm text-[#3b82f6] mt-1">
                                                            <?php echo htmlspecialchars($med['posologie']); ?>
                                                        </p>
                                                    </div>
                                                    <div class="text-right">
                                                        <p class="text-xs <?php echo $days_left < 7 ? 'text-[#ef4444]' : 'text-gray-500'; ?>">
                                                            <?php if ($days_left < 7): ?>
                                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                            <?php endif; ?>
                                                            Expire <?php echo date('d/m/Y', strtotime($med['date_validite'])); ?>
                                                        </p>
                                                        <p class="text-xs text-gray-500 mt-1">
                                                            <?php echo round($days_left); ?> jour<?php echo round($days_left) > 1 ? 's' : ''; ?> restant<?php echo round($days_left) > 1 ? 's' : ''; ?>
                                                        </p>
                                                    </div>
                                                </div>
                                                <?php if ($index === 0): // Afficher la barre de progression uniquement pour le premier médicament ?>
                                                <div class="mt-3 w-full bg-gray-200 rounded-full h-1.5">
                                                    <?php 
                                                        // Calculer le pourcentage de jours restants (max 100%)
                                                        $percent = min(100, max(0, ($days_left / 30) * 100));
                                                        $bar_color = $days_left < 7 ? 'bg-[#ef4444]' : 'bg-[#10b981]';
                                                    ?>
                                                    <div class="<?php echo $bar_color; ?> h-1.5 rounded-full" style="width: <?php echo $percent; ?>%"></div>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <div class="text-center mt-4">
                                    <a href="ordonnace.php" class="text-sm text-[#10b981] hover:underline">Voir toutes mes ordonnances</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Section Documents et Carnet de santé -->
                <div class="mt-8 bg-white rounded-xl shadow-lg p-6 glass-effect">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-[#1e40af]">
                            <i class="fas fa-file-medical mr-2"></i>Mon Carnet de Santé
                        </h2>
                        <a href="carnet.php" class="bg-[#3b82f6] hover:bg-[#2563eb] text-white px-4 py-2 rounded-lg transition-colors duration-300 flex items-center">
                            <i class="fas fa-upload mr-2"></i>Ajouter un document
                        </a>
                    </div>
                    
                    <div class="space-y-4">
                        <?php if (empty($documents)): ?>
                            <div class="flex flex-col items-center justify-center py-8 bg-[#F9FAFB] rounded-lg">
                                <div class="w-16 h-16 rounded-full bg-[#EFF6FF] flex items-center justify-center mb-3">
                                    <i class="fas fa-file-medical text-2xl text-[#3b82f6]"></i>
                                </div>
                                <p class="text-gray-500 font-medium">Aucun document disponible</p>
                                <p class="text-gray-400 text-sm mt-1">Ajoutez vos documents médicaux ici</p>
                            </div>
                        <?php else: ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <?php foreach ($documents as $doc): ?>
                                    <div class="flex items-center p-4 bg-[#F9FAFB] rounded-lg hover:bg-[#EFF6FF] transition-all duration-300">
                                        <div class="mr-4 bg-[#EFF6FF] rounded-full w-12 h-12 flex items-center justify-center">
                                            <i class="fas fa-file-prescription text-[#3b82f6] text-xl"></i>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <p class="font-medium text-[#1e40af]">Ordonnance - <?php echo date('d/m/Y', strtotime($doc['date_creation'])); ?></p>
                                                    <p class="text-sm text-[#3b82f6] mt-1">Dr. <?php echo htmlspecialchars($doc['medecin_prenom'] . ' ' . $doc['medecin_nom']); ?></p>
                                                    <p class="text-xs text-gray-500 mt-1">
                                                        <?php 
                                                            $med_list = explode(",", $doc['medicaments']);
                                                            echo count($med_list) > 1 ? count($med_list) . " médicaments" : "1 médicament";
                                                        ?>
                                                    </p>
                                                </div>
                                                <div class="flex space-x-2">
                                                    <a href="ordonnance.php?id=<?php echo $doc['id']; ?>" class="text-[#3b82f6] hover:text-[#2563eb] bg-[#EFF6FF] hover:bg-[#DBEAFE] p-2 rounded-full transition-colors duration-300" title="Voir le détail">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="ordonnance.php?id=<?php echo $doc['id']; ?>&download=1" class="text-[#10b981] hover:text-[#059669] bg-[#ECFDF5] hover:bg-[#D1FAE5] p-2 rounded-full transition-colors duration-300" title="Télécharger">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-center mt-4">
                                <a href="carnet.php" class="text-sm text-[#3b82f6] hover:underline">Voir tous mes documents</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Section Rappels et Alertes -->
                <div class="mt-8 bg-white rounded-xl shadow-lg p-6 glass-effect">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-[#1e40af]">
                            <i class="fas fa-bell mr-2"></i>Rappels et Alertes
                        </h2>
                        <span class="text-xs text-gray-500 bg-[#F9FAFB] px-3 py-1 rounded-full">
                            Mis à jour <?php echo date('d/m/Y'); ?>
                        </span>
                    </div>
                    
                    <?php if (empty($rappels)): ?>
                        <div class="flex flex-col items-center justify-center py-8 bg-[#F9FAFB] rounded-lg">
                            <div class="w-16 h-16 rounded-full bg-[#ECFDF5] flex items-center justify-center mb-3">
                                <i class="fas fa-check-circle text-2xl text-[#10b981]"></i>
                            </div>
                            <p class="text-gray-500 font-medium">Aucun rappel pour le moment</p>
                            <p class="text-gray-400 text-sm mt-1">Tout est à jour</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <?php foreach ($rappels as $rappel): ?>
                                <?php 
                                    $days_left = date_diff(new DateTime(), new DateTime($rappel['date_validite']))->days;
                                    $urgency = $days_left < 3 ? 'high' : ($days_left < 7 ? 'medium' : 'low');
                                    $colors = [
                                        'high' => ['bg' => 'bg-[#FEF2F2]', 'border' => 'border-[#FEE2E2]', 'icon_bg' => 'bg-[#FEE2E2]', 'icon' => 'text-[#ef4444]', 'text' => 'text-[#B91C1C]'],
                                        'medium' => ['bg' => 'bg-[#FFFBEB]', 'border' => 'border-[#FEF3C7]', 'icon_bg' => 'bg-[#FEF3C7]', 'icon' => 'text-[#f59e0b]', 'text' => 'text-[#92400E]'],
                                        'low' => ['bg' => 'bg-[#F0F9FF]', 'border' => 'border-[#E0F2FE]', 'icon_bg' => 'bg-[#E0F2FE]', 'icon' => 'text-[#3b82f6]', 'text' => 'text-[#1e40af]']
                                    ];
                                ?>
                                <div class="reminder-card p-4 <?php echo $colors[$urgency]['bg']; ?> rounded-lg border <?php echo $colors[$urgency]['border']; ?> transition-all duration-300">
                                    <div class="flex items-start">
                                        <div class="mr-3 <?php echo $colors[$urgency]['icon_bg']; ?> rounded-full w-10 h-10 flex items-center justify-center">
                                            <?php if ($rappel['renouvellement'] == 1): ?>
                                                <i class="fas fa-sync-alt <?php echo $colors[$urgency]['icon']; ?>"></i>
                                            <?php else: ?>
                                                <i class="fas fa-calendar-alt <?php echo $colors[$urgency]['icon']; ?>"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <p class="font-medium <?php echo $colors[$urgency]['text']; ?>">
                                                <?php if ($rappel['renouvellement'] == 1): ?>
                                                    Renouvellement d'ordonnance
                                                <?php else: ?>
                                                    Ordonnance expirant bientôt
                                                <?php endif; ?>
                                            </p>
                                            <p class="text-sm <?php echo $colors[$urgency]['text']; ?> opacity-80 mt-1">
                                                <?php if ($days_left == 0): ?>
                                                    Expire aujourd'hui
                                                <?php elseif ($days_left == 1): ?>
                                                    Expire demain
                                                <?php else: ?>
                                                    Expire dans <?php echo $days_left; ?> jours
                                                <?php endif; ?>
                                                <?php if ($rappel['renouvellement'] == 1 && $rappel['nombre_renouvellements'] > 0): ?>
                                                    <span class="inline-block ml-2 px-2 py-0.5 bg-white rounded-full text-xs">
                                                        <?php echo $rappel['nombre_renouvellements']; ?> renouvellement<?php echo $rappel['nombre_renouvellements'] > 1 ? 's' : ''; ?> restant<?php echo $rappel['nombre_renouvellements'] > 1 ? 's' : ''; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </p>
                                            <div class="mt-2">
                                                <a href="ordonnance.php?id=<?php echo $rappel['id']; ?>" class="text-xs <?php echo $colors[$urgency]['text']; ?> hover:underline">
                                                    Voir l'ordonnance →
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Section Messagerie -->
                <div class="mt-8 bg-white rounded-xl shadow-lg p-6 glass-effect">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-[#1e40af]">
                            <i class="fas fa-envelope mr-2"></i>Messagerie
                            <?php if ($messages['total'] > 0): ?>
                                <span class="ml-2 px-2 py-1 bg-[#3b82f6] text-white text-sm rounded-full pulse">
                                    <?php echo $messages['total']; ?> non lu<?php echo $messages['total'] > 1 ? 's' : ''; ?>
                                </span>
                            <?php endif; ?>
                        </h2>
                        <a href="messages.php" class="bg-[#3b82f6] hover:bg-[#2563eb] text-white px-4 py-2 rounded-lg transition-colors duration-300 flex items-center">
                            <i class="fas fa-comments mr-2"></i>Voir tous les messages
                        </a>
                    </div>
                    <div class="space-y-4">
                        <?php if (empty($dernier_message)): ?>
                            <div class="flex flex-col items-center justify-center py-8 bg-[#F9FAFB] rounded-lg">
                                <div class="w-16 h-16 rounded-full bg-[#F5F3FF] flex items-center justify-center mb-3">
                                    <i class="fas fa-envelope-open text-2xl text-[#8b5cf6]"></i>
                                </div>
                                <p class="text-gray-500 font-medium">Aucun message</p>
                                <p class="text-gray-400 text-sm mt-1">Votre boîte de réception est vide</p>
                            </div>
                        <?php else: ?>
                            <div class="p-4 bg-[#F9FAFB] rounded-lg hover:bg-[#EFF6FF] transition-all duration-300">
                                <div class="flex items-start">
                                    <div class="mr-4 bg-[#F5F3FF] rounded-full w-12 h-12 flex items-center justify-center">
                                        <i class="fas fa-user-md text-[#8b5cf6] text-xl"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex justify-between items-start">
                                            <p class="font-medium text-[#1e40af]">Dr. <?php echo htmlspecialchars($dernier_message['medecin_prenom'] . ' ' . $dernier_message['medecin_nom']); ?></p>
                                            <span class="text-xs text-gray-500 bg-[#F5F3FF] px-2 py-1 rounded-full">
                                                <?php 
                                                $date = new DateTime($dernier_message['date_envoi']);
                                                $now = new DateTime();
                                                $diff = $date->diff($now);
                                                
                                                if ($diff->d == 0) {
                                                    if ($diff->h == 0) {
                                                        echo "Il y a " . $diff->i . " min";
                                                    } else {
                                                        echo "Il y a " . $diff->h . "h";
                                                    }
                                                } else if ($diff->d == 1) {
                                                    echo "Hier";
                                                } else {
                                                    echo $date->format('d/m/Y');
                                                }
                                                ?>
                                            </span>
                                        </div>
                                        <div class="mt-2 p-3 bg-white rounded-lg border border-[#E0E7FF]">
                                            <p class="text-sm text-gray-700"><?php echo htmlspecialchars($dernier_message['contenu']); ?></p>
                                        </div>
                                        <div class="mt-3 flex justify-end">
                                            <a href="messages.php?reply=<?php echo $dernier_message['id']; ?>" class="text-xs text-[#8b5cf6] hover:underline flex items-center">
                                                <i class="fas fa-reply mr-1"></i> Répondre
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center mt-4">
                                <a href="messages.php" class="text-sm text-[#8b5cf6] hover:underline">Voir tous mes messages</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Graphique des consultations -->
                <div class="mt-8 bg-white rounded-xl shadow-lg p-6 glass-effect">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-[#1e40af]">
                            <i class="fas fa-chart-line mr-2"></i>Suivi de santé
                        </h2>
                        <div class="text-xs text-gray-500 bg-[#F9FAFB] px-3 py-1 rounded-full">
                            Dernières 6 mois
                        </div>
                    </div>
                    <div class="w-full h-64">
                        <canvas id="healthChart"></canvas>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Inclusion du chatbot d'analyse médicale -->
    <?php include_once '../../views/components/include_chatbot_analyzer.php'; ?>

    <!-- Scripts -->
    <script>
        // Menu mobile toggle
        document.addEventListener('DOMContentLoaded', function() {
            var sidebarToggle = document.getElementById('sidebarToggle');
            var sidebar = document.querySelector('.sidebar');
            var overlay = document.getElementById('overlay');

            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('-translate-x-full');
                overlay.classList.toggle('active');
            });

            overlay.addEventListener('click', function() {
                sidebar.classList.remove('-translate-x-full');
                this.classList.remove('active');
            });

            // Le nouveau chatbot d'analyse médicale est géré par son propre script JavaScript

            // Graphique de suivi de santé
            const ctx = document.getElementById('healthChart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin'],
                        datasets: [{
                            label: 'Consultations',
                            data: [2, 1, 3, 1, 2, 1],
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true
                        }, {
                            label: 'Ordonnances',
                            data: [1, 1, 2, 1, 1, 0],
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
