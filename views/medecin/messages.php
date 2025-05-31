<?php
require_once '../../config/database.php';
require_once '../../models/Message.php';
require_once '../../includes/session.php';
require_once '../../includes/upload_image.php';

// Vérifier si l'utilisateur est connecté et est un médecin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$message = new Message($db);

// Récupérer la liste des patients du médecin
$query = "SELECT id, nom, prenom FROM patient WHERE id_medecin = :medecin_id ORDER BY nom, prenom";
$stmt = $db->prepare($query);
$stmt->bindParam(':medecin_id', $_SESSION['user_id']);
$stmt->execute();
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement de l'envoi de message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['envoyer'])) {
    $message->contenu = $_POST['contenu'];
    $message->sender_id = $_SESSION['user_id'];
    $message->receiver_id = $_POST['receiver_id'];
    $message->sender_type = 'medecin';
    $message->image_url = null; // Par défaut, pas d'image
    
    // Vérifier si une image a été uploadée
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = uploadMessageImage($_FILES['image']);
        
        if ($uploadResult['success']) {
            $message->image_url = $uploadResult['image_url'];
        } else {
            $error = $uploadResult['message'];
        }
    }
    
    // Si pas d'erreur d'upload, envoyer le message
    if (!isset($error)) {
        if ($message->envoyer()) {
            $success = "Message envoyé avec succès !";
        } else {
            $error = "Erreur lors de l'envoi du message.";
        }
    }
}

// Récupérer les messages si un patient est sélectionné
$conversation = null;
if (isset($_GET['patient_id'])) {
    $stmt = $message->getConversation($_SESSION['user_id'], $_GET['patient_id']);
    $conversation = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Marquer les messages comme lus
    foreach ($conversation as $msg) {
        if ($msg['receiver_id'] == $_SESSION['user_id'] && $msg['lu'] == 0) {
            $message->marquerCommeLu($msg['id']);
        }
    }
}

// Patient sélectionné
$selected_patient = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : ($patients[0]['id'] ?? null);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie - MedConnect</title>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php include_once '../../views/components/styles.php'; ?>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
            background-image: 
                radial-gradient(circle at 20% 35%, rgba(34, 197, 94, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 75% 80%, rgba(14, 165, 233, 0.03) 0%, transparent 50%);
        }
        
        .message-bubble {
            position: relative;
            transition: all 0.3s ease;
        }
        
        .message-bubble:hover {
            transform: translateY(-2px);
        }
        
        .message-bubble::before {
            content: '';
            position: absolute;
            bottom: -8px;
            width: 0;
            height: 0;
            border-style: solid;
        }
        
        .message-bubble.sent::before {
            right: 10px;
            border-width: 8px 0 0 8px;
            border-color: transparent transparent transparent theme('colors.primary.600');
        }
        
        .message-bubble.received::before {
            left: 10px;
            border-width: 8px 8px 0 0;
            border-color: white transparent transparent transparent;
        }

        .patient-list-item {
            transition: all 0.3s ease;
        }

        .patient-list-item:hover {
            background-color: theme('colors.primary.50');
            transform: translateX(5px);
        }

        .patient-list-item.active {
            background-color: theme('colors.primary.100');
            border-left: 4px solid theme('colors.primary.500');
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
            border-left-color: theme('colors.primary.500');
        }
        
        .notification.error {
            background: linear-gradient(to right, rgba(254, 242, 242, 1), rgba(254, 242, 242, 0.8));
            border-left-color: #ef4444;
        }
        
        @keyframes slideIn {
            0% { transform: translateX(100%); opacity: 0; }
            100% { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes fadeOut {
            0% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; }
        }

        .message-input {
            transition: all 0.3s ease;
        }

        .message-input:focus {
            box-shadow: 0 0 0 2px rgba(46, 125, 50, 0.2);
        }

        .nav-link {
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            background-color: rgba(46, 125, 50, 0.1);
            transform: translateX(5px);
        }
        
        .nav-link.active {
            background-color: rgba(46, 125, 50, 0.2);
            border-left: 4px solid #2E7D32;
        }

        .chat-bubble {
            max-width: 70%;
            padding: 0.75rem 1rem;
            border-radius: 1.25rem;
            margin-bottom: 0.5rem;
            display: inline-block;
            word-break: break-word;
        }
        .bubble-me {
            background: #1e40af;
            color: white;
            border-bottom-right-radius: 0.25rem;
            margin-left: auto;
        }
        .bubble-them {
            background: #e5e7eb;
            color: #1e293b;
            border-bottom-left-radius: 0.25rem;
            margin-right: auto;
        }
        .chat-container {
            height: 60vh;
            overflow-y: auto;
            padding: 1rem;
            background: #f1f5f9;
        }
        
        /* Styles pour la barre latérale */
        .glass {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
        }
        
        .sidebar-logo {
            background: linear-gradient(135deg, #22c55e, #0ea5e9);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-family: 'Montserrat', sans-serif;
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
                <a href="messages.php" class="nav-link active flex items-center px-4 py-3 text-slate-700">
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
            <header class="bg-white shadow-sm">
                <div class="container mx-auto px-4 py-4 flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#2E7D32] to-[#81C784] flex items-center justify-center">
                            <i class="fas fa-user-md text-white text-xl"></i>
                        </div>
                        <h1 class="text-2xl font-bold text-[#1B5E20]">Messagerie</h1>
                    </div>
                    <div class="text-sm text-[#558B2F]">
                        <i class="fas fa-calendar-alt mr-2"></i><?php echo date('d/m/Y'); ?>
                    </div>
                </div>
            </header>

            <!-- Contenu principal -->
            <main class="container mx-auto px-4 py-8">
                <?php if (isset($success)): ?>
                    <div class="relative">
                        <div class="glass-card p-4 mb-6 rounded-lg shadow-md border-l-4 border-l-green-500 bg-gradient-to-r from-green-50 to-transparent" role="alert" data-aos="fade-up">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full bg-green-100 text-green-500 flex items-center justify-center">
                                        <i class="fas fa-check text-lg"></i>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="font-medium text-gray-800"><?php echo $success; ?></p>
                                </div>
                                <div class="ml-auto">
                                    <button type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none" onclick="this.parentElement.parentElement.parentElement.remove()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="relative">
                        <div class="glass-card p-4 mb-6 rounded-lg shadow-md border-l-4 border-l-red-500 bg-gradient-to-r from-red-50 to-transparent" role="alert" data-aos="fade-up">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full bg-red-100 text-red-500 flex items-center justify-center">
                                        <i class="fas fa-exclamation text-lg"></i>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="font-medium text-gray-800"><?php echo $error; ?></p>
                                </div>
                                <div class="ml-auto">
                                    <button type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none" onclick="this.parentElement.parentElement.parentElement.remove()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="bg-white rounded-xl shadow-lg overflow-hidden glass-effect">
                    <div class="grid grid-cols-12">
                        <!-- Liste des patients -->
                        <div class="col-span-4 border-r border-gray-200">
                            <div class="p-4 bg-primary-50">
                                <h2 class="text-lg font-semibold text-primary-800">
                                    <i class="fas fa-users mr-2"></i>Patients
                                </h2>
                            </div>
                            <div class="overflow-y-auto h-[600px]">
                                <?php foreach ($patients as $patient): ?>
                                    <a href="?patient_id=<?php echo $patient['id']; ?>" 
                                       class="patient-list-item block p-4 border-b border-gray-100 <?php echo (isset($_GET['patient_id']) && $_GET['patient_id'] == $patient['id']) ? 'active' : ''; ?>">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0">
                                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-500 to-primary-400 flex items-center justify-center shadow-sm">
                                                        <i class="fas fa-user text-white"></i>
                                                    </div>
                                                </div>
                                                <div class="ml-3">
                                                    <p class="text-sm font-medium text-primary-800">
                                                        <?php echo htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Zone de conversation -->
                        <div class="col-span-8">
                            <?php if (isset($_GET['patient_id'])): ?>
                                <!-- En-tête de la conversation -->
                                <div class="p-4 bg-gradient-to-r from-primary-50 to-transparent border-b border-gray-200 flex items-center">
                                    <?php 
                                    // Récupérer les informations du patient sélectionné
                                    $patient_id = $_GET['patient_id'];
                                    $patient_info = null;
                                    foreach ($patients as $p) {
                                        if ($p['id'] == $patient_id) {
                                            $patient_info = $p;
                                            break;
                                        }
                                    }
                                    ?>
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-500 to-primary-400 flex items-center justify-center shadow-md">
                                        <i class="fas fa-user text-white"></i>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="font-semibold text-gray-800">
                                            <?php echo htmlspecialchars($patient_info['nom'] . ' ' . $patient_info['prenom']); ?>
                                        </h3>
                                    </div>
                                </div>
                                
                                <!-- Messages -->
                                <div class="h-[450px] overflow-y-auto p-4 bg-gradient-to-b from-primary-50/30 to-white" id="message-container">
                                    <?php if ($conversation): ?>
                                        <?php foreach ($conversation as $msg): ?>
                                            <div class="mb-4 <?php echo $msg['sender_id'] == $_SESSION['user_id'] ? 'text-right' : ''; ?>">
                                                <div class="message-bubble inline-block max-w-[70%] rounded-2xl p-4 shadow-sm <?php echo $msg['sender_id'] == $_SESSION['user_id'] ? 'sent bg-gradient-to-r from-primary-500 to-primary-600 text-white' : 'received bg-white border border-gray-100 text-gray-800'; ?>">
                                                    <?php if (!empty($msg['contenu'])): ?>
                                                        <p class="text-sm leading-relaxed"><?php echo htmlspecialchars($msg['contenu']); ?></p>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($msg['image_url'])): ?>
                                                        <div class="mt-2 <?php echo !empty($msg['contenu']) ? 'pt-2 border-t border-<?php echo $msg["sender_id"] == $_SESSION["user_id"] ? "primary-400" : "gray-200"; ?>' : ''; ?>">
                                                            <img src="<?php echo htmlspecialchars($msg['image_url']); ?>" alt="Image" class="rounded-lg max-w-full max-h-64 cursor-pointer hover:opacity-90 transition-opacity" onclick="openImageModal('<?php echo htmlspecialchars($msg['image_url']); ?>')">
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <p class="text-xs mt-2 flex items-center justify-end <?php echo $msg['sender_id'] == $_SESSION['user_id'] ? 'text-primary-100' : 'text-gray-400'; ?>">
                                                        <i class="fas fa-clock mr-1 text-xs"></i>
                                                        <?php echo date('d/m/Y H:i', strtotime($msg['date_envoi'])); ?>
                                                        <?php if ($msg['sender_id'] == $_SESSION['user_id']): ?>
                                                            <i class="fas fa-check-double ml-1 <?php echo $msg['lu'] ? 'text-blue-300' : ''; ?>"></i>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="flex flex-col items-center justify-center h-full text-gray-500">
                                            <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                                                <i class="fas fa-comment-slash text-gray-400 text-3xl"></i>
                                            </div>
                                            <p class="text-center">Aucun message dans cette conversation.<br>Envoyez un message pour commencer.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Formulaire d'envoi -->
                                <div class="border-t border-gray-200 p-4 bg-white">
                                    <form method="POST" class="flex flex-col gap-3" id="message-form" enctype="multipart/form-data">
                                        <input type="hidden" name="receiver_id" value="<?php echo $_GET['patient_id']; ?>">
                                        <div class="relative flex-1">
                                            <textarea name="contenu" rows="1" class="message-input w-full border-2 border-gray-200 rounded-xl px-5 py-3 focus:outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100 pr-12" placeholder="Écrivez votre message..."></textarea>
                                            <div class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                                                <i class="fas fa-smile hover:text-primary-500 cursor-pointer"></i>
                                            </div>
                                        </div>
                                        
                                        <!-- Zone de prévisualisation de l'image -->
                                        <div id="image-preview" class="hidden mb-2 relative">
                                            <img src="" alt="Aperçu de l'image" class="max-h-32 rounded-lg shadow-sm">
                                            <button type="button" id="remove-image" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center shadow-sm hover:bg-red-600 transition-colors">
                                                <i class="fas fa-times text-xs"></i>
                                            </button>
                                        </div>
                                        
                                        <div class="flex items-center gap-3">
                                            <!-- Bouton d'upload d'image -->
                                            <label for="image-upload" class="bg-primary-100 hover:bg-primary-200 text-primary-700 rounded-full w-12 h-12 flex items-center justify-center shadow-md hover:shadow-lg transition-all duration-300 cursor-pointer">
                                                <i class="fas fa-image"></i>
                                                <input type="file" id="image-upload" name="image" accept="image/*" class="hidden">
                                            </label>
                                            
                                            <!-- Bouton d'envoi -->
                                            <button type="submit" name="envoyer" class="bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white rounded-full w-12 h-12 flex items-center justify-center shadow-md hover:shadow-lg transition-all duration-300 ml-auto">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="h-[600px] flex flex-col items-center justify-center text-[#558B2F]">
                                    <i class="fas fa-comments text-6xl mb-4"></i>
                                    <p class="text-lg">Sélectionnez un patient pour commencer la conversation</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Auto-resize textarea
        const textarea = document.querySelector('textarea');
        if (textarea) {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        }

        // Scroll to bottom of messages
        const messagesContainer = document.querySelector('.overflow-y-auto');
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Auto-scroll to bottom when new messages arrive
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
            });
        });

        if (messagesContainer) {
            observer.observe(messagesContainer, { childList: true });
        }
    </script>
    <!-- Modal d'affichage d'image en plein écran -->
    <div id="image-modal" class="fixed inset-0 bg-black bg-opacity-80 z-50 hidden flex items-center justify-center p-4">
        <div class="relative max-w-4xl max-h-full">
            <button id="close-image-modal" class="absolute top-2 right-2 bg-white rounded-full w-8 h-8 flex items-center justify-center shadow-md hover:bg-gray-200 transition-colors z-10">
                <i class="fas fa-times"></i>
            </button>
            <img id="modal-image" src="" alt="Image en plein écran" class="max-h-[90vh] max-w-full rounded-lg shadow-2xl">
        </div>
    </div>

    <script>
        // Gestion de la prévisualisation d'image
        const imageUpload = document.getElementById('image-upload');
        const imagePreview = document.getElementById('image-preview');
        const previewImage = imagePreview.querySelector('img');
        const removeImageBtn = document.getElementById('remove-image');
        
        // Modal d'image
        const imageModal = document.getElementById('image-modal');
        const modalImage = document.getElementById('modal-image');
        const closeImageModal = document.getElementById('close-image-modal');
        
        // Fonction pour ouvrir le modal d'image
        function openImageModal(imageUrl) {
            modalImage.src = imageUrl;
            imageModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Empêcher le défilement
        }
        
        // Événement pour fermer le modal
        closeImageModal.addEventListener('click', function() {
            imageModal.classList.add('hidden');
            document.body.style.overflow = ''; // Rétablir le défilement
        });
        
        // Fermer le modal en cliquant en dehors de l'image
        imageModal.addEventListener('click', function(e) {
            if (e.target === imageModal) {
                imageModal.classList.add('hidden');
                document.body.style.overflow = '';
            }
        });
        
        // Prévisualisation de l'image sélectionnée
        if (imageUpload) {
            imageUpload.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                        imagePreview.classList.remove('hidden');
                    };
                    
                    reader.readAsDataURL(file);
                }
            });
        }
        
        // Supprimer l'image sélectionnée
        if (removeImageBtn) {
            removeImageBtn.addEventListener('click', function() {
                imageUpload.value = ''; // Réinitialiser l'input file
                imagePreview.classList.add('hidden');
                previewImage.src = '';
            });
        }
    </script>
</body>
</html>