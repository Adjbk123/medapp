<?php
require_once '../../includes/session.php';
require_once '../../config/config.php';

// Vérifier si l'utilisateur est connecté et est un médecin
requireLogin();
requireRole('medecin');

// Générer un token CSRF s'il n'existe pas
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = $_SESSION['user_id'];
$ordonnances = []; // Initialiser le tableau des ordonnances

try {
    // Récupérer la liste des ordonnances du médecin
    $stmt = db()->prepare("
        SELECT o.*, p.nom as patient_nom, p.prenom as patient_prenom
        FROM ordonnance o
        JOIN patient p ON o.idpatient = p.id
        WHERE o.idmedecin = ?
        ORDER BY o.date_creation DESC
    ");
    
    if (!$stmt->execute([$user_id])) {
        throw new PDOException("Erreur lors de l'exécution de la requête");
    }
    
    $ordonnances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($ordonnances)) {
        $message = "Vous n'avez pas encore créé d'ordonnances.";
    }
} catch (PDOException $e) {
    error_log("Erreur de base de données : " . $e->getMessage());
    $message = "Une erreur est survenue lors de la récupération des ordonnances.";
    $ordonnances = []; // S'assurer que $ordonnances est un tableau vide en cas d'erreur
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordonnances - MedConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
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
                        },
                        secondary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                    },
                    fontFamily: {
                        'sans': ['Inter', 'sans-serif'],
                        'heading': ['Montserrat', 'sans-serif'],
                    },
                },
            },
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f9ff;
            background-image: 
                radial-gradient(at 40% 20%, rgba(34, 197, 94, 0.1) 0px, transparent 50%),
                radial-gradient(at 80% 0%, rgba(59, 130, 246, 0.1) 0px, transparent 50%),
                radial-gradient(at 0% 50%, rgba(245, 158, 11, 0.1) 0px, transparent 50%);
            background-attachment: fixed;
        }
        
        /* Glassmorphism */
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .glass-card:hover {
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        /* Carte d'ordonnance */
        .ordonnance-card {
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-radius: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .ordonnance-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.08);
        }
        
        /* Styles pour la barre latérale */
        .glass {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
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
        
        /* Boutons */
        .btn-primary {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(22, 163, 74, 0.2);
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(22, 163, 74, 0.3);
        }
        
        .btn-icon {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            transition: all 0.2s ease;
        }
        
        .btn-icon:hover {
            transform: translateY(-2px);
        }
        
        /* Navigation */
        .nav-link {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .nav-link:before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0%;
            height: 100%;
            background-color: rgba(34, 197, 94, 0.1);
            transition: all 0.3s ease;
            z-index: -1;
            border-radius: 0.5rem;
        }
        
        .nav-link:hover:before {
            width: 100%;
        }
        
        .nav-link:hover {
            transform: translateX(0);
        }
        
        .nav-link.active {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(59, 130, 246, 0.1));
            border-left: 3px solid #22c55e;
            font-weight: 500;
        }
        
        /* Éléments décoratifs */
        .icon-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .section-title {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            color: #166534;
            position: relative;
        }
    </style>
</head>
<body>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true,
                mirror: false
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
                <a href="ordonnances.php" class="nav-link active flex items-center px-4 py-3 text-slate-700">
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
        <div class="flex-1 pl-0 overflow-auto">
            <!-- En-tête -->
            <header class="glass sticky top-0 z-20">
                <div class="container mx-auto px-6 py-4 flex justify-between items-center">
                    <div class="flex items-center space-x-4" data-aos="fade-right">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-r from-primary-500 to-secondary-500 flex items-center justify-center shadow-md">
                            <i class="fas fa-prescription text-white text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-primary-800 font-heading">Ordonnances</h1>
                            <p class="text-sm text-gray-600">Gestion des prescriptions médicales</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4" data-aos="fade-left">
                        <div class="relative">
                            <input type="text" id="searchOrdonnance" placeholder="Rechercher une ordonnance..." class="pl-10 pr-4 py-3 rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-transparent bg-white bg-opacity-70 backdrop-blur-sm">
                            <i class="fas fa-search absolute left-3 top-3 text-primary-400"></i>
                        </div>
                        <a href="nouvelle_ordonnance.php" class="btn-primary">
                            <i class="fas fa-plus-circle mr-2"></i>Nouvelle ordonnance
                        </a>
                    </div>
                </div>
            </header>

            <!-- Contenu principal -->
            <main class="container mx-auto px-6 py-8">
                <?php if (isset($message)): ?>
                    <div class="glass-card p-4 mb-6 border-l-4 border-amber-400" role="alert" data-aos="fade-up">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-amber-500 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-amber-800"><?php echo htmlspecialchars($message); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- En-tête de section -->
                <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6 gap-4" data-aos="fade-up">
                    <h2 class="section-title flex items-center">
                        <span class="icon-circle bg-gradient-to-r from-primary-400 to-primary-600 mr-3 text-white">
                            <i class="fas fa-prescription"></i>
                        </span>
                        Mes Ordonnances
                        <span class="ml-3 bg-primary-100 text-primary-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                            <?php echo count($ordonnances); ?> ordonnance(s)
                        </span>
                    </h2>
                    <div class="flex space-x-2">
                        <a href="nouvelle_ordonnance.php" class="btn-primary" data-aos="fade-left">
                            <i class="fas fa-plus-circle mr-2"></i>Créer une ordonnance
                        </a>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="glass-card p-4 mb-6" data-aos="fade-up">
                    <div class="flex flex-wrap gap-4 items-center">
                        <div class="flex-1 min-w-[200px]">
                            <label for="filterDate" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                            <input type="date" id="filterDate" class="w-full rounded-lg border border-gray-200 p-2 focus:ring-2 focus:ring-primary-400 focus:border-transparent bg-white bg-opacity-70">
                        </div>
                        <div class="flex-1 min-w-[200px]">
                            <label for="filterPatient" class="block text-sm font-medium text-gray-700 mb-1">Patient</label>
                            <select id="filterPatient" class="w-full rounded-lg border border-gray-200 p-2 focus:ring-2 focus:ring-primary-400 focus:border-transparent bg-white bg-opacity-70">
                                <option value="">Tous les patients</option>
                                <!-- Options dynamiques pour les patients -->
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button id="resetFilters" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
                                <i class="fas fa-sync-alt"></i> Réinitialiser
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Liste des ordonnances -->
                <?php if (empty($ordonnances)): ?>
                    <div class="glass-card p-12 text-center" data-aos="fade-up">
                        <div class="text-6xl text-gray-300 mb-4"><i class="fas fa-prescription-bottle-alt"></i></div>
                        <h3 class="text-xl font-medium text-gray-500 mb-2">Aucune ordonnance</h3>
                        <p class="text-gray-400 mb-6">Vous n'avez pas encore créé d'ordonnances pour vos patients.</p>
                        <a href="nouvelle_ordonnance.php" class="btn-primary inline-block">
                            <i class="fas fa-plus-circle mr-2"></i>Créer votre première ordonnance
                        </a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($ordonnances as $index => $ordonnance): ?>
                            <div class="ordonnance-card p-6" data-aos="fade-up" data-aos-delay="<?php echo $index * 50; ?>">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-12 h-12 rounded-full bg-gradient-to-r from-primary-500 to-secondary-500 flex items-center justify-center shadow-md">
                                            <i class="fas fa-user text-white"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-semibold text-primary-800">
                                                <?php echo htmlspecialchars($ordonnance['patient_prenom'] . ' ' . $ordonnance['patient_nom']); ?>
                                            </h3>
                                            <p class="text-sm text-gray-500 flex items-center">
                                                <i class="fas fa-calendar-day text-primary-500 mr-2"></i>
                                                <?php echo date('d/m/Y', strtotime($ordonnance['date_creation'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex space-x-2">
                                        <a href="voir_ordonnance.php?id=<?php echo $ordonnance['id']; ?>" 
                                           class="btn-icon bg-blue-100 text-blue-600 hover:bg-blue-200" title="Voir l'ordonnance">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="modifier_ordonnance.php?id=<?php echo $ordonnance['id']; ?>" 
                                           class="btn-icon bg-amber-100 text-amber-600 hover:bg-amber-200" title="Modifier l'ordonnance">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="imprimer_ordonnance.php?id=<?php echo $ordonnance['id']; ?>" 
                                           class="btn-icon bg-green-100 text-green-600 hover:bg-green-200" title="Imprimer l'ordonnance">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="mt-4 space-y-3">
                                    <div class="glass-card p-3 bg-opacity-50">
                                        <h4 class="text-sm font-medium text-primary-700 mb-1 flex items-center">
                                            <i class="fas fa-pills text-primary-500 mr-2"></i>Médicaments
                                        </h4>
                                        <p class="text-sm text-gray-600 pl-6">
                                            <?php echo htmlspecialchars($ordonnance['medicaments'] ?? 'Aucun médicament prescrit'); ?>
                                        </p>
                                    </div>
                                    <div class="glass-card p-3 bg-opacity-50">
                                        <h4 class="text-sm font-medium text-primary-700 mb-1 flex items-center">
                                            <i class="fas fa-clipboard-list text-primary-500 mr-2"></i>Instructions
                                        </h4>
                                        <p class="text-sm text-gray-600 pl-6">
                                            <?php echo htmlspecialchars($ordonnance['instructions'] ?? 'Aucune instruction spécifique'); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
            
            <!-- Pied de page -->
            <footer class="mt-auto py-6 px-6">
                <div class="container mx-auto">
                    <p class="text-center text-gray-500 text-sm">&copy; <?php echo date('Y'); ?> MedConnect - Tous droits réservés</p>
                </div>
            </footer>
        </div>
    </div>
    
    <!-- Script pour la recherche et le filtrage -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchOrdonnance');
            const resetButton = document.getElementById('resetFilters');
            const dateFilter = document.getElementById('filterDate');
            const patientFilter = document.getElementById('filterPatient');
            const ordonnanceCards = document.querySelectorAll('.ordonnance-card');
            
            // Fonction de recherche
            searchInput.addEventListener('input', filterOrdonnances);
            dateFilter.addEventListener('change', filterOrdonnances);
            patientFilter.addEventListener('change', filterOrdonnances);
            
            // Réinitialiser les filtres
            resetButton.addEventListener('click', function() {
                searchInput.value = '';
                dateFilter.value = '';
                patientFilter.value = '';
                
                // Afficher toutes les ordonnances
                ordonnanceCards.forEach(card => {
                    card.style.display = 'block';
                });
            });
            
            function filterOrdonnances() {
                const searchTerm = searchInput.value.toLowerCase();
                const dateValue = dateFilter.value;
                const patientValue = patientFilter.value.toLowerCase();
                
                ordonnanceCards.forEach(card => {
                    const patientName = card.querySelector('h3').textContent.toLowerCase();
                    const dateText = card.querySelector('.text-gray-500').textContent.toLowerCase();
                    const medicaments = card.querySelector('.glass-card:nth-child(1) p').textContent.toLowerCase();
                    const instructions = card.querySelector('.glass-card:nth-child(2) p').textContent.toLowerCase();
                    
                    const matchesSearch = patientName.includes(searchTerm) || 
                                         medicaments.includes(searchTerm) || 
                                         instructions.includes(searchTerm);
                    
                    const matchesDate = !dateValue || dateText.includes(dateValue);
                    const matchesPatient = !patientValue || patientName.includes(patientValue);
                    
                    if (matchesSearch && matchesDate && matchesPatient) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>
</html>