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
$nom = $_SESSION['nom'];
$prenom = $_SESSION['prenom'];

// Récupérer la liste des patients du médecin
try {
    $stmt = db()->prepare("
        SELECT DISTINCT p.id, p.nom, p.prenom
        FROM patient p
        WHERE p.id_medecin = ?
        ORDER BY p.nom, p.prenom
    ");
    $stmt->execute([$user_id]);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des patients.";
    error_log($e->getMessage());
    $patients = [];
}

// Récupérer les consultations
try {
    $query = "
        SELECT c.*, p.nom as patient_nom, p.prenom as patient_prenom
        FROM consultation c
        JOIN patient p ON c.id_patient = p.id
        WHERE c.id_medecin = ?
    ";
    $params = [$user_id];

    // Si un patient est sélectionné, ajouter le filtre
    if (isset($_GET['patient_id']) && !empty($_GET['patient_id'])) {
        $query .= " AND c.id_patient = ?";
        $params[] = $_GET['patient_id'];
    }

    $query .= " ORDER BY c.date_consultation DESC";
    
    $stmt = db()->prepare($query);
    $stmt->execute($params);
    $consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des consultations.";
    error_log($e->getMessage());
    $consultations = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultations - MedConnect</title>
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
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(22, 163, 74, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #64748b, #475569);
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(71, 85, 105, 0.2);
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(71, 85, 105, 0.3);
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
        
        /* Animation des éléments de la liste */
        .consultation-row {
            transition: all 0.3s ease;
        }
        
        .consultation-row:hover {
            background-color: rgba(34, 197, 94, 0.05);
            transform: translateX(5px);
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
                <a href="consultations.php" class="nav-link active flex items-center px-4 py-3 text-slate-700">
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
                            <i class="fas fa-stethoscope text-white text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-primary-800 font-heading">Consultations</h1>
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
            <main class="container mx-auto px-4 py-8">
                <!-- En-tête de la page -->
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h2 class="text-2xl font-bold text-[#1B5E20]">Consultations</h2>
                        <p class="text-[#558B2F]">Gérez vos consultations et suivez vos patients</p>
                    </div>
                    <a href="nouvelle_consultation.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors duration-300">
                        <i class="fas fa-plus mr-2"></i>Nouvelle Consultation
                    </a>
                </div>

                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <!-- Filtre par patient -->
                <div class="glass-card p-6 mb-8" data-aos="fade-up">
                    <h3 class="text-lg font-semibold text-primary-800 mb-4 flex items-center">
                        <span class="icon-circle bg-gradient-to-r from-primary-400 to-primary-600 mr-3 text-white">
                            <i class="fas fa-filter"></i>
                        </span>
                        Filtrer les consultations
                    </h3>
                    <form method="GET" class="flex flex-col md:flex-row items-center space-y-4 md:space-y-0 md:space-x-4">
                        <div class="flex-1 w-full">
                            <label for="patient_id" class="block text-sm font-medium text-gray-700 mb-2">Sélectionner un patient</label>
                            <div class="relative">
                                <select name="patient_id" id="patient_id" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-400 focus:border-transparent bg-white bg-opacity-70 backdrop-blur-sm transition-all duration-300">
                                    <option value="">Tous les patients</option>
                                    <?php foreach ($patients as $patient): ?>
                                        <option value="<?php echo $patient['id']; ?>" <?php echo (isset($_GET['patient_id']) && $_GET['patient_id'] == $patient['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-primary-500">
                                    <!-- <i class="fas fa-user-circle"></i> -->
                                </div>
                            </div>
                        </div>
                        <div class="flex items-end space-x-3 md:mt-6">
                            <button type="submit" class="btn-primary px-6 py-3 rounded-lg transition-all duration-300 flex items-center justify-center">
                                <i class="fas fa-filter mr-2"></i>Appliquer
                            </button>
                            <?php if (isset($_GET['patient_id']) && !empty($_GET['patient_id'])): ?>
                                <a href="consultations.php" class="btn-secondary px-6 py-3 rounded-lg transition-all duration-300 flex items-center justify-center">
                                    <i class="fas fa-times mr-2"></i>Réinitialiser
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Liste des consultations -->
                <div class="glass-card p-6" data-aos="fade-up" data-aos-delay="100">
                    <h3 class="text-lg font-semibold text-primary-800 mb-6 flex items-center">
                        <span class="icon-circle bg-gradient-to-r from-primary-400 to-primary-600 mr-3 text-white">
                            <i class="fas fa-stethoscope"></i>
                        </span>
                        Liste des consultations
                        <span class="ml-3 bg-primary-100 text-primary-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                            <?php echo count($consultations); ?> consultation(s)
                        </span>
                    </h3>
                    
                    <?php if (empty($consultations)): ?>
                        <div class="text-center py-12">
                            <div class="text-5xl text-gray-300 mb-4"><i class="fas fa-clipboard-list"></i></div>
                            <h3 class="text-xl font-medium text-gray-500 mb-1">Aucune consultation</h3>
                            <p class="text-gray-400">Vous n'avez pas encore enregistré de consultations</p>
                            <a href="nouvelle_consultation.php" class="mt-6 inline-block btn-primary px-6 py-3 rounded-lg">
                                <i class="fas fa-plus mr-2"></i>Créer une consultation
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full rounded-lg overflow-hidden">
                                <thead>
                                    <tr class="bg-gradient-to-r from-primary-500/10 to-secondary-500/10 text-primary-800">
                                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Patient</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Motif</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Diagnostic</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Traitement</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach ($consultations as $index => $consultation): ?>
                                        <tr class="consultation-row hover:bg-primary-50/50 transition-colors duration-150" data-aos="fade-up" data-aos-delay="<?php echo $index * 50; ?>">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center mr-3">
                                                        <i class="fas fa-calendar-day text-primary-500"></i>
                                                    </div>
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-900"><?php echo date('d/m/Y', strtotime($consultation['date_consultation'])); ?></div>
                                                        <div class="text-xs text-gray-500"><?php echo date('H:i', strtotime($consultation['date_consultation'])); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($consultation['patient_prenom'] . ' ' . $consultation['patient_nom']); ?></div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-900 max-w-xs truncate"><?php echo htmlspecialchars($consultation['motif']); ?></div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-900 max-w-xs truncate"><?php echo htmlspecialchars($consultation['diagnostic'] ?? 'Non spécifié'); ?></div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-900 max-w-xs truncate"><?php echo htmlspecialchars($consultation['traitement'] ?? 'Non spécifié'); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex space-x-3">
                                                    <a href="voir_consultation.php?id=<?php echo $consultation['id']; ?>" 
                                                       class="btn-icon bg-blue-100 text-blue-600 hover:bg-blue-200">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="modifier_consultation.php?id=<?php echo $consultation['id']; ?>" 
                                                       class="btn-icon bg-amber-100 text-amber-600 hover:bg-amber-200">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="imprimer_consultation.php?id=<?php echo $consultation['id']; ?>" 
                                                       class="btn-icon bg-green-100 text-green-600 hover:bg-green-200">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Initialisation des animations AOS
        AOS.init({
            duration: 800,
            easing: 'ease-out-cubic',
            once: true,
            offset: 50,
            delay: 50
        });
        
        // Gestion des filtres
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                button.classList.add('active');
            });
        });

        // Auto-submit du formulaire de filtre quand un patient est sélectionné
        document.getElementById('patient_id').addEventListener('change', function() {
            this.form.submit();
        });
        
        // Effet de survol sur les lignes du tableau
        document.querySelectorAll('.consultation-row').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.classList.add('shadow-sm');
            });
            row.addEventListener('mouseleave', function() {
                this.classList.remove('shadow-sm');
            });
        });
    </script>
</body>
</html> 