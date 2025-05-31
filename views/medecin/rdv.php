<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

// Vérifier si l'utilisateur est connecté
requireLogin();

// Vérifier si l'utilisateur a le rôle requis
requireRole('medecin');

// Initialiser les messages flash
if (!isset($_SESSION['flash_message'])) {
    $_SESSION['flash_message'] = [];
}

// Initialiser la connexion à la base de données
$database = new Database();
$db = $database->getConnection();

// Récupérer les informations du médecin
$query = "SELECT prenom, nom FROM medecin WHERE id = :medecin_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':medecin_id', $_SESSION['user_id']);
$stmt->execute();
$medecin = $stmt->fetch(PDO::FETCH_ASSOC);
$prenom = $medecin['prenom'];
$nom = $medecin['nom'];

// Gestion de la mise à jour du statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    try {
        // Vérification CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Erreur de sécurité: Token CSRF invalide");
        }
        
        // Débogage - journaliser les données reçues
        error_log("Données POST reçues: " . print_r($_POST, true));

        // Validation des entrées
        $rdv_id = filter_input(INPUT_POST, 'rdv_id', FILTER_VALIDATE_INT);
        // Utiliser htmlspecialchars au lieu de FILTER_SANITIZE_STRING qui est déprécié
        $statut = isset($_POST['statut']) ? htmlspecialchars($_POST['statut'], ENT_QUOTES, 'UTF-8') : null;
        $statuts_valides = ['en attente', 'confirmé', 'annulé', 'accepté', 'refusé'];
        
        // Débogage - journaliser les valeurs après filtrage
        error_log("Après filtrage - rdv_id: $rdv_id, statut: $statut");
        
        if (!$statut || !in_array($statut, $statuts_valides)) {
            $message_erreur = 'Statut de rendez-vous invalide ou vide: ' . ($statut ?: 'vide');
            error_log($message_erreur);
            
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $message_erreur,
                    'received_status' => $statut,
                    'valid_statuses' => $statuts_valides
                ]);
                exit;
            } else {
                $_SESSION['flash_message'] = [
                    'type' => 'error',
                    'message' => $message_erreur
                ];
                header('Location: rdv.php');
                exit;
            }
        }

        if (!$rdv_id) {
            throw new Exception("ID de rendez-vous invalide");
        }
        // La validation du statut a déjà été faite plus haut

        // Démarrer une transaction
        $db->beginTransaction();

        // Vérifier que le médecin peut modifier ce rendez-vous
        $query = "SELECT id, idpatient FROM rendezvous WHERE id = :id AND idmedecin = :medecin_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $rdv_id, PDO::PARAM_INT);
        $stmt->bindParam(':medecin_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $rendezvous = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$rendezvous) {
            throw new Exception("Vous n'êtes pas autorisé à modifier ce rendez-vous");
        }

        // Mettre à jour le statut
        $updateQuery = "UPDATE rendezvous SET statut = :statut WHERE id = :id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':statut', $statut, PDO::PARAM_STR);
        $updateStmt->bindParam(':id', $rdv_id, PDO::PARAM_INT);

        if (!$updateStmt->execute()) {
            throw new Exception("Échec de la mise à jour du rendez-vous");
        }

        // Si le rendez-vous est confirmé, mettre à jour le médecin du patient
        if ($statut === 'confirmé') {
            $updatePatientQuery = "UPDATE patient SET id_medecin = :medecin_id WHERE id = :patient_id";
            $updatePatientStmt = $db->prepare($updatePatientQuery);
            $updatePatientStmt->bindParam(':medecin_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $updatePatientStmt->bindParam(':patient_id', $rendezvous['idpatient'], PDO::PARAM_INT);
            
            if (!$updatePatientStmt->execute()) {
                throw new Exception("Échec de la mise à jour du médecin du patient");
            }
        }

        // Valider la transaction
        $db->commit();

        // Vérifier si la requête est AJAX
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Le statut du rendez-vous a été mis à jour avec succès', 'newStatus' => $statut]);
            exit;
        }

        $_SESSION['flash_message'] = [
            'type' => 'success',
            'message' => 'Le statut du rendez-vous a été mis à jour avec succès'
        ];

    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        // Vérifier si la requête est AJAX
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }

        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => $e->getMessage()
        ];
        error_log("Erreur rdv.php: " . $e->getMessage());
    }

    if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
        header('Location: rdv.php');
        exit;
    }
}

// Récupérer les rendez-vous du médecin
$query = "SELECT r.*, p.nom as patient_nom, p.prenom as patient_prenom 
          FROM rendezvous r 
          JOIN patient p ON r.idpatient = p.id 
          WHERE r.idmedecin = :medecin_id 
          ORDER BY r.dateheure ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':medecin_id', $_SESSION['user_id']);
$stmt->execute();
$rendezvous = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Générer un token CSRF s'il n'existe pas
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Gestion globale des erreurs et des accès refusés pour AJAX
function send_json_error($message) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

// Vérifier l'authentification et le rôle
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        send_json_error('Accès refusé ou session expirée.');
    } else {
        header('Location: ../../login.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda Médecin - MedConnect</title>
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
        
        .rdv-card {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .rdv-card::before {
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
        
        .rdv-card:hover::before {
            opacity: 1;
        }
        
        .rdv-card:hover {
            transform: translateY(-5px);
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
        
        .sidebar-logo {
            background: linear-gradient(135deg, #22c55e, #0ea5e9);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
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
        
        .status-badge {
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .status-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: inherit;
            filter: brightness(1.1);
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .status-badge:hover::before {
            opacity: 1;
        }
        
        /* Styles pour les sélecteurs */
        select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2322c55e'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        select:focus {
            border-color: #22c55e;
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1);
            outline: none;
        }
        
        /* Styles pour les notifications */
        .notification {
            animation: slideIn 0.5s ease forwards, fadeOut 0.5s ease 2.5s forwards;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border-left-width: 4px;
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            max-width: 24rem;
            z-index: 50;
        }
        
        .notification.success {
            background: linear-gradient(to right, rgba(240, 253, 244, 1), rgba(240, 253, 244, 0.8));
            border-left-color: #22c55e;
        }
        
        .notification.error {
            background: linear-gradient(to right, rgba(254, 242, 242, 1), rgba(254, 242, 242, 0.8));
            border-left-color: #ef4444;
        }
        
        .notification .icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        
        .notification.success .icon {
            background-color: rgba(187, 247, 208, 0.5);
            color: #16a34a;
        }
        
        .notification.error .icon {
            background-color: rgba(254, 202, 202, 0.5);
            color: #dc2626;
        }
        
        .notification .content {
            flex-grow: 1;
            font-weight: 500;
            color: #1f2937;
        }
        
        .notification .close {
            color: #9ca3af;
            cursor: pointer;
            transition: color 0.2s ease;
            margin-left: 1rem;
        }
        
        .notification .close:hover {
            color: #6b7280;
        }
        
        @keyframes slideIn {
            0% { transform: translateX(100%); opacity: 0; }
            100% { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes fadeOut {
            0% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; }
        }
    </style>
</head>
<body class="min-h-screen">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                duration: 800,
                easing: 'ease-out',
                once: true
            });
        });
    </script>

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
                <a href="patients.php" class="nav-link flex items-center px-4 py-3 text-slate-700">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3 bg-primary-100/50">
                        <i class="fas fa-users text-primary-600"></i>
                    </div>
                    <span>Mes Patients</span>
                </a>
                <a href="rdv.php" class="nav-link active flex items-center px-4 py-3 text-slate-700">
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
            <header class="glass sticky top-0 z-20">
                <div class="container mx-auto px-6 py-4 flex justify-between items-center">
                    <div class="flex items-center space-x-4" data-aos="fade-right">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-r from-primary-500 to-secondary-500 flex items-center justify-center shadow-md">
                            <i class="fas fa-calendar-alt text-white text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-primary-800 font-heading">Mon Agenda</h1>
                            <p class="text-sm text-gray-600">Dr. <?php echo htmlspecialchars($prenom . ' ' . $nom); ?></p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4" data-aos="fade-left">
                        <div class="px-4 py-2 glass-card flex items-center">
                            <i class="fas fa-calendar-day text-primary-500 mr-2"></i>
                            <span class="text-primary-800 font-medium"><?php echo date('d/m/Y'); ?></span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Contenu principal -->
            <main class="container mx-auto px-6 py-8">
                <!-- Afficher les messages flash -->
                <?php if (!empty($_SESSION['flash_message'])): ?>
                    <div class="relative">
                        <div class="glass-card p-4 mb-6 rounded-lg shadow-md border-l-4 <?= $_SESSION['flash_message']['type'] === 'success' ? 'border-l-green-500 bg-gradient-to-r from-green-50 to-transparent' : 'border-l-red-500 bg-gradient-to-r from-red-50 to-transparent' ?>" role="alert" data-aos="fade-up">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full <?= $_SESSION['flash_message']['type'] === 'success' ? 'bg-green-100 text-green-500' : 'bg-red-100 text-red-500' ?> flex items-center justify-center">
                                        <i class="<?= $_SESSION['flash_message']['type'] === 'success' ? 'fas fa-check' : 'fas fa-exclamation' ?> text-lg"></i>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="font-medium text-gray-800"><?= htmlspecialchars($_SESSION['flash_message']['message']) ?></p>
                                </div>
                                <div class="ml-auto">
                                    <button type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none" onclick="this.parentElement.parentElement.parentElement.remove()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php unset($_SESSION['flash_message']); ?>
                <?php endif; ?>

                <!-- Statistiques des rendez-vous -->
                <div class="mb-8" data-aos="fade-up">
                    <h2 class="section-title flex items-center mb-6">
                        <span class="icon-circle mr-3 text-xl">
                            <i class="fas fa-chart-pie icon-gradient"></i>
                        </span>
                        Aperçu des rendez-vous
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="glass-card p-6" data-aos="zoom-in" data-aos-delay="100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 text-sm font-medium">Total rendez-vous</p>
                                    <h3 class="text-3xl font-bold text-primary-700 mt-1"><?php echo count($rendezvous); ?></h3>
                                </div>
                                <div class="w-14 h-14 rounded-full bg-gradient-to-r from-primary-100 to-primary-200 flex items-center justify-center">
                                    <i class="fas fa-calendar-check text-primary-500 text-xl"></i>
                                </div>
                            </div>
                        </div>
                        
                        <?php 
                        $rdv_confirmes = 0;
                        $rdv_en_attente = 0;
                        
                        foreach ($rendezvous as $rdv) {
                            if ($rdv['statut'] === 'confirmé') {
                                $rdv_confirmes++;
                            } elseif ($rdv['statut'] === 'en attente') {
                                $rdv_en_attente++;
                            }
                        }
                        ?>
                        
                        <div class="glass-card p-6 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1" data-aos="zoom-in" data-aos-delay="200">
                            <div class="relative overflow-hidden">
                                <!-- Cercle décoratif en arrière-plan -->
                                <div class="absolute -top-10 -right-10 w-32 h-32 rounded-full bg-gradient-to-br from-green-100/40 to-green-300/40 blur-md"></div>
                                
                                <div class="flex items-center justify-between relative z-10">
                                    <div>
                                        <p class="text-gray-600 text-sm font-medium uppercase tracking-wider">Rendez-vous confirmés</p>
                                        <h3 class="text-4xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-green-600 to-green-400 mt-2"><?php echo $rdv_confirmes; ?></h3>
                                        <div class="h-1 w-16 bg-gradient-to-r from-green-500 to-green-300 rounded-full mt-2"></div>
                                    </div>
                                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-green-100 to-green-200 flex items-center justify-center shadow-md">
                                        <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="glass-card p-6 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1" data-aos="zoom-in" data-aos-delay="300">
                            <div class="relative overflow-hidden">
                                <!-- Cercle décoratif en arrière-plan -->
                                <div class="absolute -top-10 -right-10 w-32 h-32 rounded-full bg-gradient-to-br from-yellow-100/40 to-yellow-300/40 blur-md"></div>
                                
                                <div class="flex items-center justify-between relative z-10">
                                    <div>
                                        <p class="text-gray-600 text-sm font-medium uppercase tracking-wider">En attente</p>
                                        <h3 class="text-4xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-yellow-600 to-yellow-400 mt-2"><?php echo $rdv_en_attente; ?></h3>
                                        <div class="h-1 w-16 bg-gradient-to-r from-yellow-500 to-yellow-300 rounded-full mt-2"></div>
                                    </div>
                                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-yellow-100 to-yellow-200 flex items-center justify-center shadow-md">
                                        <i class="fas fa-clock text-yellow-500 text-2xl"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Liste des rendez-vous -->
                <div data-aos="fade-up" data-aos-delay="100">
                    <h2 class="section-title flex items-center mb-6">
                        <span class="icon-circle mr-3 text-xl">
                            <i class="fas fa-calendar-alt icon-gradient"></i>
                        </span>
                        Liste des rendez-vous
                    </h2>
                    
                    <?php if (empty($rendezvous)): ?>
                        <div class="glass-card p-8 text-center" data-aos="fade-up">
                            <div class="w-24 h-24 mx-auto rounded-full bg-gradient-to-r from-gray-100 to-gray-200 flex items-center justify-center mb-4 shadow-inner">
                                <i class="fas fa-calendar-plus text-gray-400 text-3xl"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-700 mb-2">Aucun rendez-vous pour le moment</h3>
                            <p class="text-gray-500 mb-6">Vous n'avez pas encore de rendez-vous programmés.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-6">
                            <?php foreach ($rendezvous as $index => $rdv): 
                                // Déterminer les classes de couleur en fonction du statut
                                $statusClasses = '';
                                $statusIcon = '';
                                $borderColor = '';
                                $gradientBg = '';
                                
                                if ($rdv['statut'] === 'confirmé' || $rdv['statut'] === 'accepté') {
                                    $statusClasses = 'bg-green-100 text-green-800 border-green-300';
                                    $statusIcon = 'fas fa-check-circle';
                                    $borderColor = 'border-l-green-500';
                                    $gradientBg = 'from-green-50 to-transparent';
                                } elseif ($rdv['statut'] === 'annulé' || $rdv['statut'] === 'refusé') {
                                    $statusClasses = 'bg-red-100 text-red-800 border-red-300';
                                    $statusIcon = 'fas fa-times-circle';
                                    $borderColor = 'border-l-red-500';
                                    $gradientBg = 'from-red-50 to-transparent';
                                } else {
                                    $statusClasses = 'bg-yellow-100 text-yellow-800 border-yellow-300';
                                    $statusIcon = 'fas fa-clock';
                                    $borderColor = 'border-l-yellow-500';
                                    $gradientBg = 'from-yellow-50 to-transparent';
                                }
                            ?>
                                <div class="rdv-card glass-card p-0 overflow-hidden shadow-sm hover:shadow-md transition-all duration-300 border-l-4 <?= $borderColor ?>" data-aos="fade-up" data-aos-delay="<?php echo $index * 50; ?>">
                                    <div class="bg-gradient-to-r <?= $gradientBg ?> p-6">
                                        <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4">
                                            <!-- Informations du patient -->
                                            <div class="flex items-start space-x-4">
                                                <div class="w-14 h-14 rounded-full bg-gradient-to-br from-primary-400 to-secondary-400 flex items-center justify-center shadow-md flex-shrink-0">
                                                    <i class="fas fa-user text-white text-xl"></i>
                                                </div>
                                                
                                                <div>
                                                    <h3 class="text-lg font-bold text-gray-800 mb-1">
                                                        <?= htmlspecialchars($rdv['patient_nom'] . ' ' . $rdv['patient_prenom']) ?>
                                                    </h3>
                                                    <div class="flex flex-wrap items-center text-sm text-gray-600 gap-3 mb-2">
                                                        <span class="flex items-center">
                                                            <i class="fas fa-calendar-day text-primary-500 mr-2"></i>
                                                            <?= date('d/m/Y', strtotime($rdv['dateheure'])) ?>
                                                        </span>
                                                        <span class="flex items-center">
                                                            <i class="fas fa-clock text-primary-500 mr-2"></i>
                                                            <?= date('H:i', strtotime($rdv['dateheure'])) ?>
                                                        </span>
                                                    </div>
                                                    
                                                    <?php if (isset($rdv['motif']) && !empty($rdv['motif'])): ?>
                                                    <div class="mt-2 p-3 bg-white/70 rounded-lg border border-gray-100">
                                                        <p class="text-sm text-gray-700">
                                                            <i class="fas fa-comment-medical text-primary-500 mr-2"></i>
                                                            <span class="font-medium">Motif:</span> <?= htmlspecialchars($rdv['motif']) ?>
                                                        </p>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <!-- Statut et actions -->
                                            <div class="flex flex-col space-y-3 md:items-end relative z-20">
                                                <span class="px-4 py-2 rounded-full text-sm font-medium border <?= $statusClasses ?> inline-flex items-center justify-center self-start md:self-auto">
                                                    <i class="<?= $statusIcon ?> mr-2"></i>
                                                    <?= ucfirst($rdv['statut']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Formulaire de mise à jour du statut -->
                                <form method="post" action="rdv.php" id="form-<?= $rdv['id'] ?>" class="mt-3 mb-6">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="rdv_id" value="<?= $rdv['id'] ?>">
                                    <input type="hidden" name="update_status" value="1">
                                    
                                    <div class="flex flex-col sm:flex-row items-center gap-3 relative z-30">
                                        <select name="statut" class="p-2.5 border-2 border-gray-300 rounded-lg bg-white w-full md:w-48 text-sm font-medium shadow-sm relative z-30" id="select-<?= $rdv['id'] ?>" data-rdv-id="<?= $rdv['id'] ?>">
                                            <option value="en attente" <?= $rdv['statut'] === 'en attente' ? 'selected' : '' ?>>En attente</option>
                                            <option value="accepté" <?= $rdv['statut'] === 'accepté' ? 'selected' : '' ?>>Accepté</option>
                                            <option value="refusé" <?= $rdv['statut'] === 'refusé' ? 'selected' : '' ?>>Refusé</option>
                                        </select>
                                        
                                        <button type="button" onclick="updateStatus(<?= $rdv['id'] ?>)" class="btn-action bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white px-4 py-2.5 rounded-lg shadow-sm hover:shadow-md transition-all duration-300 w-full sm:w-auto text-sm font-medium relative z-30" id="btn-<?= $rdv['id'] ?>">
                                            <i class="fas fa-save mr-2"></i>Mettre à jour
                                        </button>
                                    </div>
                                </form>
                                        </div>
                                    </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
   

<script>
function updateStatus(rdvId) {
    console.log(`Début de la mise à jour du statut pour le RDV ${rdvId}`);
    const form = document.getElementById(`form-${rdvId}`);
    if (!form) {
        console.error(`Formulaire non trouvé pour l'ID: form-${rdvId}`);
        return;
    }

    // Soumettre directement le formulaire sans AJAX
    form.submit();
}

// Fonction pour afficher des notifications cohérentes avec le design
function showNotification(message, type = 'success', duration = 3000) {
    // Créer la notification
    const notification = document.createElement('div');
    notification.className = `notification ${type} fixed top-4 right-4`;
    
    // Contenu de la notification
    notification.innerHTML = `
        <div class="icon">
            <i class="fas ${type === 'success' ? 'fa-check' : 'fa-exclamation'}"></i>
        </div>
        <div class="content">${message}</div>
        <div class="close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </div>
    `;
    
    // Ajouter la notification au document
    document.body.appendChild(notification);
    
    // Supprimer la notification après la durée spécifiée
    if (duration > 0) {
        setTimeout(() => {
            if (notification.parentElement) {
                notification.classList.add('fade-out');
                setTimeout(() => notification.remove(), 500);
            }
        }, duration);
    }
    
    return notification;
}

// Initialiser les formulaires et les sélecteurs quand le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    // Stocker l'ancienne valeur du select avant le changement
    document.querySelectorAll('select[name="statut"]').forEach(select => {
        select.setAttribute('data-old-value', select.value);
        
        // Enregistrer la valeur au focus
        select.addEventListener('focus', function() {
            this.setAttribute('data-old-value', this.value);
        });
        
        // Débogage des changements de statut
        select.addEventListener('change', function() {
            console.log('Statut changé à:', this.value);
        });
    });
    
    // Vérifier que tous les formulaires sont correctement initialisés
    document.querySelectorAll('form[id^="form-"]').forEach(form => {
        console.log('Formulaire initialisé:', form.id);
    });
});
</script>
</body>
</html>
