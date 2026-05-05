<?php
// Ajout des en-têtes de sécurité
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self' https: 'unsafe-inline' 'unsafe-eval'; img-src 'self' https: data:;");

// Mode débogage forcé pour identifier l'erreur
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Définir le chemin racine pour les liens dans header et footer
$root_path = '';

// Point d'entrée principal de l'application
// La session sera démarrée dans le fichier session.php, ne pas la démarrer ici

// Afficher un message pour confirmer que le script commence son exécution
echo "<!-- Démarrage du script index.php -->";

try {
    // Inclusion des fichiers nécessaires avec vérification
    if (!file_exists('config/config.php')) {
        throw new Exception("Le fichier config.php est introuvable.");
    }
    require_once 'config/config.php';
    require_once 'config/paths.php';

    if (!file_exists('includes/session.php')) {
        throw new Exception("Le fichier session.php est introuvable.");
    }
    require_once 'includes/session.php';
    
    if (!file_exists('includes/routing.php')) {
        throw new Exception("Le fichier routing.php est introuvable.");
    }
    require_once 'includes/routing.php';
    
    echo "<!-- Fichiers chargés avec succès -->";
    
    // Redirection si l'utilisateur est déjà connecté
    if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
        // Redirection vers le dashboard uniquement si on est sur la page d'accueil
        $base = rtrim(BASE_URL, '/');
        $uri  = strtok($_SERVER['REQUEST_URI'], '?');
        if ($uri === $base . '/' || $uri === $base . '/index.php' || $uri === $base) {
            $dashboard_url = getDashboardUrl();
            header('Location: ' . $dashboard_url);
            exit();
        }
    }
} catch (Exception $e) {
    // Journalisation de l'erreur
    error_log("Erreur dans index.php : " . $e->getMessage());
    
    // Afficher l'erreur précise pour le débogage
    echo '<div style="background-color: #ffdddd; border: 1px solid #ff0000; padding: 10px; margin: 10px;">';
    echo '<h2>Erreur détectée :</h2>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    
    // En mode développement, afficher plus de détails
    if (env('APP_ENV') === 'development') {
        echo '<h3>Trace :</h3>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        
        // Information sur le chemin des fichiers
        echo '<h3>Informations système :</h3>';
        echo '<p>Chemin actuel : ' . htmlspecialchars(getcwd()) . '</p>';
        echo '<p>Chemin du fichier : ' . htmlspecialchars(__FILE__) . '</p>';
        echo '<p>PHP version : ' . htmlspecialchars(phpversion()) . '</p>';
        
        if (function_exists('env')) {
            echo '<p>APP_ENV : ' . htmlspecialchars(env('APP_ENV', 'non défini')) . '</p>';
        } else {
            echo '<p>Fonction env() non disponible</p>';
        }
    }
    
    echo '</div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedConnect - Votre santé, notre priorité</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eefdf2',
                            100: '#d7f9e0',
                            200: '#b2f1c4',
                            300: '#7ee4a0',
                            400: '#4ad07c',
                            500: '#22c55e', // Base primary
                            600: '#179c4a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                            950: '#052e16'
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
                            950: '#082f49'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Plus Jakarta Sans', 'sans-serif']
                    },
                    animation: {
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'bounce-slow': 'bounce 3s infinite',
                        'float': 'float 6s ease-in-out infinite',
                        'wave': 'wave 2.5s infinite'
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-10px)' }
                        },
                        wave: {
                            '0%': { transform: 'rotate(0deg)' },
                            '10%': { transform: 'rotate(14deg)' },
                            '20%': { transform: 'rotate(-8deg)' },
                            '30%': { transform: 'rotate(14deg)' },
                            '40%': { transform: 'rotate(-4deg)' },
                            '50%': { transform: 'rotate(10deg)' },
                            '60%': { transform: 'rotate(0deg)' },
                            '100%': { transform: 'rotate(0deg)' }
                        }
                    },
                    backdropBlur: {
                        xs: '2px'
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/waypoints/4.0.1/jquery.waypoints.min.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            scroll-behavior: smooth;
            background-color: #fafafa;
        }
        
        /* Glassmorphism */
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .glass-dark {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .glass-card:hover {
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            transform: translateY(-5px);
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        
        /* Styles globaux */
        .section-padding {
            padding: 7rem 0;
        }
        
        .section-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 2.75rem;
            font-weight: 800;
            background: linear-gradient(90deg, #22c55e, #0ea5e9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
        }
        
        .section-subtitle {
            font-family: 'Inter', sans-serif;
            font-size: 1.125rem;
            font-weight: 500;
            color: #64748b;
            margin-bottom: 2rem;
            max-width: 800px;
        }
        
        /* Hero section */
        .hero-bg {
            background: radial-gradient(circle at 10% 20%, rgba(220, 252, 231, 0.8) 0%, rgba(186, 230, 253, 0.5) 90%);
            position: relative;
            overflow: hidden;
        }
        
        .hero-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%2322c55e' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.5;
        }
        
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            z-index: 0;
            opacity: 0.4;
        }
        
        .blob-1 {
            top: 10%;
            right: 10%;
            width: 300px;
            height: 300px;
            background: rgba(34, 197, 94, 0.4);
            animation: blob-move 15s infinite alternate ease-in-out;
        }
        
        .blob-2 {
            bottom: 10%;
            left: 10%;
            width: 350px;
            height: 350px;
            background: rgba(14, 165, 233, 0.4);
            animation: blob-move 20s infinite alternate-reverse ease-in-out;
        }
        
        @keyframes blob-move {
            0% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(5%, 5%) scale(1.1); }
            66% { transform: translate(-5%, 10%) scale(0.9); }
            100% { transform: translate(0%, -5%) scale(1); }
        }
        
        /* Cards et éléments d'interface */
        .feature-card {
            @apply glass-card p-8 relative overflow-hidden;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .feature-card::after {
            content: '';
            position: absolute;
            inset: 0;
            z-index: -1;
            background: linear-gradient(120deg, rgba(34, 197, 94, 0.05) 0%, rgba(14, 165, 233, 0.05) 100%);
            opacity: 0;
            transition: opacity 0.5s ease;
        }
        
        .feature-card:hover::after {
            opacity: 1;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
        }
        
        /* Boutons et éléments interactifs */
        .btn-primary {
            @apply relative overflow-hidden bg-gradient-to-r from-primary-500 to-secondary-500 text-white font-medium py-3.5 px-8 rounded-xl transition-all duration-300 shadow-md hover:shadow-lg;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(120deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transform: translateX(-100%);
            transition: 0.6s;
        }
        
        .btn-primary:hover::before {
            transform: translateX(100%);
        }
        
        .btn-secondary {
            @apply relative overflow-hidden bg-white text-primary-600 border border-primary-200 font-medium py-3.5 px-8 rounded-xl transition-all duration-300 shadow-sm hover:shadow-md hover:bg-primary-50;
        }
        
        .btn-outline {
            @apply relative overflow-hidden bg-transparent text-primary-600 border border-primary-300 font-medium py-3 px-6 rounded-xl transition-all duration-300 hover:bg-primary-50;
        }
        
        /* Icônes et éléments visuels */
        .icon-box {
            @apply w-16 h-16 rounded-2xl bg-gradient-to-br from-primary-100 to-primary-50 flex items-center justify-center mb-6;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 15px -3px rgba(34, 197, 94, 0.1), 0 4px 6px -2px rgba(34, 197, 94, 0.05);
        }
        
        .feature-card:hover .icon-box {
            @apply bg-gradient-to-br from-primary-200 to-primary-100;
            transform: scale(1.1) rotate(10deg);
        }
        
        .icon-box i {
            @apply text-2xl text-primary-600;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .icon-box i {
            @apply text-primary-700;
            transform: scale(1.2);
        }
        
        /* Testimonials */
        .testimonial-card {
            @apply glass-card p-8 relative overflow-hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .testimonial-card::before {
            content: '\201C';
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 120px;
            font-family: 'Georgia', serif;
            color: rgba(34, 197, 94, 0.1);
            line-height: 1;
            z-index: 0;
        }
        
        .testimonial-card:hover {
            transform: translateY(-10px) scale(1.02);
        }
        
        .avatar-border {
            position: relative;
            padding: 4px;
            border-radius: 50%;
            background: linear-gradient(120deg, #22c55e, #0ea5e9);
        }
        
        /* Stats */
        .stat-card {
            @apply glass-card p-6 relative overflow-hidden;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, #22c55e, #0ea5e9);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.5s ease;
        }
        
        .stat-card:hover::after {
            transform: scaleX(1);
        }
        
        /* FAQ */
        .faq-item {
            @apply glass-card mb-5 overflow-hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .faq-item:hover {
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08);
        }
        
        .faq-item.active {
            @apply border-primary-300;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08);
        }
        
        .faq-button {
            @apply w-full text-left py-5 px-6 flex justify-between items-center;
            transition: all 0.3s ease;
        }
        
        .faq-icon-container {
            @apply w-10 h-10 rounded-full flex items-center justify-center transition-all duration-300;
            background: linear-gradient(120deg, rgba(34, 197, 94, 0.1), rgba(14, 165, 233, 0.1));
        }
        
        .faq-item.active .faq-icon-container {
            background: linear-gradient(120deg, #22c55e, #0ea5e9);
        }
        
        .faq-item.active .faq-icon-container i {
            @apply text-white;
        }
        
        .faq-content {
            @apply px-6 pb-5 pt-0;
            background: linear-gradient(120deg, rgba(34, 197, 94, 0.05), rgba(14, 165, 233, 0.05));
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50">
    <?php include_once 'views/components/header.php'; ?>

    <main class="flex-grow">
        <!-- Section héro avec effet moderne -->
        <section class="hero-bg py-28 relative z-10 overflow-hidden">
            <!-- Blobs décoratifs -->
            <div class="blob blob-1"></div>
            <div class="blob blob-2"></div>
            
            <div class="container mx-auto px-6 relative z-10">
                <div class="flex flex-col-reverse lg:flex-row items-center justify-between gap-12">
                    <!-- Texte avec animations -->
                    <div class="lg:w-1/2 text-center lg:text-left" data-aos="fade-up" data-aos-delay="100">
                        <span class="inline-block px-4 py-1.5 rounded-full bg-gradient-to-r from-primary-100 to-secondary-100 text-primary-600 font-medium text-sm mb-6">
                            <i class="fas fa-stethoscope mr-2"></i>Plateforme de santé numérique
                        </span>
                        <h1 class="text-5xl md:text-6xl font-bold leading-tight mb-6 font-display">
                            <span class="text-slate-800">Votre santé,</span><br>
                            <span class="bg-gradient-to-r from-primary-500 to-secondary-500 bg-clip-text text-transparent">notre priorité</span>
                        </h1>
                        <p class="text-xl text-slate-600 mb-8 leading-relaxed max-w-xl">
                            MedConnect révolutionne votre expérience de santé avec des consultations en ligne, 
                            un suivi personnalisé et un accès sécurisé à votre dossier médical.
                        </p>

                        <div class="flex flex-col sm:flex-row sm:space-x-6 space-y-4 sm:space-y-0 justify-center lg:justify-start">
                            <a href="#services" class="btn-primary group">
                                <span>Découvrez nos services</span>
                                <i class="fas fa-arrow-right ml-2 transition-transform duration-300 group-hover:translate-x-1"></i>
                            </a>
                            <a href="views/login.php" class="btn-secondary group">
                                <i class="fas fa-user-md mr-2"></i>
                                <span>Se connecter</span>
                            </a>
                        </div>
                        
                        <!-- Badges de confiance -->
                        <div class="mt-10 flex flex-wrap items-center justify-center lg:justify-start gap-6">
                            <span class="text-sm text-slate-500 font-medium">Approuvé par :</span>
                            <div class="flex space-x-6">
                                <div class="flex items-center">
                                    <i class="fas fa-shield-alt text-primary-500 mr-2"></i>
                                    <span class="text-sm font-medium text-slate-700">Sécurité certifiée</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-check-circle text-primary-500 mr-2"></i>
                                    <span class="text-sm font-medium text-slate-700">Médecins vérifiés</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Image avec effet moderne -->
                    <div class="lg:w-1/2" data-aos="fade-up" data-aos-delay="200">
                        <div class="relative">
                            <!-- Cercle décoratif -->
                            <div class="absolute -top-10 -left-10 w-40 h-40 rounded-full bg-gradient-to-br from-primary-200 to-secondary-100 opacity-50 blur-2xl"></div>
                            <div class="absolute -bottom-10 -right-10 w-60 h-60 rounded-full bg-gradient-to-br from-secondary-200 to-primary-100 opacity-50 blur-2xl"></div>
                            
                            <!-- Image principale -->
                            <div class="glass-card p-2 relative z-10 overflow-hidden">
                                <img src="./assets/images/home.jpg" 
                                    alt="Équipe médicale illustration" 
                                    class="w-full h-auto rounded-xl shadow-xl object-cover animate-float">
                                
                                <!-- Badge flottant 1 -->
                                <div class="absolute top-5 -left-3 glass-card py-2 px-4 flex items-center space-x-2 animate-float" style="animation-delay: 1s;">
                                    <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center">
                                        <i class="fas fa-calendar-check text-primary-600"></i>
                                    </div>
                                    <span class="text-sm font-medium text-slate-700">Rendez-vous faciles</span>
                                </div>
                                
                                <!-- Badge flottant 2 -->
                                <div class="absolute bottom-5 -right-3 glass-card py-2 px-4 flex items-center space-x-2 animate-float" style="animation-delay: 2s;">
                                    <div class="w-8 h-8 rounded-full bg-secondary-100 flex items-center justify-center">
                                        <i class="fas fa-video text-secondary-600"></i>
                                    </div>
                                    <span class="text-sm font-medium text-slate-700">Téléconsultation HD</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section statistiques avec glassmorphism -->
        <section class="py-16 relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-r from-primary-50 to-secondary-50 opacity-50"></div>
            
            <div class="container mx-auto px-4 relative z-10">
                <div class="text-center mb-12" data-aos="fade-up">
                    <span class="inline-block px-4 py-1.5 rounded-full bg-gradient-to-r from-primary-100 to-secondary-100 text-primary-600 font-medium text-sm mb-4">
                        <i class="fas fa-chart-line mr-2"></i>Nos chiffres clés
                    </span>
                    <h2 class="section-title">MedConnect en chiffres</h2>
                    <p class="section-subtitle mx-auto">Découvrez l'impact de notre plateforme sur la santé numérique</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Stat 1 -->
                    <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
                        <div class="flex items-center space-x-6 p-6">
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-primary-100 to-primary-50 flex items-center justify-center">
                                <i class="fas fa-user-md text-2xl text-primary-600"></i>
                            </div>
                            <div>
                                <div class="text-4xl font-bold bg-gradient-to-r from-primary-600 to-primary-500 bg-clip-text text-transparent">
                                    <span class="counter" data-target="1500">0</span>+
                                </div>
                                <div class="text-slate-600 font-medium">Médecins qualifiés</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Stat 2 -->
                    <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
                        <div class="flex items-center space-x-6 p-6">
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-secondary-100 to-secondary-50 flex items-center justify-center">
                                <i class="fas fa-users text-2xl text-secondary-600"></i>
                            </div>
                            <div>
                                <div class="text-4xl font-bold bg-gradient-to-r from-secondary-600 to-secondary-500 bg-clip-text text-transparent">
                                    <span class="counter" data-target="20000">0</span>+
                                </div>
                                <div class="text-slate-600 font-medium">Patients satisfaits</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Stat 3 -->
                    <div class="stat-card" data-aos="fade-up" data-aos-delay="300">
                        <div class="flex items-center space-x-6 p-6">
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-primary-100 to-secondary-100 flex items-center justify-center">
                                <i class="fas fa-stethoscope text-2xl text-primary-600"></i>
                            </div>
                            <div>
                                <div class="text-4xl font-bold bg-gradient-to-r from-primary-500 to-secondary-500 bg-clip-text text-transparent">
                                    <span class="counter" data-target="30">0</span>+
                                </div>
                                <div class="text-slate-600 font-medium">Spécialités médicales</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section services avec glassmorphism -->
        <section id="services" class="py-24 relative overflow-hidden">
            <!-- Formes décoratives -->
            <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-primary-200 to-secondary-100 rounded-full -translate-y-1/2 translate-x-1/3 opacity-20 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-gradient-to-tr from-secondary-200 to-primary-100 rounded-full translate-y-1/2 -translate-x-1/3 opacity-20 blur-3xl"></div>
            
            <div class="container mx-auto px-4 max-w-7xl relative z-10">
                <div class="text-center mb-20" data-aos="fade-up">
                    <span class="inline-block px-4 py-1.5 rounded-full bg-gradient-to-r from-primary-100 to-secondary-100 text-primary-600 font-medium text-sm mb-4">
                        <i class="fas fa-star mr-2"></i>Nos services
                    </span>
                    <h2 class="section-title">Une expérience médicale révolutionnaire</h2>
                    <p class="section-subtitle mx-auto">
                        Découvrez comment nous transformons votre parcours de soins avec des solutions innovantes et personnalisées pour une santé connectée.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 xl:gap-12 max-w-6xl mx-auto">
                    <!-- Carte 1 -->
                    <div class="feature-card group" data-aos="fade-up" data-aos-delay="100">
                        <div class="icon-box mx-auto">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-800 mb-4 text-center group-hover:text-primary-700 transition-colors duration-300">Consultations intelligentes</h3>
                        <p class="text-slate-600 text-center mb-6">
                            Planifiez vos rendez-vous en quelques clics avec notre système de prise de rendez-vous intelligent qui s'adapte à vos disponibilités.
                        </p>
                        <ul class="space-y-3 mb-6">
                            <li class="flex items-center text-slate-700 bg-gradient-to-r from-primary-50 to-transparent p-2 rounded-lg">
                                <div class="w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-check text-xs text-primary-600"></i>
                                </div>
                                <span>Disponible 24h/24 et 7j/7</span>
                            </li>
                            <li class="flex items-center text-slate-700 bg-gradient-to-r from-primary-50 to-transparent p-2 rounded-lg">
                                <div class="w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-check text-xs text-primary-600"></i>
                                </div>
                                <span>Rappels et notifications intelligents</span>
                            </li>
                        </ul>
                        <div class="text-center">
                            <a href="#" class="btn-outline inline-flex items-center text-sm">
                                <span>En savoir plus</span>
                                <i class="fas fa-arrow-right ml-2 transition-transform duration-300 group-hover:translate-x-1"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Carte 2 -->
                    <div class="feature-card group" data-aos="fade-up" data-aos-delay="200">
                        <div class="icon-box mx-auto">
                            <i class="fas fa-video"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-800 mb-4 text-center group-hover:text-primary-700 transition-colors duration-300">Téléconsultation premium</h3>
                        <p class="text-slate-600 text-center mb-6">
                            Consultez des médecins qualifiés depuis chez vous avec une expérience de téléconsultation haute qualité et sécurisée.
                        </p>
                        <ul class="space-y-3 mb-6">
                            <li class="flex items-center text-slate-700 bg-gradient-to-r from-secondary-50 to-transparent p-2 rounded-lg">
                                <div class="w-6 h-6 rounded-full bg-secondary-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-check text-xs text-secondary-600"></i>
                                </div>
                                <span>Vidéo HD sécurisée de bout en bout</span>
                            </li>
                            <li class="flex items-center text-slate-700 bg-gradient-to-r from-secondary-50 to-transparent p-2 rounded-lg">
                                <div class="w-6 h-6 rounded-full bg-secondary-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-check text-xs text-secondary-600"></i>
                                </div>
                                <span>Support technique 24/7 disponible</span>
                            </li>
                        </ul>
                        <div class="text-center">
                            <a href="#" class="btn-outline inline-flex items-center text-sm">
                                <span>En savoir plus</span>
                                <i class="fas fa-arrow-right ml-2 transition-transform duration-300 group-hover:translate-x-1"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Carte 3 -->
                    <div class="feature-card group" data-aos="fade-up" data-aos-delay="300">
                        <div class="icon-box mx-auto">
                            <i class="fas fa-file-medical"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-800 mb-4 text-center group-hover:text-primary-700 transition-colors duration-300">Dossier médical digital</h3>
                        <p class="text-slate-600 text-center mb-6">
                            Accédez à votre dossier médical complet et partagez-le en toute sécurité avec vos médecins pour un suivi optimal.
                        </p>
                        <ul class="space-y-3 mb-6">
                            <li class="flex items-center text-slate-700 bg-gradient-to-r from-primary-50 to-secondary-50 p-2 rounded-lg">
                                <div class="w-6 h-6 rounded-full bg-gradient-to-r from-primary-100 to-secondary-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-check text-xs text-primary-600"></i>
                                </div>
                                <span>Historique médical complet et sécurisé</span>
                            </li>
                            <li class="flex items-center text-slate-700 bg-gradient-to-r from-primary-50 to-secondary-50 p-2 rounded-lg">
                                <div class="w-6 h-6 rounded-full bg-gradient-to-r from-primary-100 to-secondary-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-check text-xs text-primary-600"></i>
                                </div>
                                <span>Partage sécurisé avec vos médecins</span>
                            </li>
                        </ul>
                        <div class="text-center">
                            <a href="#" class="btn-outline inline-flex items-center text-sm">
                                <span>En savoir plus</span>
                                <i class="fas fa-arrow-right ml-2 transition-transform duration-300 group-hover:translate-x-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Bouton d'action -->
                <div class="text-center mt-16" data-aos="fade-up" data-aos-delay="400">
                    <a href="#" class="btn-primary inline-flex items-center">
                        <span>Explorer tous nos services</span>
                        <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>
        </section>

        <!-- Section médecins avec design moderne -->
        <section id="doctors" class="py-24 relative overflow-hidden">
            <!-- Formes décoratives -->
            <div class="absolute inset-0 bg-gradient-to-br from-slate-50 to-white"></div>
            <div class="absolute top-40 left-0 w-72 h-72 bg-primary-100 rounded-full opacity-30 blur-3xl"></div>
            <div class="absolute bottom-40 right-0 w-80 h-80 bg-secondary-100 rounded-full opacity-30 blur-3xl"></div>
            
            <div class="container mx-auto px-4 relative z-10">
                <div class="text-center mb-20" data-aos="fade-up">
                    <span class="inline-block px-4 py-1.5 rounded-full bg-gradient-to-r from-primary-100 to-secondary-100 text-primary-600 font-medium text-sm mb-4">
                        <i class="fas fa-user-md mr-2"></i>Notre équipe
                    </span>
                    <h2 class="section-title">Nos médecins experts</h2>
                    <p class="section-subtitle mx-auto">
                        Une équipe de médecins qualifiés dans différentes spécialités, prêts à vous accompagner pour tous vos besoins de santé.
                    </p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <!-- Médecin 1 -->
                    <div class="glass-card group" data-aos="fade-up" data-aos-delay="100">
                        <div class="relative overflow-hidden rounded-t-xl">
                            <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="Dr. Emilie Laurent" 
                                class="w-full h-64 object-cover transition-transform duration-700 group-hover:scale-110">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            <!-- Badge de spécialité -->
                            <div class="absolute top-4 right-4 glass px-3 py-1 rounded-full text-sm font-medium text-primary-700">
                                <i class="fas fa-heartbeat mr-1"></i> Cardiologue
                            </div>
                        </div>
                        
                        <div class="p-6 relative">
                            <!-- Avatar avec bordure gradient -->
                            <div class="avatar-border absolute -top-10 left-1/2 transform -translate-x-1/2">
                                <img src="https://randomuser.me/api/portraits/women/44.jpg" 
                                    alt="Dr. Emilie Laurent" 
                                    class="w-20 h-20 rounded-full object-cover border-4 border-white">
                            </div>
                            
                            <div class="mt-12 text-center">
                                <h3 class="text-xl font-bold text-slate-800 mb-1">Dr. Emilie Laurent</h3>
                                <p class="bg-gradient-to-r from-primary-500 to-primary-600 bg-clip-text text-transparent font-medium mb-3">
                                    Cardiologue
                                </p>
                                <div class="flex items-center justify-center mb-4">
                                    <div class="text-yellow-400 flex">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star-half-alt"></i>
                                    </div>
                                    <span class="text-slate-600 ml-2 text-sm font-medium">4.8/5</span>
                                </div>
                                <div class="flex justify-center space-x-3 mb-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                                        <i class="fas fa-check-circle mr-1"></i> Vérifié
                                    </span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <i class="fas fa-video mr-1"></i> Téléconsultation
                                    </span>
                                </div>
                                <button class="w-full py-3 btn-primary group-hover:shadow-lg transition-all duration-300">
                                    <i class="fas fa-user-md mr-2"></i> Voir le profil
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Médecin 2 -->
                    <div class="glass-card group" data-aos="fade-up" data-aos-delay="200">
                        <div class="relative overflow-hidden rounded-t-xl">
                            <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Dr. Thomas Petit" 
                                class="w-full h-64 object-cover transition-transform duration-700 group-hover:scale-110">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            <!-- Badge de spécialité -->
                            <div class="absolute top-4 right-4 glass px-3 py-1 rounded-full text-sm font-medium text-primary-700">
                                <i class="fas fa-allergies mr-1"></i> Dermatologue
                            </div>
                        </div>
                        
                        <div class="p-6 relative">
                            <!-- Avatar avec bordure gradient -->
                            <div class="avatar-border absolute -top-10 left-1/2 transform -translate-x-1/2">
                                <img src="https://randomuser.me/api/portraits/men/32.jpg" 
                                    alt="Dr. Thomas Petit" 
                                    class="w-20 h-20 rounded-full object-cover border-4 border-white">
                            </div>
                            
                            <div class="mt-12 text-center">
                                <h3 class="text-xl font-bold text-slate-800 mb-1">Dr. Thomas Petit</h3>
                                <p class="bg-gradient-to-r from-primary-500 to-primary-600 bg-clip-text text-transparent font-medium mb-3">
                                    Dermatologue
                                </p>
                                <div class="flex items-center justify-center mb-4">
                                    <div class="text-yellow-400 flex">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <span class="text-slate-600 ml-2 text-sm font-medium">5.0/5</span>
                                </div>
                                <div class="flex justify-center space-x-3 mb-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                                        <i class="fas fa-check-circle mr-1"></i> Vérifié
                                    </span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <i class="fas fa-video mr-1"></i> Téléconsultation
                                    </span>
                                </div>
                                <button class="w-full py-3 btn-primary group-hover:shadow-lg transition-all duration-300">
                                    <i class="fas fa-user-md mr-2"></i> Voir le profil
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Médecin 3 -->
                    <div class="glass-card group" data-aos="fade-up" data-aos-delay="300">
                        <div class="relative overflow-hidden rounded-t-xl">
                            <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="Dr. Sophie Martin" 
                                class="w-full h-64 object-cover transition-transform duration-700 group-hover:scale-110">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            <!-- Badge de spécialité -->
                            <div class="absolute top-4 right-4 glass px-3 py-1 rounded-full text-sm font-medium text-primary-700">
                                <i class="fas fa-baby mr-1"></i> Pédiatre
                            </div>
                        </div>
                        
                        <div class="p-6 relative">
                            <!-- Avatar avec bordure gradient -->
                            <div class="avatar-border absolute -top-10 left-1/2 transform -translate-x-1/2">
                                <img src="https://randomuser.me/api/portraits/women/68.jpg" 
                                    alt="Dr. Sophie Martin" 
                                    class="w-20 h-20 rounded-full object-cover border-4 border-white">
                            </div>
                            
                            <div class="mt-12 text-center">
                                <h3 class="text-xl font-bold text-slate-800 mb-1">Dr. Sophie Martin</h3>
                                <p class="bg-gradient-to-r from-primary-500 to-primary-600 bg-clip-text text-transparent font-medium mb-3">
                                    Pédiatre
                                </p>
                                <div class="flex items-center justify-center mb-4">
                                    <div class="text-yellow-400 flex">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="far fa-star"></i>
                                    </div>
                                    <span class="text-slate-600 ml-2 text-sm font-medium">4.1/5</span>
                                </div>
                                <div class="flex justify-center space-x-3 mb-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                                        <i class="fas fa-check-circle mr-1"></i> Vérifié
                                    </span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <i class="fas fa-video mr-1"></i> Téléconsultation
                                    </span>
                                </div>
                                <button class="w-full py-3 btn-primary group-hover:shadow-lg transition-all duration-300">
                                    <i class="fas fa-user-md mr-2"></i> Voir le profil
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Médecin 4 -->
                    <div class="glass-card group" data-aos="fade-up" data-aos-delay="400">
                        <div class="relative overflow-hidden rounded-t-xl">
                            <img src="https://randomuser.me/api/portraits/men/86.jpg" alt="Dr. Nicolas Dubois" 
                                class="w-full h-64 object-cover transition-transform duration-700 group-hover:scale-110">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            <!-- Badge de spécialité -->
                            <div class="absolute top-4 right-4 glass px-3 py-1 rounded-full text-sm font-medium text-primary-700">
                                <i class="fas fa-stethoscope mr-1"></i> Généraliste
                            </div>
                        </div>
                        
                        <div class="p-6 relative">
                            <!-- Avatar avec bordure gradient -->
                            <div class="avatar-border absolute -top-10 left-1/2 transform -translate-x-1/2">
                                <img src="https://randomuser.me/api/portraits/men/86.jpg" 
                                    alt="Dr. Nicolas Dubois" 
                                    class="w-20 h-20 rounded-full object-cover border-4 border-white">
                            </div>
                            
                            <div class="mt-12 text-center">
                                <h3 class="text-xl font-bold text-slate-800 mb-1">Dr. Nicolas Dubois</h3>
                                <p class="bg-gradient-to-r from-primary-500 to-primary-600 bg-clip-text text-transparent font-medium mb-3">
                                    Généraliste
                                </p>
                                <div class="flex items-center justify-center mb-4">
                                    <div class="text-yellow-400 flex">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star-half-alt"></i>
                                    </div>
                                    <span class="text-slate-600 ml-2 text-sm font-medium">4.7/5</span>
                                </div>
                                <div class="flex justify-center space-x-3 mb-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                                        <i class="fas fa-check-circle mr-1"></i> Vérifié
                                    </span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <i class="fas fa-video mr-1"></i> Téléconsultation
                                    </span>
                                </div>
                                <button class="w-full py-3 btn-primary group-hover:shadow-lg transition-all duration-300">
                                    <i class="fas fa-user-md mr-2"></i> Voir le profil
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Bouton d'action -->
                <div class="text-center mt-16" data-aos="fade-up" data-aos-delay="500">
                    <a href="#" class="btn-secondary inline-flex items-center">
                        <i class="fas fa-search mr-2"></i>
                        <span>Voir tous nos médecins</span>
                    </a>
                </div>
            </div>
        </section>

        <!-- Section témoignages -->
        <section id="testimonials" class="py-24 bg-white">
            <div class="container mx-auto px-4">
                <div class="text-center mb-20" data-aos="fade-up">
                    <span class="text-green-500 font-semibold mb-2 inline-block">TÉMOIGNAGES</span>
                    <h2 class="section-title text-4xl font-bold text-[#166534] mb-6">Ce que disent nos patients</h2>
                    <p class="text-xl text-[#166534] max-w-3xl mx-auto">
                        Découvrez les expériences de nos patients avec MedConnect et comment notre plateforme a transformé leur accès aux soins de santé.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                    <!-- Témoignage 1 -->
                    <div class="testimonial-card group" data-aos="fade-up" data-aos-delay="100">
                        <div class="relative mb-6">
                            <div class="absolute -top-4 -left-4 w-12 h-12 bg-green-100 rounded-full flex items-center justify-center text-green-600">
                                <i class="fas fa-quote-left text-xl"></i>
                            </div>
                            <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150&h=150&fit=crop" 
                                alt="Kossiwa Mensah" 
                                class="w-20 h-20 rounded-full object-cover border-4 border-white shadow-md mx-auto transform group-hover:scale-110 transition-all duration-300">
                        </div>
                        <div class="text-center mb-4">
                            <h4 class="text-lg font-semibold text-[#166534]">Kossiwa Mensah</h4>
                            <p class="text-green-600 text-sm">Patient, Cotonou</p>
                            <div class="flex justify-center mt-2">
                                <i class="fas fa-star text-yellow-400"></i>
                                <i class="fas fa-star text-yellow-400"></i>
                                <i class="fas fa-star text-yellow-400"></i>
                                <i class="fas fa-star text-yellow-400"></i>
                                <i class="fas fa-star text-yellow-400"></i>
                            </div>
                        </div>
                        <p class="text-[#166534] text-base italic text-center">
                            "MedConnect a révolutionné ma façon de consulter. Plus besoin de me déplacer, je peux prendre rendez-vous en quelques clics. Le service est excellent et les médecins très professionnels."
                        </p>
                    </div>

                    <!-- Témoignage 2 -->
                    <div class="testimonial-card group" data-aos="fade-up" data-aos-delay="200">
                        <div class="relative mb-6">
                            <div class="absolute -top-4 -left-4 w-12 h-12 bg-green-100 rounded-full flex items-center justify-center text-green-600">
                                <i class="fas fa-quote-left text-xl"></i>
                            </div>
                            <img src="https://images.unsplash.com/photo-1580489944761-15a19d654956?w=150&h=150&fit=crop" 
                                alt="Aminata Diallo" 
                                class="w-20 h-20 rounded-full object-cover border-4 border-white shadow-md mx-auto transform group-hover:scale-110 transition-all duration-300">
                        </div>
                        <div class="text-center mb-4">
                            <h4 class="text-lg font-semibold text-[#166534]">Aminata Diallo</h4>
                            <p class="text-green-600 text-sm">Patient, Porto-Novo</p>
                            <div class="flex justify-center mt-2">
                                <i class="fas fa-star text-yellow-400"></i>
                                <i class="fas fa-star text-yellow-400"></i>
                                <i class="fas fa-star text-yellow-400"></i>
                                <i class="fas fa-star text-yellow-400"></i>
                                <i class="fas fa-star-half-alt text-yellow-400"></i>
                            </div>
                        </div>
                        <p class="text-[#166534] text-base italic text-center">
                            "La téléconsultation est vraiment pratique. J'ai pu consulter un spécialiste sans avoir à me déplacer. Le suivi est excellent et les médecins sont très à l'écoute."
                        </p>
                    </div>

                    <!-- Témoignage 3 -->
                    <div class="testimonial-card group" data-aos="fade-up" data-aos-delay="300">
                        <div class="relative mb-6">
                            <div class="absolute -top-4 -left-4 w-12 h-12 bg-green-100 rounded-full flex items-center justify-center text-green-600">
                                <i class="fas fa-quote-left text-xl"></i>
                            </div>
                            <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?w=150&h=150&fit=crop" 
                                alt="Kofi Agbeko" 
                                class="w-20 h-20 rounded-full object-cover border-4 border-white shadow-md mx-auto transform group-hover:scale-110 transition-all duration-300">
                        </div>
                        <div class="text-center mb-4">
                            <h4 class="text-lg font-semibold text-[#166534]">Kofi Agbeko</h4>
                            <p class="text-green-600 text-sm">Patient, Abomey-Calavi</p>
                            <div class="flex justify-center mt-2">
                                <i class="fas fa-star text-yellow-400"></i>
                                <i class="fas fa-star text-yellow-400"></i>
                                <i class="fas fa-star text-yellow-400"></i>
                                <i class="fas fa-star text-yellow-400"></i>
                                <i class="fas fa-star text-yellow-400"></i>
                            </div>
                        </div>
                        <p class="text-[#166534] text-base italic text-center">
                            "Le suivi de mon dossier médical est beaucoup plus simple. Je peux accéder à mes résultats et prescriptions en un clic. La plateforme est vraiment bien pensée."
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section FAQ -->
        <section id="faq" class="py-24 bg-gradient-to-b from-white via-[#f0fdf4] to-[#dcfce7]">
            <div class="container mx-auto px-4 max-w-4xl">
                <div class="text-center mb-20" data-aos="fade-up">
                    <span class="text-green-500 font-semibold mb-2 inline-block">FAQ</span>
                    <h2 class="section-title text-4xl font-bold text-[#166534] mb-6">Questions fréquentes</h2>
                    <p class="text-xl text-[#166534] max-w-3xl mx-auto">
                        Retrouvez les réponses aux questions les plus courantes sur nos services et notre plateforme.
                    </p>
                </div>

                <div class="space-y-4" data-aos="fade-up" data-aos-delay="100">
                    <!-- Question 1 -->
                    <div class="faq-item overflow-hidden">
                        <button class="flex justify-between items-center w-full text-left p-4" onclick="toggleFAQ(this)">
                            <h3 class="text-xl font-semibold text-[#166534]">Comment prendre rendez-vous avec un médecin ?</h3>
                            <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center transition-transform duration-300">
                                <i class="fas fa-chevron-down text-green-600"></i>
                            </div>
                        </button>
                        <div class="overflow-hidden max-h-0 transition-all duration-300 ease-in-out">
                            <div class="p-4 pt-0 text-[#166534] bg-green-50 rounded-b-xl">
                                <p>Pour prendre rendez-vous, il suffit de créer un compte patient, de choisir un médecin parmi notre réseau et de sélectionner un créneau disponible. Notre système de prise de rendez-vous est disponible 24h/24 et 7j/7.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Question 2 -->
                    <div class="faq-item overflow-hidden" data-aos="fade-up" data-aos-delay="150">
                        <button class="flex justify-between items-center w-full text-left p-4" onclick="toggleFAQ(this)">
                            <h3 class="text-xl font-semibold text-[#166534]">Les consultations sont-elles remboursées ?</h3>
                            <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center transition-transform duration-300">
                                <i class="fas fa-chevron-down text-green-600"></i>
                            </div>
                        </button>
                        <div class="overflow-hidden max-h-0 transition-all duration-300 ease-in-out">
                            <div class="p-4 pt-0 text-[#166534] bg-green-50 rounded-b-xl">
                                <p>Oui, les consultations sont remboursées par l'Assurance Maladie dans les mêmes conditions qu'une consultation en cabinet. Nous vous fournissons une feuille de soins électronique que vous pouvez transmettre à votre mutuelle pour le remboursement complémentaire.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Question 3 -->
                    <div class="faq-item overflow-hidden" data-aos="fade-up" data-aos-delay="200">
                        <button class="flex justify-between items-center w-full text-left p-4" onclick="toggleFAQ(this)">
                            <h3 class="text-xl font-semibold text-[#166534]">Comment accéder à mon dossier médical ?</h3>
                            <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center transition-transform duration-300">
                                <i class="fas fa-chevron-down text-green-600"></i>
                            </div>
                        </button>
                        <div class="overflow-hidden max-h-0 transition-all duration-300 ease-in-out">
                            <div class="p-4 pt-0 text-[#166534] bg-green-50 rounded-b-xl">
                                <p>Votre dossier médical est accessible depuis votre espace patient après connexion. Vous y trouverez l'historique de vos consultations, vos ordonnances, vos résultats d'analyses et tous vos documents médicaux partagés par vos médecins.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Question 4 -->
                    <div class="faq-item overflow-hidden" data-aos="fade-up" data-aos-delay="250">
                        <button class="flex justify-between items-center w-full text-left p-4" onclick="toggleFAQ(this)">
                            <h3 class="text-xl font-semibold text-[#166534]">Comment devenir médecin sur MedConnect ?</h3>
                            <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center transition-transform duration-300">
                                <i class="fas fa-chevron-down text-green-600"></i>
                            </div>
                        </button>
                        <div class="overflow-hidden max-h-0 transition-all duration-300 ease-in-out">
                            <div class="p-4 pt-0 text-[#166534] bg-green-50 rounded-b-xl">
                                <p>Pour rejoindre notre réseau de médecins, créez un compte médecin et fournissez les documents nécessaires (diplômes, numéro professionnel). Notre équipe vérifiera vos informations et vous accompagnera dans la mise en place de votre espace de consultation.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include_once 'views/components/footer.php'; ?>

    <!-- Script JS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Navigation mobile
            const menuButton = document.querySelector('.md\\:hidden');
            const menuItems = document.querySelector('.md\\:flex');
            
            if (menuButton && menuItems) {
                menuButton.addEventListener('click', function() {
                    menuItems.classList.toggle('hidden');
                });
            }
            
            // Redirection si nécessaire
            <?php if(isset($redirect) && $redirect): ?>
            setTimeout(function() {
                window.location.href = '<?php echo $redirect_url; ?>';
            }, 100);
            <?php endif; ?>

            // Initialisation des FAQ
            initFAQ();
        });

        // Fonction pour gérer l'accordéon FAQ
        function toggleFAQ(button) {
            const faqItem = button.closest('.faq-item');
            const content = button.nextElementSibling;
            const icon = button.querySelector('i');
            const iconContainer = button.querySelector('div');
            
            // Fermer tous les autres FAQ items
            document.querySelectorAll('.faq-item.active').forEach(item => {
                if (item !== faqItem) {
                    const itemContent = item.querySelector('.overflow-hidden.max-h-0');
                    const itemIcon = item.querySelector('button i');
                    const itemIconContainer = item.querySelector('button div');
                    
                    item.classList.remove('active');
                    itemContent.style.maxHeight = '0px';
                    itemIcon.style.transform = 'rotate(0deg)';
                    if (itemIconContainer) itemIconContainer.style.backgroundColor = '#dcfce7';
                }
            });
            
            // Toggle l'élément actuel
            if (content.style.maxHeight === '0px' || !content.style.maxHeight) {
                faqItem.classList.add('active');
                content.style.maxHeight = content.scrollHeight + 'px';
                icon.style.transform = 'rotate(180deg)';
                if (iconContainer) iconContainer.style.backgroundColor = '#22c55e';
                if (iconContainer) iconContainer.style.color = 'white';
            } else {
                faqItem.classList.remove('active');
                content.style.maxHeight = '0px';
                icon.style.transform = 'rotate(0deg)';
                if (iconContainer) iconContainer.style.backgroundColor = '#dcfce7';
                if (iconContainer) iconContainer.style.color = '#22c55e';
            }
        }

        // Initialisation des FAQ
        function initFAQ() {
            const faqItems = document.querySelectorAll('.faq-item');
            faqItems.forEach(item => {
                const content = item.querySelector('.overflow-hidden');
                if (content) content.style.maxHeight = '0px';
            });
        }
    </script>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialisation des animations AOS
        AOS.init({
            duration: 800,
            once: true,
            offset: 100,
            easing: 'ease-in-out'
        });

        // Fonction pour formater les nombres avec des espaces
        function formatNumber(number) {
            return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
        }

        // Fonction pour animer les compteurs
        function animateCounter(element) {
            const target = parseInt(element.getAttribute('data-target'));
            const duration = 2000; // 2 secondes
            const step = target / (duration / 16); // 60fps
            let current = 0;

            const timer = setInterval(() => {
                current += step;
                if (current >= target) {
                    element.textContent = formatNumber(target);
                    clearInterval(timer);
                } else {
                    element.textContent = formatNumber(Math.floor(current));
                }
            }, 16);
        }

        // Initialiser les compteurs avec Waypoints
        $(document).ready(function() {
            $('.counter').each(function() {
                const element = this;
                new Waypoint({
                    element: element,
                    handler: function() {
                        animateCounter(element);
                        this.destroy(); // Ne déclencher qu'une seule fois
                    },
                    offset: '90%'
                });
            });
            
            // Smooth scroll pour les liens d'ancrage
            $('a[href^="#"]').on('click', function(e) {
                e.preventDefault();
                const target = $(this.getAttribute('href'));
                if (target.length) {
                    $('html, body').animate({
                        scrollTop: target.offset().top - 100
                    }, 800, 'swing');
                }
            });
        });
    </script>

</body>
</html> 