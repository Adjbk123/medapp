<?php
// Récupérer le nom du fichier actuel pour marquer le lien actif
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Barre latérale -->
<div class="w-64 glass-card m-4 rounded-2xl border-0 overflow-hidden flex flex-col justify-between">
    <div>
        <div class="p-6 flex items-center justify-center">
            <div data-aos="zoom-in" class="flex items-center space-x-3">
                <img src="<?php echo (file_exists('../../assets/img/logo.png')) ? '../../assets/img/logo.png' : '../assets/img/logo.png'; ?>" alt="MedConnect Logo" class="h-10">
                <h1 class="text-xl font-bold text-primary-700 font-heading">MedConnect</h1>
            </div>
        </div>
        
        <div class="px-4 py-2">
            <p class="text-xs uppercase text-gray-500 font-medium tracking-wider mb-2 pl-4">Menu principal</p>
            <div class="space-y-1">
                <a href="dashboard.php" class="nav-link flex items-center px-4 py-3 text-gray-700 rounded-lg <?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>" data-aos="fade-right" data-aos-delay="100">
                    <div class="icon-circle <?php echo ($current_page === 'dashboard.php') ? 'bg-primary-100' : 'bg-gray-100'; ?> text-primary-600 mr-3">
                        <i class="fas fa-home"></i>
                    </div>
                    <span>Tableau de bord</span>
                </a>
                <a href="patients.php" class="nav-link flex items-center px-4 py-3 text-gray-700 rounded-lg <?php echo ($current_page === 'patients.php') ? 'active' : ''; ?>" data-aos="fade-right" data-aos-delay="150">
                    <div class="icon-circle <?php echo ($current_page === 'patients.php') ? 'bg-primary-100' : 'bg-gray-100'; ?> text-primary-600 mr-3">
                        <i class="fas fa-users"></i>
                    </div>
                    <span>Patients</span>
                </a>
                <a href="consultations.php" class="nav-link flex items-center px-4 py-3 text-gray-700 rounded-lg <?php echo ($current_page === 'consultations.php') ? 'active' : ''; ?>" data-aos="fade-right" data-aos-delay="200">
                    <div class="icon-circle <?php echo ($current_page === 'consultations.php') ? 'bg-primary-100' : 'bg-gray-100'; ?> text-primary-600 mr-3">
                        <i class="fas fa-stethoscope"></i>
                    </div>
                    <span>Consultations</span>
                </a>
                <a href="ordonnances.php" class="nav-link flex items-center px-4 py-3 text-gray-700 rounded-lg <?php echo ($current_page === 'ordonnances.php') ? 'active' : ''; ?>" data-aos="fade-right" data-aos-delay="250">
                    <div class="icon-circle <?php echo ($current_page === 'ordonnances.php') ? 'bg-primary-100' : 'bg-gray-100'; ?> text-primary-600 mr-3">
                        <i class="fas fa-prescription"></i>
                    </div>
                    <span>Ordonnances</span>
                </a>
                <a href="rdv.php" class="nav-link flex items-center px-4 py-3 text-gray-700 rounded-lg <?php echo ($current_page === 'rendez_vous.php' || $current_page === 'rdv.php') ? 'active' : ''; ?>" data-aos="fade-right" data-aos-delay="300">
                    <div class="icon-circle <?php echo ($current_page === 'rendez_vous.php' || $current_page === 'rdv.php') ? 'bg-primary-100' : 'bg-gray-100'; ?> text-primary-600 mr-3">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <span>Rendez-vous</span>
                </a>
                <a href="messages.php" class="nav-link flex items-center px-4 py-3 text-gray-700 rounded-lg <?php echo ($current_page === 'messages.php') ? 'active' : ''; ?>" data-aos="fade-right" data-aos-delay="350">
                    <div class="icon-circle <?php echo ($current_page === 'messages.php') ? 'bg-primary-100' : 'bg-gray-100'; ?> text-primary-600 mr-3">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <span>Messages</span>
                </a>
                <a href="profile_medecin.php" class="nav-link flex items-center px-4 py-3 text-gray-700 rounded-lg <?php echo ($current_page === 'profile_medecin.php') ? 'active' : ''; ?>" data-aos="fade-right" data-aos-delay="400">
                    <div class="icon-circle <?php echo ($current_page === 'profile_medecin.php') ? 'bg-primary-100' : 'bg-gray-100'; ?> text-primary-600 mr-3">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <span>Mon Profil</span>
                </a>
            </div>
        </div>
    </div>
    
    <div class="p-4 mt-auto">
        <a href="<?php echo (file_exists('../logout.php')) ? '../logout.php' : '../../logout.php'; ?>" class="flex items-center px-4 py-3 text-red-500 hover:text-red-700 rounded-lg hover:bg-red-50 transition-all duration-300">
            <div class="icon-circle bg-red-100 text-red-500 mr-3">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <span>Déconnexion</span>
        </a>
    </div>
</div>
