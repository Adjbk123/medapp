<?php
require_once '../../includes/session.php';
require_once '../../config/config.php';

// Vérifier si l'utilisateur est connecté
requireLogin();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistant Médical Intelligent - MedConnect</title>
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?php include_once '../../views/components/styles.php'; ?>
    <style>
        .nav-link {
            transition: all 0.3s ease;
        }
        .nav-link:hover {
            background-color: rgba(77, 156, 82, 0.1);
            transform: translateX(5px);
        }
        .nav-link.active {
            background-color: rgba(77, 156, 82, 0.2);
            border-left: 4px solid #4d9c52;
        }
        
        /* Styles pour la démo du chatbot */
        .demo-card {
            transition: all 0.3s ease;
        }
        .demo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        /* Animation pour les éléments */
        .animate-fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-primary-50 to-primary-100 min-h-screen">
    <div class="min-h-screen flex">
        <!-- Barre latérale -->
        <aside class="w-64 bg-white shadow-lg flex flex-col py-6 px-4">
            <div class="flex items-center justify-start mb-12">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary-600 to-primary-300 flex items-center justify-center">
                    <i class="fas fa-heartbeat text-white text-xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-primary-800 ml-3">MedConnect</h1>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 space-y-2">
                <a href="dashboard.php" class="nav-link flex items-center px-4 py-3 text-slate-700">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3 bg-primary-100">
                        <i class="fas fa-home text-primary-600"></i>
                    </div>
                    <span>Tableau de bord</span>
                </a>
                <a href="profile.php" class="nav-link flex items-center px-4 py-3 text-slate-700">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3 bg-primary-100/50">
                        <i class="fas fa-user text-primary-600"></i>
                    </div>
                    <span>Mon Profil</span>
                </a>
                <a href="rdv.php" class="nav-link flex items-center px-4 py-3 text-slate-700">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3 bg-primary-100/50">
                        <i class="fas fa-calendar-alt text-primary-600"></i>
                    </div>
                    <span>Mes Rendez-vous</span>
                </a>
                <a href="ordonnances.php" class="nav-link flex items-center px-4 py-3 text-slate-700">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3 bg-primary-100/50">
                        <i class="fas fa-prescription text-primary-600"></i>
                    </div>
                    <span>Mes Ordonnances</span>
                </a>
                <a href="messages.php" class="nav-link flex items-center px-4 py-3 text-slate-700">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3 bg-primary-100/50">
                        <i class="fas fa-envelope text-primary-600"></i>
                    </div>
                    <span>Messages</span>
                </a>
                <a href="chatbot_analyzer.php" class="nav-link active flex items-center px-4 py-3 text-slate-700">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3 bg-primary-100/50">
                        <i class="fas fa-stethoscope text-primary-600"></i>
                    </div>
                    <span>Assistant Médical</span>
                </a>
            </nav>
            
            <div class="mt-6">
                <a href="../../logout.php" class="block bg-red-500 hover:bg-red-600 text-white text-center px-4 py-3 rounded-lg transition-colors duration-300">
                    <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                </a>
            </div>
        </aside>

        <!-- Contenu principal -->
        <div class="flex-1">
            <!-- En-tête -->
            <header class="bg-white shadow-sm">
                <div class="container mx-auto px-4 py-4 flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center">
                            <i class="fas fa-stethoscope text-white text-xl"></i>
                        </div>
                        <h1 class="text-2xl font-bold text-primary-800">Assistant Médical Intelligent</h1>
                    </div>
                </div>
            </header>

            <!-- Contenu principal -->
            <main class="container mx-auto px-4 py-8">
                <!-- Introduction à l'assistant -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-8 animate-fade-in">
                    <div class="flex items-start">
                        <div class="bg-primary-100 p-4 rounded-lg mr-6">
                            <i class="fas fa-robot text-primary-600 text-4xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-primary-800 mb-2">Votre Assistant Médical Personnel</h2>
                            <p class="text-gray-700 mb-4">
                                Notre assistant médical intelligent est conçu pour vous aider à comprendre vos symptômes et vous orienter vers les spécialistes appropriés. 
                                Il utilise une technologie d'analyse avancée pour évaluer vos symptômes et vous fournir des recommandations personnalisées.
                            </p>
                            <div class="bg-primary-50 border-l-4 border-primary-500 p-4 rounded-r-lg">
                                <p class="text-primary-800 font-medium">
                                    <i class="fas fa-info-circle mr-2"></i>Cet assistant ne remplace pas une consultation médicale. En cas d'urgence, contactez immédiatement les services d'urgence.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Exemples de symptômes -->
                <h3 class="text-lg font-semibold text-primary-800 mb-4">Exemples de symptômes que vous pouvez décrire :</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-md p-4 demo-card animate-fade-in" style="animation-delay: 0.1s">
                        <div class="flex items-center mb-3">
                            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center mr-3">
                                <i class="fas fa-heartbeat text-red-500"></i>
                            </div>
                            <h4 class="font-medium text-gray-800">Symptômes cardiovasculaires</h4>
                        </div>
                        <ul class="text-gray-600 space-y-2 pl-4">
                            <li><i class="fas fa-angle-right text-primary-500 mr-2"></i>Douleurs thoraciques</li>
                            <li><i class="fas fa-angle-right text-primary-500 mr-2"></i>Palpitations</li>
                            <li><i class="fas fa-angle-right text-primary-500 mr-2"></i>Essoufflement à l'effort</li>
                        </ul>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-md p-4 demo-card animate-fade-in" style="animation-delay: 0.2s">
                        <div class="flex items-center mb-3">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                <i class="fas fa-brain text-blue-500"></i>
                            </div>
                            <h4 class="font-medium text-gray-800">Symptômes neurologiques</h4>
                        </div>
                        <ul class="text-gray-600 space-y-2 pl-4">
                            <li><i class="fas fa-angle-right text-primary-500 mr-2"></i>Maux de tête</li>
                            <li><i class="fas fa-angle-right text-primary-500 mr-2"></i>Vertiges</li>
                            <li><i class="fas fa-angle-right text-primary-500 mr-2"></i>Troubles de l'équilibre</li>
                        </ul>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-md p-4 demo-card animate-fade-in" style="animation-delay: 0.3s">
                        <div class="flex items-center mb-3">
                            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center mr-3">
                                <i class="fas fa-lungs text-green-500"></i>
                            </div>
                            <h4 class="font-medium text-gray-800">Symptômes respiratoires</h4>
                        </div>
                        <ul class="text-gray-600 space-y-2 pl-4">
                            <li><i class="fas fa-angle-right text-primary-500 mr-2"></i>Toux persistante</li>
                            <li><i class="fas fa-angle-right text-primary-500 mr-2"></i>Difficulté à respirer</li>
                            <li><i class="fas fa-angle-right text-primary-500 mr-2"></i>Congestion nasale</li>
                        </ul>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-md p-4 demo-card animate-fade-in" style="animation-delay: 0.4s">
                        <div class="flex items-center mb-3">
                            <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center mr-3">
                                <i class="fas fa-stomach text-yellow-500"></i>
                            </div>
                            <h4 class="font-medium text-gray-800">Symptômes digestifs</h4>
                        </div>
                        <ul class="text-gray-600 space-y-2 pl-4">
                            <li><i class="fas fa-angle-right text-primary-500 mr-2"></i>Mal de ventre</li>
                            <li><i class="fas fa-angle-right text-primary-500 mr-2"></i>Nausées</li>
                            <li><i class="fas fa-angle-right text-primary-500 mr-2"></i>Troubles digestifs</li>
                        </ul>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-md p-4 demo-card animate-fade-in" style="animation-delay: 0.5s">
                        <div class="flex items-center mb-3">
                            <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center mr-3">
                                <i class="fas fa-allergies text-purple-500"></i>
                            </div>
                            <h4 class="font-medium text-gray-800">Symptômes dermatologiques</h4>
                        </div>
                        <ul class="text-gray-600 space-y-2 pl-4">
                            <li><i class="fas fa-angle-right text-primary-500 mr-2"></i>Problèmes de peau</li>
                            <li><i class="fas fa-angle-right text-primary-500 mr-2"></i>Éruptions cutanées</li>
                            <li><i class="fas fa-angle-right text-primary-500 mr-2"></i>Démangeaisons</li>
                        </ul>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-md p-4 demo-card animate-fade-in" style="animation-delay: 0.6s">
                        <div class="flex items-center mb-3">
                            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center mr-3">
                                <i class="fas fa-bone text-indigo-500"></i>
                            </div>
                            <h4 class="font-medium text-gray-800">Symptômes musculo-squelettiques</h4>
                        </div>
                        <ul class="text-gray-600 space-y-2 pl-4">
                            <li><i class="fas fa-angle-right text-primary-500 mr-2"></i>Douleurs articulaires</li>
                            <li><i class="fas fa-angle-right text-primary-500 mr-2"></i>Douleurs musculaires</li>
                            <li><i class="fas fa-angle-right text-primary-500 mr-2"></i>Raideurs</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Bouton pour lancer le chatbot -->
                <div class="text-center py-6 animate-fade-in" style="animation-delay: 0.7s">
                    <button id="openChatbot" class="bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white font-medium py-3 px-8 rounded-full shadow-lg transition-all duration-300 transform hover:scale-105 flex items-center mx-auto">
                        <i class="fas fa-stethoscope mr-2"></i>
                        Démarrer l'analyse de symptômes
                    </button>
                    <p class="text-gray-600 mt-3">Cliquez sur le bouton pour commencer à décrire vos symptômes</p>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Inclure le chatbot -->
    <?php include_once '../../views/components/chatbot_analyzer.php'; ?>
    
    <script>
        // Ouvrir le chatbot quand on clique sur le bouton principal
        document.getElementById('openChatbot').addEventListener('click', function() {
            document.getElementById('chatButton').click();
        });
    </script>
</body>
</html>
