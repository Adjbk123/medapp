<?php
require_once '../../includes/session.php';
require_once '../../config/config.php';

// Vérifier si l'utilisateur est connecté
requireLogin();

// Vérifier si l'utilisateur a le rôle requis
requireRole('medecin');

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

// Récupérer les informations du médecin
$stmt = db()->prepare("
    SELECT 
        m.*,
        pm.adresse,
        pm.profession,
        pm.imgdiplome,
        pm.disponibilite,
        s.nomspecialite
    FROM medecin m
    LEFT JOIN profilmedecin pm ON m.id = pm.idmedecin
    LEFT JOIN specialite s ON m.idspecialite = s.id
    WHERE m.id = ?
");
$stmt->execute([$user_id]);
$medecin = $stmt->fetch(PDO::FETCH_ASSOC);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        db()->beginTransaction();

        // Mise à jour des informations de base
        $stmt = db()->prepare("
            UPDATE medecin 
            SET nom = ?, prenom = ?, email = ?, contact = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $_POST['nom'],
            $_POST['prenom'],
            $_POST['email'],
            $_POST['contact'],
            $user_id
        ]);

        // Mise à jour ou insertion du profil
        $stmt = db()->prepare("
            INSERT INTO profilmedecin (idmedecin, adresse, disponibilite)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
            adresse = VALUES(adresse),
            disponibilite = VALUES(disponibilite)
        ");
        $stmt->execute([
            $user_id,
            $_POST['adresse'],
            $_POST['disponibilite']
        ]);

        // Gestion de l'upload de l'image du diplôme
        if (isset($_FILES['imgdiplome']) && $_FILES['imgdiplome']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../uploads/diplomes/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = strtolower(pathinfo($_FILES['imgdiplome']['name'], PATHINFO_EXTENSION));
            $new_filename = 'diplome_' . $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['imgdiplome']['tmp_name'], $upload_path)) {
                $stmt = db()->prepare("
                    UPDATE profilmedecin 
                    SET imgdiplome = ?
                    WHERE idmedecin = ?
                ");
                $stmt->execute([$new_filename, $user_id]);
            }
        }

        db()->commit();
        $success = "Profil mis à jour avec succès !";
        
        // Rafraîchir les données
        $stmt = db()->prepare("
            SELECT 
                m.*,
                pm.adresse,
                pm.profession,
                pm.imgdiplome,
                pm.disponibilite,
                s.nomspecialite
            FROM medecin m
            LEFT JOIN profilmedecin pm ON m.id = pm.idmedecin
            LEFT JOIN specialite s ON m.idspecialite = s.id
            WHERE m.id = ?
        ");
        $stmt->execute([$user_id]);
        $medecin = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        db()->rollBack();
        $error = "Une erreur est survenue lors de la mise à jour du profil.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - MedConnect</title>
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
        
        /* Sections de profil */
        .profile-section {
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-radius: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .profile-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.08);
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
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.8);
            color: #166534;
            border: 1px solid rgba(22, 163, 74, 0.3);
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }
        
        .btn-secondary:hover {
            background: rgba(220, 252, 231, 0.8);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        /* Champs de formulaire */
        .input-field {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(22, 163, 74, 0.2);
            border-radius: 0.5rem;
            padding: 0.625rem 1rem;
            transition: all 0.3s ease;
        }
        
        .input-field:focus {
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.2);
            border-color: #22c55e;
            outline: none;
            background: rgba(255, 255, 255, 0.95);
        }
        
        /* Upload de fichier */
        .file-upload {
            position: relative;
            overflow: hidden;
            margin: 10px 0;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(59, 130, 246, 0.1));
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            border: 1px dashed rgba(22, 163, 74, 0.3);
            transition: all 0.3s ease;
        }
        
        .file-upload:hover {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(59, 130, 246, 0.2));
        }
        
        .file-upload input[type="file"] {
            position: absolute;
            top: 0;
            right: 0;
            min-width: 100%;
            min-height: 100%;
            font-size: 100px;
            text-align: right;
            filter: alpha(opacity=0);
            opacity: 0;
            outline: none;
            cursor: pointer;
            display: block;
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
                <a href="profile_medecin.php" class="nav-link active flex items-center px-4 py-3 text-slate-700">
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
                            <i class="fas fa-user-md text-white text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-primary-800 font-heading">Mon Profil</h1>
                            <p class="text-sm text-gray-600">Gérez vos informations personnelles et professionnelles</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4" data-aos="fade-left">
                        <a href="dashboard.php" class="btn-secondary">
                            <i class="fas fa-arrow-left mr-2"></i>Retour au tableau de bord
                        </a>
                    </div>
                </div>
            </header>

            <!-- Contenu principal -->
            <main class="container mx-auto px-6 py-8">
                <?php if ($success): ?>
                    <div class="glass-card p-4 mb-6 border-l-4 border-primary-500" role="alert" data-aos="fade-up">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-primary-500 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-primary-800"><?php echo $success; ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="glass-card p-4 mb-6 border-l-4 border-red-500" role="alert" data-aos="fade-up">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-red-800"><?php echo $error; ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Carte de profil -->
                <div class="glass-card overflow-hidden" data-aos="fade-up">
                    <form method="POST" enctype="multipart/form-data" class="p-6 space-y-8">
                        <!-- Informations personnelles -->
                        <div class="profile-section p-6" data-aos="fade-up" data-aos-delay="100">
                            <h2 class="section-title flex items-center mb-6">
                                <span class="icon-circle bg-gradient-to-r from-primary-400 to-primary-600 mr-3 text-white">
                                    <i class="fas fa-user"></i>
                                </span>
                                Informations personnelles
                            </h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-primary-700 mb-2">Nom</label>
                                    <input type="text" name="nom" value="<?php echo htmlspecialchars($medecin['nom']); ?>" 
                                           class="input-field w-full" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-primary-700 mb-2">Prénom</label>
                                    <input type="text" name="prenom" value="<?php echo htmlspecialchars($medecin['prenom']); ?>" 
                                           class="input-field w-full" required>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                <div>
                                    <label class="block text-sm font-medium text-primary-700 mb-2">Email</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($medecin['email']); ?>" 
                                           class="input-field w-full" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-primary-700 mb-2">Téléphone</label>
                                    <input type="tel" name="contact" value="<?php echo htmlspecialchars($medecin['contact']); ?>" 
                                           class="input-field w-full" required>
                                </div>
                            </div>
                        </div>

                        <!-- Informations professionnelles -->
                        <div class="profile-section p-6" data-aos="fade-up" data-aos-delay="200">
                            <h2 class="section-title flex items-center mb-6">
                                <span class="icon-circle bg-gradient-to-r from-primary-400 to-secondary-500 mr-3 text-white">
                                    <i class="fas fa-briefcase-medical"></i>
                                </span>
                                Informations professionnelles
                            </h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-primary-700 mb-2">Spécialité</label>
                                    <div class="input-field w-full bg-primary-50 text-primary-700 flex items-center">
                                        <i class="fas fa-stethoscope text-primary-500 mr-2"></i>
                                        <?php echo htmlspecialchars($medecin['nomspecialite'] ?? 'Non spécifiée'); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6">
                                <label class="block text-sm font-medium text-primary-700 mb-2">Adresse professionnelle</label>
                                <textarea name="adresse" rows="3" class="input-field w-full"><?php echo htmlspecialchars($medecin['adresse'] ?? ''); ?></textarea>
                            </div>

                            <div class="mt-6">
                                <label class="block text-sm font-medium text-primary-700 mb-2">Disponibilités</label>
                                <textarea name="disponibilite" rows="3" class="input-field w-full"><?php echo htmlspecialchars($medecin['disponibilite'] ?? ''); ?></textarea>
                            </div>

                            <div class="mt-6">
                                <label class="block text-sm font-medium text-primary-700 mb-2">Diplôme</label>
                                <?php if (!empty($medecin['imgdiplome'])): ?>
                                    <div class="glass-card p-3 mb-4 flex items-center">
                                        <i class="fas fa-file-pdf text-primary-500 text-xl mr-3"></i>
                                        <div>
                                            <p class="text-sm font-medium">Diplôme actuel</p>
                                            <a href="../../uploads/diplomes/<?php echo htmlspecialchars($medecin['imgdiplome']); ?>" 
                                               target="_blank" class="text-primary-600 hover:text-primary-800 text-sm flex items-center mt-1">
                                                <i class="fas fa-external-link-alt mr-1"></i>Voir le document
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="file-upload">
                                    <label class="btn-secondary inline-block cursor-pointer">
                                        <i class="fas fa-upload mr-2"></i>Choisir un fichier
                                    </label>
                                    <input type="file" name="imgdiplome" accept=".pdf,.jpg,.jpeg,.png" class="input-field w-full">
                                </div>
                                <p class="mt-1 text-sm text-primary-600">Formats acceptés : PDF, JPG, PNG</p>
                            </div>
                        </div>

                        <div class="flex justify-end" data-aos="fade-up" data-aos-delay="300">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save mr-2"></i>Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </main>
            
            <!-- Pied de page -->
            <footer class="mt-auto py-6 px-6">
                <div class="container mx-auto">
                    <p class="text-center text-gray-500 text-sm">&copy; <?php echo date('Y'); ?> MedConnect - Tous droits réservés</p>
                </div>
            </footer>
        </div>
    </div>

    <script>
        // Mise à jour du nom du fichier sélectionné
        document.querySelector('input[type="file"]').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                this.previousElementSibling.innerHTML = `<i class="fas fa-file mr-2"></i>${fileName}`;
            }
        });
    </script>
</body>
</html> 