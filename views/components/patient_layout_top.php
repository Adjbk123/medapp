<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/session.php';
requireLogin();
requireRole('patient');

$id_patient = $_SESSION['user_id'];
$nom = $_SESSION['nom'];
$prenom = $_SESSION['prenom'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'MedConnect'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f9f5;
        }
        .nav-link {
            transition: all 0.3s ease;
        }
        .nav-link:hover {
            background-color: rgba(59, 130, 246, 0.1);
            transform: translateX(5px);
        }
        .nav-link.active {
            background-color: rgba(59, 130, 246, 0.2);
            border-left: 4px solid #3b82f6;
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        /* Fix for sticky sidebar to ensure it doesn't scroll with content */
        aside {
            height: 100vh;
            position: sticky;
            top: 0;
            flex-shrink: 0;
        }
        .main-content {
            flex-grow: 1;
            min-height: 100vh;
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-[#EFF6FF] to-[#DBEAFE] min-h-screen">
    <div class="flex min-h-screen">
        <?php include_once __DIR__ . '/patient_sidebar.php'; ?>
        
        <div class="main-content flex flex-col">
            <!-- En-tête -->
            <header class="bg-white shadow-sm sticky top-0 z-10">
                <div class="container mx-auto px-4 py-4 flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#3b82f6] to-[#60a5fa] flex items-center justify-center">
                            <i class="<?php echo $header_icon ?? 'fas fa-user'; ?> text-white text-xl"></i>
                        </div>
                        <h1 class="text-2xl font-bold text-[#1e40af]"><?php echo $header_title ?? ($prenom . ' ' . $nom); ?></h1>
                    </div>
                    <div class="text-sm text-[#3b82f6]">
                        <i class="fas fa-calendar-alt mr-2"></i><?php echo date('d/m/Y'); ?>
                    </div>
                </div>
            </header>
            
            <main class="flex-1 container mx-auto px-4 py-8">
