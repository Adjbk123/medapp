<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

requireLogin();
requireRole('patient');

$user_id = $_SESSION['user_id'];
$nom = $_SESSION['nom'];
$prenom = $_SESSION['prenom'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistant Médical - MedConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/chatbot.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f9ff;
            overflow-x: hidden;
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
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.05);
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
                <div class="ml-3">
                    <h1 class="text-xl font-bold text-[#1e40af]">MedConnect</h1>
                    <p class="text-xs text-gray-500">Votre santé, notre priorité</p>
                </div>
            </div>
            
            <nav class="flex-1">
                <ul class="space-y-1">
                    <li>
                        <a href="dashboard.php" class="nav-link flex items-center px-4 py-3 text-gray-700 hover:text-[#3b82f6]">
                            <i class="fas fa-home w-6"></i>
                            <span>Tableau de bord</span>
                        </a>
                    </li>
                    <li>
                        <a href="rdv.php" class="nav-link flex items-center px-4 py-3 text-gray-700 hover:text-[#3b82f6]">
                            <i class="fas fa-calendar-alt w-6"></i>
                            <span>Rendez-vous</span>
                        </a>
                    </li>
                    <li>
                        <a href="ordonnace.php" class="nav-link flex items-center px-4 py-3 text-gray-700 hover:text-[#3b82f6]">
                            <i class="fas fa-prescription w-6"></i>
                            <span>Ordonnances</span>
                        </a>
                    </li>
                    <li>
                        <a href="carnet.php" class="nav-link flex items-center px-4 py-3 text-gray-700 hover:text-[#3b82f6]">
                            <i class="fas fa-book-medical w-6"></i>
                            <span>Carnet de santé</span>
                        </a>
                    </li>
                    <li>
                        <a href="messagerie.php" class="nav-link flex items-center px-4 py-3 text-gray-700 hover:text-[#3b82f6]">
                            <i class="fas fa-envelope w-6"></i>
                            <span>Messagerie</span>
                        </a>
                    </li>
                    <li>
                        <a href="chat.php" class="nav-link active flex items-center px-4 py-3 text-[#3b82f6]">
                            <i class="fas fa-comments w-6"></i>
                            <span>Assistant Médical</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="mt-6">
                <a href="../../logout.php" class="flex items-center px-4 py-3 text-gray-700 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors duration-300">
                    <i class="fas fa-sign-out-alt w-6"></i>
                    <span>Déconnexion</span>
                </a>
            </div>
        </aside>

        <div class="flex-1">
            <!-- En-tête -->
            <header class="bg-white shadow-md sticky top-0 z-30">
                <div class="container mx-auto px-4 py-4 flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <button id="sidebarToggle" class="text-gray-500 hover:text-[#3b82f6] lg:hidden">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        <h2 class="text-xl font-semibold text-[#1e40af]">Assistant Médical</h2>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <button class="flex items-center text-gray-700 hover:text-[#3b82f6]">
                                <div class="w-10 h-10 rounded-full bg-[#EFF6FF] flex items-center justify-center">
                                    <i class="fas fa-user text-[#3b82f6]"></i>
                                </div>
                                <span class="ml-2 hidden md:block"><?php echo htmlspecialchars($prenom . ' ' . $nom); ?></span>
                                <i class="fas fa-chevron-down ml-2 text-xs hidden md:block"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <main class="container mx-auto px-4 py-8">
                <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-[#1e40af]">
                            <i class="fas fa-robot mr-2"></i>Assistant Médical
                        </h2>
                    </div>
                    
                    <div class="chatbot-container">
                        <div class="chatbot-messages" id="chatbotMessages">
                            <div class="message chatbot-message">
                                Bonjour <?php echo htmlspecialchars($prenom); ?>, je suis votre assistant médical. Comment puis-je vous aider aujourd'hui ?
                            </div>
                            <!-- Les messages s'afficheront ici -->
                        </div>
                        
                        <div class="chatbot-input">
                            <input type="text" id="userInput" placeholder="Posez votre question médicale ici..." autocomplete="off">
                            <button id="sendButton">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Menu mobile toggle
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('-translate-x-full');
            document.getElementById('overlay').classList.toggle('active');
        });

        document.getElementById('overlay').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.remove('-translate-x-full');
            this.classList.remove('active');
        });

        // Chatbot functionality
        document.addEventListener('DOMContentLoaded', function() {
            const chatbotMessages = document.getElementById('chatbotMessages');
            const userInput = document.getElementById('userInput');
            const sendButton = document.getElementById('sendButton');

            function addMessage(message, isUser = false) {
                const messageDiv = document.createElement('div');
                messageDiv.className = isUser ? 'message user-message' : 'message chatbot-message';
                messageDiv.textContent = message;
                chatbotMessages.appendChild(messageDiv);
                chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
            }

            function sendMessage() {
                const message = userInput.value.trim();
                if (message === '') return;

                // Ajouter le message de l'utilisateur
                addMessage(message, true);
                userInput.value = '';

                // Envoyer la requête au serveur
                fetch('../../chatbot/process.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'message=' + encodeURIComponent(message)
                })
                .then(response => response.json())
                .then(data => {
                    // Ajouter la réponse du chatbot
                    addMessage(data.response);
                })
                .catch(error => {
                    console.error('Error:', error);
                    addMessage('Désolé, une erreur est survenue. Veuillez réessayer plus tard.');
                });
            }

            sendButton.addEventListener('click', sendMessage);
            userInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
        });
    </script>
</body>
</html>
