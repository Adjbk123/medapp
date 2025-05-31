<?php
require_once '../../config/config.php';
require_once '../../includes/session.php';
requireLogin();
requireRole('patient');

// Récupération du mot-clé de recherche
$search = $_GET['search'] ?? '';

if (!empty($search)) {
    $sql = "SELECT * FROM pharmacie WHERE nom LIKE :search1 OR localisation LIKE :search2 ORDER BY nom ASC";
    $stmt = db()->prepare($sql);
    $stmt->execute([':search1' => "%$search%", ':search2' => "%$search%"]);
} else {
    $sql = "SELECT * FROM pharmacie ORDER BY nom ASC";
    $stmt = db()->prepare($sql);
    $stmt->execute();
}

$pharmacies = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacies - MedConnect</title>
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
        
        /* Navigation */
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
        
        /* Effets visuels */
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
        
        /* Cartes de pharmacie */
        .pharmacy-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            overflow: hidden;
        }
        .pharmacy-card:hover {
            transform: translateY(-5px);
            border-left-color: #3b82f6;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        /* Indicateur de statut */
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }
        .search-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-[#EFF6FF] to-[#DBEAFE] min-h-screen">
    <div class="min-h-screen flex">
        <!-- Barre latérale -->
        <aside class="w-64 bg-white shadow-lg flex flex-col py-6 px-4">
            <div class="flex items-center justify-center mb-10">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#3b82f6] to-[#60a5fa] flex items-center justify-center">
                    <i class="fas fa-heartbeat text-white text-xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-[#1e40af] ml-3">MedConnect</h1>
            </div>
            <nav class="flex-1 space-y-2">
                <a href="dashboard.php" class="nav-link block px-4 py-3 rounded-lg text-[#1e40af]">
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
                <a href="listes_pharmacie.php" class="nav-link active block px-4 py-3 rounded-lg text-[#1e40af]">
                    <i class="fas fa-pills mr-3"></i>Ma Pharmacie
                </a>
                <a href="messages.php" class="nav-link block px-4 py-3 rounded-lg text-[#1e40af]">
                    <i class="fas fa-envelope mr-3"></i>Messages
                </a>
                <a href="profile_patient.php" class="nav-link block px-4 py-3 rounded-lg text-[#1e40af]">
                    <i class="fas fa-user mr-3"></i>Mon Profil
                </a>
            </nav>
            <div class="mt-6">
                <a href="../../logout.php" class="block bg-[#FF5252] hover:bg-[#D32F2F] text-white text-center px-4 py-3 rounded-lg transition-colors duration-300">
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
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#3b82f6] to-[#60a5fa] flex items-center justify-center">
                            <i class="fas fa-pills text-white text-xl"></i>
                        </div>
                        <h1 class="text-2xl font-bold text-[#1e40af]">Pharmacies</h1>
                    </div>
                    <div class="text-sm text-[#3b82f6]">
                        <i class="fas fa-calendar-alt mr-2"></i><?php echo date('d/m/Y'); ?>
                    </div>
                </div>
            </header>

            <!-- Contenu principal -->
            <main class="container mx-auto px-4 py-8">
                <!-- Formulaire de recherche -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-8 glass-effect">
                    <form method="get" class="flex items-center gap-4">
                        <div class="flex-1 relative">
                            <input type="text" 
                                   name="search" 
                                   placeholder="Rechercher une pharmacie..." 
                                   value="<?= htmlspecialchars($search) ?>"
                                   class="search-input w-full border border-gray-200 rounded-lg px-4 py-3 pl-10 focus:outline-none">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-[#3b82f6]"></i>
                        </div>
                        <button type="submit" 
                                class="bg-[#3b82f6] hover:bg-[#2563eb] text-white px-6 py-3 rounded-lg transition-colors duration-300 flex items-center gap-2">
                            <i class="fas fa-search"></i>
                            Rechercher
                        </button>
                    </form>
                </div>

                <!-- Chatbot WhatsApp Banner -->
                <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl shadow-lg p-4 mb-6 text-white flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center mr-4 shadow-md">
                            <i class="fab fa-whatsapp text-green-500 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg">Besoin d'aide ? Contactez notre chatbot WhatsApp</h3>
                            <p class="text-green-100">Assistance médicale 24/7 et informations sur les médicaments</p>
                        </div>
                    </div>
                    <a href="https://wa.me/22956191919" target="_blank" class="bg-white text-green-600 hover:bg-green-50 px-4 py-2 rounded-lg font-medium flex items-center transition-all transform hover:scale-105">
                        <i class="fab fa-whatsapp mr-2"></i>
                        +229 56191919
                    </a>
                </div>

                <!-- Filtres rapides -->
                <div class="flex flex-wrap gap-3 mb-6">
                    <button type="button" class="filter-btn active flex items-center px-4 py-2 bg-white rounded-full shadow-sm hover:shadow-md transition-all text-[#1e40af]">
                        <i class="fas fa-globe mr-2"></i> Toutes
                    </button>
                    <button type="button" class="filter-btn flex items-center px-4 py-2 bg-white rounded-full shadow-sm hover:shadow-md transition-all text-[#1e40af]">
                        <span class="status-indicator status-open"></span> Ouvertes
                    </button>
                    <button type="button" class="filter-btn flex items-center px-4 py-2 bg-white rounded-full shadow-sm hover:shadow-md transition-all text-[#1e40af]">
                        <i class="fas fa-star mr-2 text-yellow-400"></i> Favorites
                    </button>
                    <button type="button" class="filter-btn flex items-center px-4 py-2 bg-white rounded-full shadow-sm hover:shadow-md transition-all text-[#1e40af]">
                        <i class="fas fa-location-arrow mr-2"></i> À proximité
                    </button>
                </div>
                
                <!-- Liste des pharmacies -->
                <?php if (count($pharmacies) > 0): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 animate-fade-in">
                        <?php foreach ($pharmacies as $pharmacie): ?>
                            <?php 
                                // Simuler des données pour la démo
                                $isOpen = rand(0, 1); // 0 = fermée, 1 = ouverte
                                $distance = rand(1, 15) / 10; // Distance en km (entre 0.1 et 1.5 km)
                                $phone = '0' . rand(1, 9) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99);
                                $rating = rand(35, 50) / 10; // Note entre 3.5 et 5.0
                                $isFavorite = rand(0, 5) > 4; // 1 chance sur 6 d'être favorite
                            ?>
                            <div class="pharmacy-card bg-white rounded-xl shadow-lg overflow-hidden glass-effect">
                                <div class="p-6">
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-14 h-14 rounded-full bg-gradient-to-br from-[#3b82f6] to-[#60a5fa] flex items-center justify-center">
                                                <i class="fas fa-pills text-white text-xl"></i>
                                            </div>
                                            <div>
                                                <div class="flex items-center">
                                                    <h3 class="text-xl font-semibold text-[#1e40af]"><?= htmlspecialchars($pharmacie['nom']) ?></h3>
                                                    <?php if ($isFavorite): ?>
                                                        <button class="ml-2 text-yellow-400 hover:text-yellow-500 transition-colors">
                                                            <i class="fas fa-star"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="ml-2 text-gray-300 hover:text-yellow-400 transition-colors">
                                                            <i class="far fa-star"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex items-center mt-1 text-sm">
                                                    <span class="status-indicator <?= $isOpen ? 'status-open' : 'status-closed' ?>"></span>
                                                    <span class="<?= $isOpen ? 'text-green-600' : 'text-red-500' ?>">
                                                        <?= $isOpen ? 'Ouvert' : 'Fermé' ?>
                                                    </span>
                                                    <?php if ($isOpen): ?>
                                                        <span class="text-gray-500 mx-2">•</span>
                                                        <span class="text-gray-500">Ferme à 20h</span>
                                                    <?php else: ?>
                                                        <span class="text-gray-500 mx-2">•</span>
                                                        <span class="text-gray-500">Ouvre demain à 8h30</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <p class="text-gray-600 flex items-start">
                                            <i class="fas fa-map-marker-alt text-[#3b82f6] mt-1 mr-3"></i>
                                            <span><?= nl2br(htmlspecialchars($pharmacie['localisation'])) ?></span>
                                        </p>
                                        <p class="text-gray-600 flex items-center mt-2">
                                            <i class="fas fa-phone text-[#3b82f6] mr-3"></i>
                                            <span><?= $phone ?></span>
                                        </p>
                                        <p class="text-gray-600 flex items-center mt-2">
                                            <i class="fas fa-location-arrow text-[#3b82f6] mr-3"></i>
                                            <span>À <?= $distance ?> km de votre position</span>
                                        </p>
                                    </div>
                                    
                                    <div class="flex items-center mt-3">
                                        <div class="flex items-center text-yellow-400">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php if ($i <= floor($rating)): ?>
                                                    <i class="fas fa-star"></i>
                                                <?php elseif ($i - 0.5 <= $rating): ?>
                                                    <i class="fas fa-star-half-alt"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="ml-2 text-gray-600"><?= $rating ?> (<?= rand(10, 99) ?> avis)</span>
                                    </div>
                                </div>
                                
                                <div class="flex border-t border-gray-100">
                                    <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($pharmacie['localisation']) ?>"
                                       target="_blank" 
                                       class="flex-1 py-3 text-center text-[#3b82f6] hover:bg-blue-50 transition-colors duration-300">
                                        <i class="fas fa-map-marked-alt mr-2"></i>
                                        Itinéraire
                                    </a>
                                    <a href="tel:<?= str_replace(' ', '', $phone) ?>" 
                                       class="flex-1 py-3 text-center text-[#3b82f6] hover:bg-blue-50 transition-colors duration-300 border-l border-gray-100">
                                        <i class="fas fa-phone mr-2"></i>
                                        Appeler
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-xl shadow-lg p-8 text-center glass-effect">
                        <div class="w-16 h-16 rounded-full bg-[#FEE2E2] flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-exclamation-circle text-[#991B1B] text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-[#1e40af] mb-2">Aucune pharmacie trouvée</h3>
                        <p class="text-[#3b82f6]">Essayez de modifier vos critères de recherche</p>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    <!-- JavaScript pour l'interactivité -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gestion des filtres
            const filterButtons = document.querySelectorAll('.filter-btn');
            const pharmacyCards = document.querySelectorAll('.pharmacy-card');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Retirer la classe active de tous les boutons
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    
                    // Ajouter la classe active au bouton cliqué
                    this.classList.add('active');
                    
                    // Récupérer le type de filtre
                    const filterType = this.textContent.trim();
                    
                    // Animation pour les cartes
                    pharmacyCards.forEach(card => {
                        card.style.opacity = '0.6';
                        card.style.transform = 'scale(0.95)';
                        
                        setTimeout(() => {
                            // Logique de filtrage simulée
                            let shouldShow = true;
                            
                            if (filterType.includes('Ouvertes')) {
                                // Vérifier si la pharmacie est ouverte
                                shouldShow = card.querySelector('.status-open') !== null;
                            } else if (filterType.includes('Favorites')) {
                                // Vérifier si la pharmacie est favorite
                                shouldShow = card.querySelector('.fas.fa-star') !== null;
                            } else if (filterType.includes('À proximité')) {
                                // Simuler un filtre de proximité (moins de 1km)
                                const distanceText = card.querySelector('.fa-location-arrow').nextElementSibling.textContent;
                                const distance = parseFloat(distanceText.match(/\d+\.\d+/)[0]);
                                shouldShow = distance < 1.0;
                            }
                            
                            // Afficher ou masquer la carte
                            if (shouldShow) {
                                card.style.display = 'block';
                                setTimeout(() => {
                                    card.style.opacity = '1';
                                    card.style.transform = 'scale(1)';
                                }, 50);
                            } else {
                                setTimeout(() => {
                                    card.style.display = 'none';
                                }, 300);
                            }
                        }, 300);
                    });
                });
            });
            
            // Gestion des favoris
            const favoriteButtons = document.querySelectorAll('.pharmacy-card .fa-star, .pharmacy-card .far.fa-star').forEach(star => {
                star.parentElement.addEventListener('click', function() {
                    const isFavorite = this.querySelector('i').classList.contains('fas');
                    
                    if (isFavorite) {
                        // Retirer des favoris
                        this.innerHTML = '<i class="far fa-star"></i>';
                        this.classList.remove('text-yellow-400');
                        this.classList.add('text-gray-300');
                    } else {
                        // Ajouter aux favoris
                        this.innerHTML = '<i class="fas fa-star"></i>';
                        this.classList.remove('text-gray-300');
                        this.classList.add('text-yellow-400');
                        
                        // Animation de pulsation
                        this.classList.add('pulse');
                        setTimeout(() => {
                            this.classList.remove('pulse');
                        }, 1000);
                    }
                });
            });
            
            // Animation d'entrée pour les cartes
            pharmacyCards.forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('animate-fade-in');
                }, index * 100);
            });
        });
    </script>
    

</body>
</html>
