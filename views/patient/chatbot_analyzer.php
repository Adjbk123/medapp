<?php
$page_title = "Assistant Médical Intelligent - MedConnect";
$header_title = "Assistant Médical Intelligent";
$header_icon = "fas fa-stethoscope";
include_once '../components/patient_layout_top.php';
?>

<style>
    .demo-card { transition: all 0.3s ease; }
    .demo-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); }
</style>

<div class="bg-white rounded-xl shadow-lg p-8 mb-8 fade-in">
    <div class="flex items-start">
        <div class="bg-blue-100 p-4 rounded-xl mr-6">
            <i class="fas fa-robot text-blue-600 text-4xl"></i>
        </div>
        <div>
            <h2 class="text-2xl font-bold text-[#1e40af] mb-2">Votre Assistant Médical Personnel</h2>
            <p class="text-gray-700 text-lg mb-6">
                Notre assistant médical intelligent utilise l'IA pour vous aider à comprendre vos symptômes et vous orienter vers les bons spécialistes.
            </p>
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-r-lg">
                <p class="text-yellow-800 font-medium">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Note : Cet assistant ne remplace pas un avis médical professionnel. En cas d'urgence, composez le 15 ou rendez-vous aux urgences.
                </p>
            </div>
        </div>
    </div>
</div>

<h3 class="text-xl font-bold text-[#1e40af] mb-6">Exemples de symptômes à analyser :</h3>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
    <!-- Card 1 -->
    <div class="bg-white rounded-xl shadow-md p-6 demo-card">
        <div class="flex items-center mb-4">
            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center mr-3 text-red-500">
                <i class="fas fa-heartbeat"></i>
            </div>
            <h4 class="font-bold text-gray-800">Cardiologie</h4>
        </div>
        <ul class="text-gray-600 space-y-2">
            <li><i class="fas fa-check text-blue-500 mr-2"></i>Douleurs thoraciques</li>
            <li><i class="fas fa-check text-blue-500 mr-2"></i>Palpitations</li>
        </ul>
    </div>
    <!-- Card 2 -->
    <div class="bg-white rounded-xl shadow-md p-6 demo-card">
        <div class="flex items-center mb-4">
            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3 text-blue-500">
                <i class="fas fa-brain"></i>
            </div>
            <h4 class="font-bold text-gray-800">Neurologie</h4>
        </div>
        <ul class="text-gray-600 space-y-2">
            <li><i class="fas fa-check text-blue-500 mr-2"></i>Maux de tête sévères</li>
            <li><i class="fas fa-check text-blue-500 mr-2"></i>Vertiges</li>
        </ul>
    </div>
    <!-- Card 3 -->
    <div class="bg-white rounded-xl shadow-md p-6 demo-card">
        <div class="flex items-center mb-4">
            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center mr-3 text-green-500">
                <i class="fas fa-lungs"></i>
            </div>
            <h4 class="font-bold text-gray-800">Pneumologie</h4>
        </div>
        <ul class="text-gray-600 space-y-2">
            <li><i class="fas fa-check text-blue-500 mr-2"></i>Toux persistante</li>
            <li><i class="fas fa-check text-blue-500 mr-2"></i>Gêne respiratoire</li>
        </ul>
    </div>
</div>

<div class="text-center">
    <button id="openChatbot" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-10 rounded-full shadow-xl transition-all transform hover:scale-105 inline-flex items-center">
        <i class="fas fa-play-circle mr-3 text-xl"></i>
        Démarrer l'analyse maintenant
    </button>
</div>

<!-- Inclusion du chatbot modal -->
<?php include_once '../../views/components/chatbot_analyzer.php'; ?>

<script>
    document.getElementById('openChatbot').addEventListener('click', function() {
        // Le bouton déclencheur réel est dans le composant inclus
        const chatBtn = document.getElementById('chatButton');
        if (chatBtn) chatBtn.click();
    });
</script>

<?php include_once '../components/patient_layout_bottom.php'; ?>
