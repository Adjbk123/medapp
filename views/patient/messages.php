<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
require_once '../../models/Message.php';
require_once '../../includes/upload_image.php';

requireLogin();
requireRole('patient');

$user_id = $_SESSION['user_id'];
$nom = $_SESSION['nom'] ?? '';
$prenom = $_SESSION['prenom'] ?? '';
$database = new Database();
$db = $database->getConnection();
$message = new Message($db);

// Traitement de l'envoi de message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['envoyer'])) {
    $message->contenu = $_POST['contenu'];
    $message->sender_id = $_SESSION['user_id'];
    $message->receiver_id = $_POST['receiver_id'];
    $message->sender_type = 'patient';
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
        if (!$message->envoyer()) {
            $error = "Erreur lors de l'envoi du message.";
        }
    }
}

// Liste des médecins avec qui le patient a eu un rendez-vous
$stmt = $db->prepare("SELECT DISTINCT m.id, m.nom, m.prenom FROM medecin m JOIN rendezvous r ON m.id = r.idmedecin WHERE r.idpatient = ? ORDER BY m.nom, m.prenom");
$stmt->execute([$user_id]);
$medecins = $stmt->fetchAll(PDO::FETCH_ASSOC);
$selected_medecin = isset($_GET['medecin_id']) ? (int)$_GET['medecin_id'] : ($medecins[0]['id'] ?? null);

// Compter les messages non lus pour chaque médecin
$unread_counts = [];
foreach ($medecins as $medecin) {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND sender_id = ? AND sender_type = 'medecin' AND lu = 0");
    $stmt->execute([$user_id, $medecin['id']]);
    $unread_counts[$medecin['id']] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

// Récupérer les messages si un médecin est sélectionné
$conversation = null;
if ($selected_medecin) {
    // Récupérer la conversation entre le patient et le médecin sélectionné
    $stmt = $db->prepare("
        SELECT m.id, m.contenu, m.image_url, m.date_envoi, m.sender_id, m.receiver_id, m.sender_type, m.lu
        FROM messages m
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.date_envoi ASC
    ");
    $stmt->execute([$user_id, $selected_medecin, $selected_medecin, $user_id]);
    $conversation = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Marquer les messages comme lus
    $stmt = $db->prepare("UPDATE messages SET lu = 1 WHERE receiver_id = ? AND sender_id = ? AND sender_type = 'medecin'");
    $stmt->execute([$user_id, $selected_medecin]);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie - MedConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(to bottom right, #EFF6FF, #DBEAFE);
            margin: 0;
            padding: 0;
        }
        .container {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        .sidebar {
            width: 300px;
            background: white;
            color: #1e40af;
            padding: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            z-index: 1000;
        }
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: white;
            border-radius: 20px 0 0 20px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin: 20px;
            margin-left: 0;
        }
        .header {
            padding: 20px;
            border-bottom: 1px solid #eaeaea;
            display: flex;
            align-items: center;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .header h2 {
            margin: 0;
            font-size: 1.2rem;
            color: #1e40af;
            font-weight: 600;
        }
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e0e7ff;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #4f46e5;
        }
        .chat-container {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
            background-color: #F9FAFB;
        }
        .chat-bubble {
            max-width: 80%;
            padding: 12px 16px;
            border-radius: 16px;
            position: relative;
            animation: fadeIn 0.3s ease-in-out;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            margin-bottom: 8px;
        }
        .bubble-me {
            align-self: flex-end;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border-bottom-right-radius: 4px;
        }
        .bubble-them {
            align-self: flex-start;
            background-color: white;
            color: #1e293b;
            border-bottom-left-radius: 4px;
            border: 1px solid #e5e7eb;
        }
        .message-status {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.7rem;
            margin-top: 5px;
            opacity: 0.8;
        }
        .bubble-me .message-status {
            color: rgba(255, 255, 255, 0.8);
        }
        .bubble-them .message-status {
            color: #9ca3af;
        }
        .input-container {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            background-color: white;
            border-top: 1px solid #eaeaea;
        }
        .input-container input {
            flex: 1;
            padding: 12px 20px;
            border: 1px solid #e0e7ff;
            border-radius: 24px;
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
        }
        .input-container input:focus {
            border-color: #818cf8;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
        }
        .send-button {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #3b82f6;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }
        .send-button:hover {
            background-color: #2563eb;
            transform: scale(1.05);
        }
        .send-button:disabled {
            background: #93c5fd;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }
        .image-button {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e0e7ff;
            color: #4f46e5;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .image-button:hover {
            background-color: #c7d2fe;
            transform: scale(1.05);
        }
        #image-preview {
            background-color: #f9fafb;
            border-top: 1px solid #e5e7eb;
            padding: 10px;
            text-align: center;
        }
        #image-preview img {
            max-width: 100%;
            max-height: 150px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 15px;
        }
        .sidebar-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: #1e40af;
        }
        .search-container {
            position: relative;
            margin-bottom: 20px;
        }
        .search-container input {
            width: 100%;
            padding: 10px 15px 10px 35px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background-color: #f9fafb;
            color: #1e293b;
            outline: none;
            transition: all 0.3s;
        }
        .search-container input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }
        .search-container input::placeholder {
            color: #9ca3af;
        }
        .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
        }
        .contact-list {
            list-style: none;
            padding: 0;
            margin: 0;
            overflow-y: auto;
            flex: 1;
        }
        .contact-list li {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            border-left: 3px solid transparent;
        }
        .contact-list li:hover {
            background-color: #f1f5f9;
            transform: translateX(3px);
        }
        .contact-list li.active {
            background-color: #eff6ff;
            border-left-color: #3b82f6;
            font-weight: 600;
        }
        .contact-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e0e7ff;
            margin-right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #4f46e5;
            box-shadow: 0 2px 4px rgba(79, 70, 229, 0.2);
        }
        .contact-info {
            flex: 1;
        }
        .contact-name {
            font-weight: 500;
            margin-bottom: 3px;
            color: #1e293b;
        }
        .contact-status {
            font-size: 0.75rem;
            color: #64748b;
        }
        .unread-badge {
            background-color: #3b82f6;
            color: white;
            border-radius: 9999px;
            min-width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
        }
        .loading, .typing {
            display: none;
            align-items: center;
            padding: 10px;
            margin-bottom: 10px;
            color: #6b7280;
            font-size: 0.85rem;
        }
        .loading.active, .typing.active {
            display: flex;
        }
        .loading i, .typing i {
            margin-right: 8px;
        }
        .dot-typing {
            position: relative;
            left: -9999px;
            width: 6px;
            height: 6px;
            border-radius: 5px;
            background-color: #6b7280;
            color: #6b7280;
            box-shadow: 9984px 0 0 0 #6b7280, 9994px 0 0 0 #6b7280, 10004px 0 0 0 #6b7280;
            animation: dotTyping 1.5s infinite linear;
        }
        
        /* Séparateurs de date */
        .chat-date-divider {
            display: flex;
            align-items: center;
            margin: 15px 0;
            text-align: center;
            color: #64748b;
            font-size: 0.8rem;
            position: relative;
        }
        
        .chat-date-divider::before,
        .chat-date-divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .chat-date-divider span {
            margin: 0 10px;
            padding: 2px 8px;
            background-color: #f8fafc;
            border-radius: 10px;
            font-weight: 500;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }
        
        /* État vide */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 0;
            text-align: center;
            color: #6b7280;
        }
        
        .empty-state-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #eff6ff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }
        
        .empty-state-icon i {
            font-size: 24px;
            color: #3b82f6;
        }
        
        @keyframes dotTyping {
            0% {
                box-shadow: 9984px 0 0 0 #6b7280, 9994px 0 0 0 #6b7280, 10004px 0 0 0 #6b7280;
            }
            16.667% {
                box-shadow: 9984px -5px 0 0 #6b7280, 9994px 0 0 0 #6b7280, 10004px 0 0 0 #6b7280;
            }
            33.333% {
                box-shadow: 9984px 0 0 0 #6b7280, 9994px 0 0 0 #6b7280, 10004px 0 0 0 #6b7280;
            }
            50% {
                box-shadow: 9984px 0 0 0 #6b7280, 9994px -5px 0 0 #6b7280, 10004px 0 0 0 #6b7280;
            }
            66.667% {
                box-shadow: 9984px 0 0 0 #6b7280, 9994px 0 0 0 #6b7280, 10004px 0 0 0 #6b7280;
            }
            83.333% {
                box-shadow: 9984px 0 0 0 #6b7280, 9994px 0 0 0 #6b7280, 10004px -5px 0 0 #6b7280;
            }
            100% {
                box-shadow: 9984px 0 0 0 #6b7280, 9994px 0 0 0 #6b7280, 10004px 0 0 0 #6b7280;
            }
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Overlay pour mobile */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(2px);
            z-index: 999;
            transition: opacity 0.3s ease;
            opacity: 0;
        }
        
        .overlay.active {
            display: block;
            opacity: 1;
        }
        
        /* Bouton de fermeture pour mobile */
        .close-sidebar {
            display: none;
            background: none;
            border: none;
            color: #1e40af;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .close-sidebar:hover {
            transform: rotate(90deg);
            color: #3b82f6;
        }
        
        /* Glass effect */
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.05);
        }
        
        /* Animation pulse pour notifications */
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
            100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                width: 80%;
                max-width: 300px;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            }
            .sidebar.active {
                transform: translateX(0);
                display: flex;
            }
            .close-sidebar {
                display: block;
                position: absolute;
                top: 20px;
                right: 20px;
            }
            .main-content {
                border-radius: 0;
                margin: 0;
                height: 100vh;
            }
            .header {
                padding: 15px;
            }
            .menu-toggle {
                display: block;
                background: none;
                border: none;
                font-size: 1.2rem;
                margin-right: 10px;
                color: #1e40af;
                cursor: pointer;
                transition: transform 0.3s ease;
            }
            .menu-toggle:hover {
                transform: rotate(90deg);
            }
            .chat-bubble {
                max-width: 90%;
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-[#EFF6FF] to-[#DBEAFE] min-h-screen">
    <!-- Overlay pour mobile -->
    <div id="overlay" class="overlay"></div>
    
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar glass-effect">
            <div class="sidebar-header">
                <h2>Messages</h2>
                <button id="closeSidebar" class="close-sidebar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="flex items-center mb-6">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#3b82f6] to-[#60a5fa] flex items-center justify-center mr-3">
                    <i class="fas fa-heartbeat text-white"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-[#1e40af]">MedConnect</h1>
                    <p class="text-xs text-[#64748b]">Messagerie</p>
                </div>
            </div>
            
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchContact" placeholder="Rechercher un médecin...">
            </div>
            
            <h3 class="text-sm uppercase text-[#64748b] font-semibold tracking-wider mb-3">Mes Médecins</h3>
            <ul class="contact-list">
                <?php if (empty($medecins)): ?>
                    <li class="text-sm text-gray-500">Aucun médecin disponible</li>
                <?php else: ?>
                    <?php foreach ($medecins as $m): ?>
                        <li class="<?php echo ($selected_medecin == $m['id']) ? 'active' : ''; ?>">
                            <a href="?medecin_id=<?php echo $m['id']; ?>" class="flex items-center w-full">
                                <div class="contact-avatar">
                                    <?php echo strtoupper(substr($m['prenom'], 0, 1) . substr($m['nom'], 0, 1)); ?>
                                </div>
                                <div class="contact-info">
                                    <div class="contact-name">Dr. <?php echo htmlspecialchars($m['prenom'] . ' ' . $m['nom']); ?></div>
                                    <div class="contact-status">Médecin</div>
                                </div>
                                <?php if (!empty($unread_counts[$m['id']])): ?>
                                    <span class="unread-badge pulse"><?php echo $unread_counts[$m['id']]; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
            
            <div class="mt-auto pt-4">
                <a href="dashboard.php" class="flex items-center p-3 rounded-lg hover:bg-[#f1f5f9] transition text-[#1e40af]">
                    <i class="fas fa-home mr-3"></i>
                    Tableau de bord
                </a>
                <a href="rdv.php" class="flex items-center p-3 rounded-lg hover:bg-[#f1f5f9] transition text-[#1e40af]">
                    <i class="fas fa-calendar-alt mr-3"></i>
                    Mes Rendez-vous
                </a>
                <a href="ordonnace.php" class="flex items-center p-3 rounded-lg hover:bg-[#f1f5f9] transition text-[#1e40af]">
                    <i class="fas fa-prescription mr-3"></i>
                    Mes Ordonnances
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <?php if ($selected_medecin): 
                $stmt = db()->prepare("SELECT nom, prenom FROM medecin WHERE id = ?");
                $stmt->execute([$selected_medecin]);
                $medecin = $stmt->fetch();
                $initials = strtoupper(substr($medecin['prenom'], 0, 1) . substr($medecin['nom'], 0, 1));
            ?>
            <div class="header">
                <button id="menuToggle" class="menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="avatar">
                    <?= $initials ?>
                </div>
                <div>
                    <h2>Dr. <?= htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']) ?></h2>
                    <div class="text-sm text-gray-500">Médecin</div>
                </div>
            </div>
            
            <div id="chat" class="chat-container">
                <?php if ($conversation): ?>
                    <?php 
                    $lastDate = null;
                    foreach ($conversation as $msg): 
                        // Format de la date pour les séparateurs
                        $msgDate = date('Y-m-d', strtotime($msg['date_envoi']));
                        
                        // Afficher un séparateur de date si nécessaire
                        if ($lastDate !== $msgDate): 
                            $lastDate = $msgDate;
                            $today = date('Y-m-d');
                            $yesterday = date('Y-m-d', strtotime('-1 day'));
                            
                            if ($msgDate === $today) {
                                $dateDisplay = "Aujourd'hui";
                            } elseif ($msgDate === $yesterday) {
                                $dateDisplay = "Hier";
                            } else {
                                $dateDisplay = date('d/m/Y', strtotime($msg['date_envoi']));
                            }
                    ?>
                        <div class="date-divider">
                            <span><?php echo $dateDisplay; ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php 
                        $isMe = $msg['sender_type'] === 'patient';
                    ?>
                    <div class="chat-bubble <?php echo $isMe ? 'bubble-me' : 'bubble-them'; ?>">
                        <?php if (!empty($msg['contenu'])): ?>
                            <div><?php echo htmlspecialchars($msg['contenu']); ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($msg['image_url'])): ?>
                            <div class="<?php echo !empty($msg['contenu']) ? 'mt-2 pt-2 border-t border-gray-200' : ''; ?>">
                                <img src="<?php echo htmlspecialchars($msg['image_url']); ?>" alt="Image" class="rounded-lg max-w-full max-h-64 cursor-pointer hover:opacity-90 transition-opacity" onclick="openImageModal('<?php echo htmlspecialchars($msg['image_url']); ?>')">
                            </div>
                        <?php endif; ?>
                        
                        <div class="message-status">
                            <span><?php echo date('H:i', strtotime($msg['date_envoi'])); ?></span>
                            <?php if ($isMe): ?>
                                <i class="fas fa-<?php echo $msg['lu'] ? 'check-double text-blue-400' : 'check'; ?>"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center h-full">
                        <div class="w-16 h-16 rounded-full bg-[#EFF6FF] flex items-center justify-center mb-3">
                            <i class="fas fa-comments text-2xl text-[#3b82f6]"></i>
                        </div>
                        <p class="text-gray-500 font-medium">Aucun message</p>
                        <p class="text-gray-400 text-sm mt-1">Commencez la conversation en envoyant un message</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div id="typing" class="typing">
                <i class="fas fa-keyboard"></i> 
                <div class="ml-2">Le médecin est en train d'écrire</div>
                <div class="dot-typing ml-2"></div>
            </div>
            
            <!-- Zone de prévisualisation de l'image -->
            <div id="image-preview" class="hidden p-3 bg-white border-t border-gray-200">
                <div class="relative inline-block">
                    <img src="" alt="Aperçu de l'image" class="max-h-32 rounded-lg shadow-sm">
                    <button type="button" id="remove-image" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center shadow-sm hover:bg-red-600 transition-colors">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            </div>
            
            <!-- Formulaire d'envoi de message standard -->
            <form method="POST" class="input-container" enctype="multipart/form-data">
                <input type="hidden" name="receiver_id" value="<?php echo $selected_medecin; ?>">
                <label for="image-upload" class="image-button">
                    <i class="fas fa-image"></i>
                    <input type="file" id="image-upload" name="image" accept="image/*" class="hidden">
                </label>
                <input type="text" name="contenu" id="contenu" placeholder="Écrivez votre message...">
                <button type="submit" name="envoyer" class="send-button">
                    <i class="fas fa-paper-plane"></i>
                </button>
                
                <?php if (isset($success)): ?>
                <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
            </form>
            <?php else: ?>
            <div class="header">
                <button id="menuToggle" class="menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h2>Messages</h2>
            </div>
            
            <div class="flex flex-col items-center justify-center h-full p-6 text-center">
                <div class="w-20 h-20 rounded-full bg-blue-100 flex items-center justify-center mb-4">
                    <i class="fas fa-comments text-blue-500 text-2xl"></i>
                </div>
                <h2 class="text-xl font-semibold mb-2">Vos messages</h2>
                <p class="text-gray-500 max-w-md">Sélectionnez un médecin dans la liste pour commencer ou continuer une conversation.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
    // Éléments DOM
    const chat = document.getElementById('chat');
    const form = document.getElementById('sendForm');
    const contenu = document.getElementById('contenu');
    const receiver_id = document.getElementById('receiver_id')?.value;
    const loading = document.getElementById('loading');
    const typing = document.getElementById('typing');
    const sendButton = document.getElementById('sendButton');
    const menuToggle = document.getElementById('menuToggle');
    const closeSidebar = document.getElementById('closeSidebar');
    const overlay = document.getElementById('overlay');
    const sidebar = document.querySelector('.sidebar');
    const searchContact = document.getElementById('searchContact');
    
    let isTyping = false;
    let typingTimeout;
    let lastDate = null;
    
    // Fonctions utilitaires
    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const yesterday = new Date(now);
        yesterday.setDate(yesterday.getDate() - 1);
        
        // Même jour
        if (date.toDateString() === now.toDateString()) {
            return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        }
        // Hier
        else if (date.toDateString() === yesterday.toDateString()) {
            return `Hier, ${date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}`;
        }
        // Cette semaine
        else if (now.getTime() - date.getTime() < 7 * 24 * 60 * 60 * 1000) {
            const options = { weekday: 'long', hour: '2-digit', minute: '2-digit' };
            return date.toLocaleDateString('fr-FR', options);
        }
        // Plus ancien
        else {
            const options = { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' };
            return date.toLocaleDateString('fr-FR', options);
        }
    }

    function shouldShowDateDivider(dateString) {
        if (!dateString) return false;
        
        const date = new Date(dateString).toLocaleDateString('fr-FR');
        if (lastDate !== date) {
            lastDate = date;
            return true;
        }
        return false;
    }
    
    function createDateDivider(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const yesterday = new Date(now);
        yesterday.setDate(yesterday.getDate() - 1);
        
        let displayDate;
        if (date.toDateString() === now.toDateString()) {
            displayDate = "Aujourd'hui";
        } else if (date.toDateString() === yesterday.toDateString()) {
            displayDate = "Hier";
        } else {
            displayDate = date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' });
        }
        
        const divider = document.createElement('div');
        divider.className = 'chat-date-divider';
        divider.innerHTML = `<span>${displayDate}</span>`;
        return divider;
    }

    function showLoading() {
        if (loading) loading.classList.add('active');
    }

    function hideLoading() {
        if (loading) loading.classList.remove('active');
    }

    function showTyping() {
        if (!typing) return;
        
        if (!isTyping) {
            isTyping = true;
            typing.classList.add('active');
        }
        clearTimeout(typingTimeout);
        typingTimeout = setTimeout(() => {
            isTyping = false;
            typing.classList.remove('active');
        }, 3000);
    }
    
    // Fonction pour ajouter un nouveau message (envoyé par l'utilisateur)
    function addMessage(msg) {
        // Ajouter un séparateur de date si nécessaire
        if (shouldShowDateDivider(msg.date_envoi)) {
            chat.appendChild(createDateDivider(msg.date_envoi));
        }
        
        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble bubble-me';
        bubble.dataset.messageId = msg.id;
        
        let contentHtml = '';
        
        // Ajouter le contenu texte s'il existe
        if (msg.contenu && msg.contenu.trim() !== '') {
            contentHtml += `<div>${escapeHtml(msg.contenu)}</div>`;
        }
        
        // Ajouter l'image s'il y en a une
        if (msg.image_url) {
            const borderClass = msg.contenu && msg.contenu.trim() !== '' ? 'mt-2 pt-2 border-t border-gray-200' : '';
            contentHtml += `
                <div class="${borderClass}">
                    <img src="${escapeHtml(msg.image_url)}" alt="Image" class="rounded-lg max-w-full max-h-64 cursor-pointer hover:opacity-90 transition-opacity" onclick="openImageModal('${escapeHtml(msg.image_url)}')">
                </div>
            `;
        }
        
        // Ajouter le statut du message
        contentHtml += `
            <div class="message-status">
                <span>${formatDate(msg.date_envoi)}</span>
                ${msg.lu ? '<i class="fas fa-check-double text-blue-400"></i>' : '<i class="fas fa-check"></i>'}
            </div>
        `;
        
        bubble.innerHTML = contentHtml;
        chat.appendChild(bubble);
        scrollToBottom();
    }

    // Chargement des messages
    function loadMessages() {
        if (!receiver_id || !chat) return;
        
        showLoading();
        fetch('../../api/messages.php?other_id=' + receiver_id)
            .then(res => res.json())
            .then(response => {
                chat.innerHTML = '';
                lastDate = null; // Réinitialiser pour les séparateurs de date
                
                // Vérifier si la réponse est au nouveau format
                const messages = response.success && response.data ? response.data : response;
                
                if (messages.length === 0) {
                    const emptyState = document.createElement('div');
                    emptyState.className = 'flex flex-col items-center justify-center py-8 text-center';
                    emptyState.innerHTML = `
                        <div class="w-16 h-16 rounded-full bg-[#EFF6FF] flex items-center justify-center mb-3">
                            <i class="fas fa-comments text-2xl text-[#3b82f6]"></i>
                        </div>
                        <p class="text-gray-500 font-medium">Aucun message</p>
                        <p class="text-gray-400 text-sm mt-1">Commencez la conversation en envoyant un message</p>
                    `;
                    chat.appendChild(emptyState);
                } else {
                    messages.forEach(msg => {
                        // Ajouter un séparateur de date si nécessaire
                        if (shouldShowDateDivider(msg.date_envoi)) {
                            chat.appendChild(createDateDivider(msg.date_envoi));
                        }
                        
                        const isMe = msg.sender_type === 'patient';
                        const bubble = document.createElement('div');
                        bubble.className = 'chat-bubble ' + (isMe ? 'bubble-me' : 'bubble-them');
                        
                        let contentHtml = '';
                        
                        // Ajouter le contenu texte s'il existe
                        if (msg.contenu && msg.contenu.trim() !== '') {
                            contentHtml += `<div>${escapeHtml(msg.contenu)}</div>`;
                        }
                        
                        // Ajouter l'image s'il y en a une
                        if (msg.image_url) {
                            const borderClass = msg.contenu && msg.contenu.trim() !== '' ? 'mt-2 pt-2 border-t border-gray-200' : '';
                            contentHtml += `
                                <div class="${borderClass}">
                                    <img src="${escapeHtml(msg.image_url)}" alt="Image" class="rounded-lg max-w-full max-h-64 cursor-pointer hover:opacity-90 transition-opacity" onclick="openImageModal('${escapeHtml(msg.image_url)}')">
                                </div>
                            `;
                        }
                        
                        // Ajouter le statut du message
                        contentHtml += `
                            <div class="message-status">
                                <span>${formatDate(msg.date_envoi)}</span>
                                ${isMe ? (msg.lu ? '<i class="fas fa-check-double text-blue-400"></i>' : '<i class="fas fa-check"></i>') : ''}
                            </div>
                        `;
                        
                        bubble.innerHTML = contentHtml;
                        chat.appendChild(bubble);
                    });
                }
                
                chat.scrollTop = chat.scrollHeight;
                hideLoading();
            })
            .catch(error => {
                console.error('Erreur lors du chargement des messages:', error);
                hideLoading();
                
                // Afficher un message d'erreur dans le chat
                if (chat.innerHTML === '') {
                    chat.innerHTML = `
                        <div class="flex flex-col items-center justify-center h-full">
                            <div class="bg-red-50 p-4 rounded-lg text-red-500 max-w-md text-center">
                                <i class="fas fa-exclamation-circle text-2xl mb-2"></i>
                                <p>Une erreur est survenue lors du chargement des messages.</p>
                                <button onclick="loadMessages()" class="mt-2 text-sm underline">Réessayer</button>
                            </div>
                        </div>
                    `;
                }
            });
    }

    // Ajout de styles pour les messages de succès et d'erreur
    document.head.insertAdjacentHTML('beforeend', `
        <style>
            .success-message {
                background-color: #f0fdf4;
                border-left: 4px solid #22c55e;
                padding: 0.75rem 1rem;
                margin: 0.5rem 0;
                border-radius: 0.375rem;
                color: #166534;
                font-size: 0.875rem;
                animation: fadeOut 5s forwards;
            }
            .error-message {
                background-color: #fef2f2;
                border-left: 4px solid #ef4444;
                padding: 0.75rem 1rem;
                margin: 0.5rem 0;
                border-radius: 0.375rem;
                color: #b91c1c;
                font-size: 0.875rem;
                animation: fadeOut 5s forwards;
            }
            @keyframes fadeOut {
                0% { opacity: 1; }
                70% { opacity: 1; }
                100% { opacity: 0; }
            }
        </style>
    `);
    
    // Après l'envoi du formulaire, recharger les messages
    document.addEventListener('DOMContentLoaded', function() {
        // Faire défiler jusqu'au dernier message après le chargement de la page
        if (chat) {
            setTimeout(() => {
                chat.scrollTop = chat.scrollHeight;
            }, 100);
        }
        
        // Masquer les messages de succès et d'erreur après 5 secondes
        const successMessage = document.querySelector('.success-message');
        const errorMessage = document.querySelector('.error-message');
        
        if (successMessage) {
            setTimeout(() => {
                successMessage.style.display = 'none';
            }, 5000);
        }
        
        if (errorMessage) {
            setTimeout(() => {
                errorMessage.style.display = 'none';
            }, 5000);
        }
    });

    // Fonctionnalité de recherche de contacts
    if (searchContact) {
        searchContact.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const contacts = document.querySelectorAll('.contact-list li');
            
            contacts.forEach(contact => {
                const name = contact.textContent.toLowerCase();
                if (name.includes(query)) {
                    contact.style.display = '';
                } else {
                    contact.style.display = 'none';
                }
            });
        });
    }

    // Gestion du menu mobile
    if (menuToggle && sidebar && overlay) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.add('active');
            overlay.classList.add('active');
        });
    }
    
    if (closeSidebar && sidebar && overlay) {
        closeSidebar.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    }
    
    if (overlay && sidebar) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    }

    // Vérification du statut de frappe
    function checkTyping() {
        if (!receiver_id) return;
        fetch('../../api/check_typing.php?user_id=' + receiver_id)
            .then(res => res.json())
            .then(data => {
                // Vérifier si la réponse est au nouveau format
                const isTyping = data.success !== undefined ? data.is_typing : data.is_typing;
                
                if (isTyping) {
                    showTyping();
                }
            })
            .catch(error => {
                console.error('Erreur lors de la vérification du statut de frappe:', error);
                // Ne pas afficher l'erreur à l'utilisateur, c'est une fonctionnalité non critique
            });
    }

    // Gestion du focus et du blur sur l'input
    if (contenu && receiver_id) {
        contenu.addEventListener('focus', () => {
            fetch('../../api/typing.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    receiver_id: receiver_id,
                    is_typing: true
                })
            });
        });

        contenu.addEventListener('blur', () => {
            fetch('../../api/typing.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    receiver_id: receiver_id,
                    is_typing: false
                })
            });
        });
    }

    // Initialisation
    document.addEventListener('DOMContentLoaded', function() {
        // Charger les messages au chargement de la page
        if (receiver_id) {
            loadMessages();
            // Rafraîchissement auto des messages
            setInterval(loadMessages, 5000);
            // Vérification périodique du statut de frappe
            setInterval(checkTyping, 3000);
        }
        
        // Focus sur le champ de message
        if (contenu) {
            setTimeout(() => contenu.focus(), 500);
        }
    });
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
        const previewImage = imagePreview?.querySelector('img');
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
        if (imageUpload && imagePreview && previewImage) {
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
        if (removeImageBtn && imageUpload && imagePreview) {
            removeImageBtn.addEventListener('click', function() {
                imageUpload.value = ''; // Réinitialiser l'input file
                imagePreview.classList.add('hidden');
                previewImage.src = '';
            });
        }
    </script>
</body>
</html>