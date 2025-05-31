<?php
require_once '../controllers/Auth.php';

// Vérifier si la session est déjà démarrée
if (session_status() == PHP_SESSION_NONE) {
    // Cela sera géré par session.php, ne pas démarrer ici
    // session_start();
}

// Inclusion des fichiers nécessaires
require_once '../config/database.php';
require_once '../models/Patient.php';
require_once '../models/Medecin.php';
require_once '../includes/session.php';

// Initialiser la connexion à la base de données
$database = new Database();
$db = $database->getConnection();

// Définir le chemin racine pour les liens dans header et footer
$root_path = '../';

// Vérifier si une erreur d'authentification est présente dans la session
if (isset($_SESSION['auth_error'])) {
    $message = $_SESSION['auth_error'];
    unset($_SESSION['auth_error']);
}

// Vérifier si une erreur de connexion est présente dans la session
if (isset($_SESSION['login_error'])) {
    $message = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

// Traitement du formulaire de connexion
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $auth = new Auth();
    
    // Récupérer les données du formulaire
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Tenter la connexion
    if ($auth->login($email, $password)) {
        // Récupérer le rôle depuis la session (pas besoin de redémarrer la session)
        $role = $_SESSION['role'];
        
        // Rediriger vers la page appropriée selon le rôle
        switch ($role) {
            case 'admin':
                header("Location: admin/dashboard.php");
                break;
            case 'medecin':
                header("Location: medecin/dashboard.php");
                break;
            case 'patient':
                header("Location: patient/dashboard.php");
                break;
            default:
                header("Location: index.php");
                break;
        }
        exit;
    } else {
        // Vérifier si le compte existe mais n'est pas vérifié
        $patient = new Patient($db);
        $patient->email = $email;
        
        $medecin = new Medecin($db);
        $medecin->email = $email;
        
        if ($patient->emailExists() && $patient->verification_status !== 'verified') {
            $message = "Votre compte n'est pas encore vérifié. Veuillez vérifier votre email et cliquer sur le lien de confirmation.";
        } elseif ($medecin->emailExists() && $medecin->verification_status !== 'verified') {
            $message = "Votre compte est en attente de vérification par l'administrateur. Vous recevrez une notification dès que votre compte sera validé.";
        } else {
            $message = "Email ou mot de passe incorrect. Veuillez réessayer.";
        }
    }
}

// x
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - MedConnect</title>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
            min-height: 100vh;
            background-image: 
                radial-gradient(circle at 20% 35%, rgba(34, 197, 94, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 75% 80%, rgba(14, 165, 233, 0.05) 0%, transparent 50%);
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
            border-radius: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
            transition: all 0.3s ease;
        }
        
        /* Form styles */
        .form-container {
            max-width: 380px;
            margin: 0 auto;
            padding: 1.75rem;
            border-radius: 0.75rem;
            overflow: hidden;
            position: relative;
        }
        
        .form-header {
            position: relative;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .form-avatar {
            width: 60px;
            height: 60px;
            margin: 0 auto 0.75rem;
            background: linear-gradient(135deg, #22c55e, #0ea5e9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px -5px rgba(34, 197, 94, 0.3);
            position: relative;
            z-index: 1;
        }
        
        .form-avatar::before {
            content: '';
            position: absolute;
            inset: 3px;
            border-radius: 50%;
            background: white;
            z-index: -1;
        }
        
        .form-avatar i {
            background: linear-gradient(135deg, #22c55e, #0ea5e9);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-size: 2.25rem;
        }
        
        .form-input-group {
            position: relative;
            margin-bottom: 1.5rem;
            border-radius: 0.75rem;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(226, 232, 240, 0.8);
            background-color: rgba(255, 255, 255, 0.8);
        }
        
        .form-input-group:focus-within {
            border-color: #22c55e;
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1);
            background-color: white;
        }
        
        .form-input-icon-wrapper {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            transition: color 0.3s ease;
        }
        
        .form-input-group:focus-within .form-input-icon-wrapper {
            color: #22c55e;
        }
        
        .form-input {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 3rem;
            border: none;
            outline: none;
            background-color: transparent;
            font-size: 0.95rem;
            color: #1e293b;
        }
        
        .form-input::placeholder {
            color: #94a3b8;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            border: none;
            border-radius: 0.75rem;
            padding: 0.875rem 1.5rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.15);
            width: 100%;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #16a34a, #15803d);
            box-shadow: 0 6px 16px rgba(34, 197, 94, 0.2);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.8);
            color: #475569;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 0.75rem;
            padding: 0.875rem 1.5rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }
        
        .btn-secondary:hover {
            background: white;
            color: #334155;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
            transform: translateY(-2px);
        }
        
        .google-icon {
            margin-right: 0.75rem;
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
        }
        
        .divider-line {
            flex: 1;
            height: 1px;
            background-color: rgba(226, 232, 240, 0.8);
        }
        
        .divider-text {
            padding: 0 1rem;
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .alert {
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            text-decoration: underline;
        }
        .link-secondary {
            color: #10b981;
        }
        .link-secondary:hover {
            color: #059669;
        }
        .register-btn {
            background-color: #10b981;
            color: white;
            border: none;
            border-radius: 0.5rem;
            padding: 0.875rem;
            font-weight: 600;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.2s;
            margin-top: 0.5rem;
        }
        .register-btn:hover {
            background-color: #059669;
        }

    </style>
</head>
<body class="flex flex-col min-h-screen">
    <?php include_once 'components/header.php'; ?>

    <main class="flex-grow py-8">
        <!-- Formes décoratives (réduites) -->
        <div class="absolute top-40 left-10 w-48 h-48 bg-primary-100 rounded-full opacity-20 blur-3xl"></div>
        <div class="absolute bottom-40 right-10 w-48 h-48 bg-secondary-100 rounded-full opacity-20 blur-3xl"></div>
        
        <div class="container mx-auto px-4 relative z-10">
            <!-- Introduction (plus compacte) -->
            <div class="text-center mb-6" data-aos="fade-up">
                <span class="inline-block px-3 py-1 rounded-full bg-gradient-to-r from-primary-100 to-secondary-100 text-primary-600 font-medium text-xs mb-2">
                    <i class="fas fa-user-shield mr-1"></i>Espace sécurisé
                </span>
                <h1 class="text-2xl font-bold text-slate-800 mb-2 font-heading">Connexion à votre compte</h1>
                <p class="text-slate-600 text-sm max-w-xl mx-auto">Accédez à votre espace personnel pour gérer vos rendez-vous médicaux.</p>
            </div>
            
                <div class="glass-card form-container" data-aos="fade-up" data-aos-delay="100">
                    <div class="form-header">
                        <div class="form-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <h2 class="text-xl font-bold text-slate-800 mb-1 font-heading">Bienvenue</h2>
                        <p class="text-slate-600 text-sm">Connectez-vous à votre espace</p>
                    </div>
                    
                    <div class="px-4 pt-2 pb-4">

                    <?php if (isset($_GET['registered']) && $_GET['registered'] === 'success' && isset($_GET['admin_verification']) && $_GET['admin_verification'] === 'pending'): ?>
                        <div class="alert alert-success flex items-start bg-green-50 border-l-4 border-green-500 text-green-700 p-3 mb-4 text-sm">
                            <i class="fas fa-check-circle mr-2 mt-0.5 text-base"></i>
                            <span>Votre inscription a été prise en compte. Votre compte est en attente de vérification par l'administrateur. Vous pourrez vous connecter dès que votre compte aura été validé.</span>
                        </div>
                    <?php elseif (isset($message)): ?>
                        <div class="alert alert-error flex items-start">
                            <i class="fas fa-exclamation-circle mr-3 mt-1 text-lg"></i>
                            <span><?php echo $message; ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <form action="" method="post" class="space-y-4">
                        <input type="hidden" name="action" value="login">
                        
                        <div class="form-group">
                            <label for="email" class="block text-xs font-medium text-slate-700 mb-1">Adresse email</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-envelope text-slate-400 text-sm"></i>
                                </div>
                                <input type="email" id="email" name="email" class="block w-full pl-9 pr-3 py-2 text-sm border border-slate-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white text-slate-700" placeholder="Entrez votre adresse email" required>
                            </div>
                        </div>

                        <div>
                            <label for="password" class="block text-slate-700 text-sm font-medium mb-2">Mot de passe</label>
                            <div class="form-input-group">
                                <div class="form-input-icon-wrapper">
                                    <i class="fas fa-lock"></i>
                                </div>
                                <input type="password" class="form-input" id="password" name="password" 
                                       placeholder="••••••••" required>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                            <input type="checkbox" id="remember" name="remember" class="h-3.5 w-3.5 text-primary-600 focus:ring-primary-500 border-slate-300 rounded">
                            <label for="remember" class="ml-2 block text-xs text-slate-700">
                                Se souvenir de moi
                            </label>
                        </div>
                            <a href="forgot_password.php" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                                Mot de passe oublié ?
                            </a>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="w-full bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white font-medium py-2 px-4 rounded-md transition-all flex items-center justify-center text-sm">
                                <i class="fas fa-sign-in-alt mr-2"></i>
                                Se connecter
                            </button>
                        </div>
                    </form>
                    
                    <div class="divider">
                        <div class="divider-line"></div>
                        <span class="divider-text">ou</span>
                        <div class="divider-line"></div>
                    </div>
                    
                    <a href="../auth/google-login.php" class="btn-secondary flex items-center justify-center py-2 px-4 text-sm rounded-md w-full mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" class="mr-2"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                        Continuer avec Google
                    </a>
                    
                    <div class="text-center mt-5 space-y-2">
                        <p class="text-xs font-medium text-slate-600">Vous n'avez pas de compte ?</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <a href="register_patient.php" class="btn-primary bg-gradient-to-r from-secondary-500 to-secondary-600 hover:from-secondary-600 hover:to-secondary-700 py-1.5 px-3 text-xs rounded-md flex items-center justify-center">
                                <i class="fas fa-user-plus mr-1"></i>
                                Patient
                            </a>
                            <a href="register_medecin.php" class="btn-primary bg-gradient-to-r from-secondary-500 to-secondary-600 hover:from-secondary-600 hover:to-secondary-700 py-1.5 px-3 text-xs rounded-md flex items-center justify-center">
                                <i class="fas fa-user-plus mr-1"></i>
                                Médecin
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include_once 'components/footer.php'; ?>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialisation des animations AOS
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                duration: 800,
                easing: 'ease-out',
                once: true,
                offset: 50,
                delay: 100
            });
            
            // Animation de chargement du formulaire
            const form = document.querySelector('form');
            form.classList.add('fade-in');
            
            // Effet de focus sur les champs de formulaire
            const inputs = document.querySelectorAll('.form-input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                input.addEventListener('blur', function() {
                    if (this.value === '') {
                        this.parentElement.classList.remove('focused');
                    }
                });
            });
        });

        // Gestion des erreurs de formulaire
        const urlParams = new URLSearchParams(window.location.search);
        const error = urlParams.get('error');
        if (error) {
            const notification = document.createElement('div');
            notification.className = 'alert alert-error';
            notification.innerHTML = `<i class="fas fa-exclamation-circle mr-3"></i>${decodeURIComponent(error)}`;
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateY(-20px)';
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }
    </script>
</body>
</html>  