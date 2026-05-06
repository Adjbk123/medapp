<?php
// Récupérer le nom du fichier actuel pour marquer le lien actif
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="w-64 bg-white shadow-lg flex flex-col py-6 px-4 h-screen sticky top-0 left-0">
    <div class="flex items-center justify-center mb-10">
        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#3b82f6] to-[#60a5fa] flex items-center justify-center">
            <i class="fas fa-heartbeat text-white text-xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-[#1e40af] ml-3">MedConnect</h1>
    </div>
    <nav class="flex-1 space-y-2">
        <a href="dashboard.php" class="nav-link block px-4 py-3 rounded-lg text-[#1e40af] <?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-home mr-3"></i>Tableau de bord
        </a>
        <a href="carnet.php" class="nav-link block px-4 py-3 rounded-lg text-[#1e40af] <?php echo ($current_page === 'carnet.php') ? 'active' : ''; ?>">
            <i class="fas fa-book-medical mr-3"></i>Mon Carnet de Santé
        </a>
        <a href="rdv.php" class="nav-link block px-4 py-3 rounded-lg text-[#1e40af] <?php echo ($current_page === 'rdv.php') ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt mr-3"></i>Mes Rendez-vous
        </a>
        <a href="ordonnace.php" class="nav-link block px-4 py-3 rounded-lg text-[#1e40af] <?php echo ($current_page === 'ordonnace.php' || $current_page === 'ordonnances.php') ? 'active' : ''; ?>">
            <i class="fas fa-prescription mr-3"></i>Mes Ordonnances
        </a>
        <a href="consultations.php" class="nav-link block px-4 py-3 rounded-lg text-[#1e40af] <?php echo ($current_page === 'consultations.php') ? 'active' : ''; ?>">
            <i class="fas fa-stethoscope mr-3"></i>Mes Consultations
        </a>
        <a href="listes_pharmacie.php" class="nav-link block px-4 py-3 rounded-lg text-[#1e40af] <?php echo ($current_page === 'listes_pharmacie.php') ? 'active' : ''; ?>">
            <i class="fas fa-pills mr-3"></i>Ma Pharmacie
        </a>
        <a href="messages.php" class="nav-link block px-4 py-3 rounded-lg text-[#1e40af] <?php echo ($current_page === 'messages.php') ? 'active' : ''; ?>">
            <i class="fas fa-envelope mr-3"></i>Messages
        </a>
        <a href="profile_patient.php" class="nav-link block px-4 py-3 rounded-lg text-[#1e40af] <?php echo ($current_page === 'profile_patient.php') ? 'active' : ''; ?>">
            <i class="fas fa-user mr-3"></i>Mon Profil
        </a>
    </nav>
    <div class="mt-6">
        <a href="./../logout.php" class="block bg-[#FF5252] hover:bg-[#D32F2F] text-white text-center px-4 py-3 rounded-lg transition-colors duration-300">
            <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
        </a>
    </div>
</aside>
