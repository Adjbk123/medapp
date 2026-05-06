<?php
$page_title = "Pharmacies - MedConnect";
$header_title = "Pharmacies";
$header_icon = "fas fa-pills";
include_once '../components/patient_layout_top.php';

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

<style>
    .pharmacy-card {
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }
    .pharmacy-card:hover {
        transform: translateY(-5px);
        border-left-color: #3b82f6;
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    }
    .search-input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
    }
</style>

<!-- Formulaire de recherche -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-8 glass-effect fade-in">
    <form method="get" class="flex flex-col md:flex-row items-center gap-4">
        <div class="flex-1 w-full relative">
            <input type="text" name="search" placeholder="Rechercher une pharmacie..." value="<?= htmlspecialchars($search) ?>" class="search-input w-full border border-gray-200 rounded-lg px-4 py-3 pl-10 focus:outline-none">
            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-[#3b82f6]"></i>
        </div>
        <button type="submit" class="w-full md:w-auto bg-[#3b82f6] hover:bg-[#2563eb] text-white px-8 py-3 rounded-lg transition-colors duration-300 flex items-center justify-center gap-2">
            <i class="fas fa-search"></i>
            Rechercher
        </button>
    </form>
</div>

<!-- Chatbot WhatsApp Banner -->
<div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl shadow-lg p-6 mb-8 text-white flex flex-col md:flex-row items-center justify-between fade-in">
    <div class="flex items-center mb-4 md:mb-0">
        <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mr-6 shadow-xl">
            <i class="fab fa-whatsapp text-green-500 text-3xl"></i>
        </div>
        <div>
            <h3 class="font-bold text-xl">Besoin d'aide ? Contactez notre chatbot WhatsApp</h3>
            <p class="text-green-100">Assistance médicale 24/7 et informations sur les médicaments</p>
        </div>
    </div>
    <a href="https://wa.me/22956191919" target="_blank" class="bg-white text-green-600 hover:bg-green-50 px-8 py-3 rounded-xl font-bold flex items-center transition-all transform hover:scale-105 shadow-lg">
        <i class="fab fa-whatsapp mr-2 text-xl"></i>
        +229 56191919
    </a>
</div>

<!-- Liste des pharmacies -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 fade-in">
    <?php if (empty($pharmacies)): ?>
        <div class="col-span-full text-center py-12 bg-white rounded-xl shadow-lg">
            <i class="fas fa-search text-gray-300 text-5xl mb-4"></i>
            <p class="text-gray-500 text-lg">Aucune pharmacie trouvée pour "<?= htmlspecialchars($search) ?>"</p>
        </div>
    <?php else: ?>
        <?php foreach ($pharmacies as $pharmacie): ?>
            <div class="pharmacy-card bg-white rounded-xl shadow-lg p-6 glass-effect flex flex-col">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-14 h-14 rounded-xl bg-[#EFF6FF] flex items-center justify-center">
                        <i class="fas fa-hospital-alt text-2xl text-[#3b82f6]"></i>
                    </div>
                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold uppercase tracking-wider">Ouvert</span>
                </div>
                <h3 class="text-xl font-bold text-[#1e40af] mb-2"><?= htmlspecialchars($pharmacie['nom']) ?></h3>
                <p class="text-gray-600 text-sm mb-4 flex items-start">
                    <i class="fas fa-map-marker-alt mt-1 mr-2 text-[#3b82f6]"></i>
                    <?= htmlspecialchars($pharmacie['localisation']) ?>
                </p>
                <div class="mt-auto pt-4 border-t border-gray-100 flex items-center justify-between">
                    <a href="tel:<?= htmlspecialchars($pharmacie['contact']) ?>" class="text-[#3b82f6] font-medium flex items-center hover:underline">
                        <i class="fas fa-phone-alt mr-2"></i>
                        <?= htmlspecialchars($pharmacie['contact']) ?>
                    </a>
                    <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($pharmacie['nom'] . ' ' . $pharmacie['localisation']) ?>" target="_blank" class="p-2 bg-[#3b82f6] text-white rounded-lg hover:bg-[#2563eb] transition-colors">
                        <i class="fas fa-directions"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include_once '../components/patient_layout_bottom.php'; ?>
