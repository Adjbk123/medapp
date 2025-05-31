<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="w-64 glass min-h-screen flex flex-col py-6 px-4">
    <div class="flex items-center justify-center mb-10">
        <div class="icon-circle">
            <i class="fas fa-heartbeat icon-gradient text-xl"></i>
        </div>
        <h1 class="sidebar-logo text-2xl font-bold ml-3">MedConnect</h1>
    </div>
    <nav class="flex-1 space-y-2">
        <a href="dashboard.php" class="nav-link block px-4 py-3 rounded-lg text-gray-700 hover:text-primary-600 <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home mr-3"></i>Tableau de bord
        </a>
        <a href="patients.php" class="nav-link block px-4 py-3 rounded-lg text-gray-700 hover:text-primary-600 <?php echo $current_page === 'patients.php' || $current_page === 'patient_details.php' ? 'active' : ''; ?>">
            <i class="fas fa-users mr-3"></i>Mes Patients
        </a>
        <a href="rdv.php" class="nav-link block px-4 py-3 rounded-lg text-gray-700 hover:text-primary-600 <?php echo $current_page === 'rdv.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt mr-3"></i>Agenda
        </a>
        <a href="consultations.php" class="nav-link block px-4 py-3 rounded-lg text-gray-700 hover:text-primary-600 <?php echo $current_page === 'consultations.php' ? 'active' : ''; ?>">
            <i class="fas fa-stethoscope mr-3"></i>Consultations
        </a>
        <a href="ordonnances.php" class="nav-link block px-4 py-3 rounded-lg text-gray-700 hover:text-primary-600 <?php echo $current_page === 'ordonnances.php' ? 'active' : ''; ?>">
            <i class="fas fa-prescription mr-3"></i>Ordonnances
        </a>
        <a href="messages.php" class="nav-link block px-4 py-3 rounded-lg text-gray-700 hover:text-primary-600 <?php echo $current_page === 'messages.php' ? 'active' : ''; ?>">
            <i class="fas fa-envelope mr-3"></i>Messages
        </a>
        <a href="profile_medecin.php" class="nav-link block px-4 py-3 rounded-lg text-gray-700 hover:text-primary-600 <?php echo $current_page === 'profile_medecin.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-md mr-3"></i>Mon Profil
        </a>
    </nav>
    <div class="mt-6">
        <a href="../../logout.php" class="block bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white text-center px-4 py-3 rounded-lg transition-all duration-300 transform hover:-translate-y-1 shadow-lg hover:shadow-xl">
            <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
        </a>
    </div>
</aside> 