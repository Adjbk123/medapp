<?php
$page_title = "Mon Carnet de Santé - MedConnect";
$header_title = "Mon Carnet de Santé";
$header_icon = "fas fa-book-medical";
include_once '../components/patient_layout_top.php';

// Vérifier si le carnet de santé existe
$stmt = db()->prepare("SELECT id FROM carnetsante WHERE id_patient = ?");
$stmt->execute([$id_patient]);
$carnet_exists = $stmt->fetch();

if (!$carnet_exists) {
    $stmt = db()->prepare("
        INSERT INTO carnetsante (id_patient, taille, poids, groupesanguin, allergie, electrophorese)
        VALUES (?, NULL, NULL, '', '', '')
    ");
    $stmt->execute([$id_patient]);
    $id_carnet = db()->lastInsertId();
} else {
    $id_carnet = $carnet_exists['id'];
}

// Vérifier si le profil patient existe
$stmt = db()->prepare("SELECT id FROM profilpatient WHERE idpatient = ?");
$stmt->execute([$id_patient]);
$profil_exists = $stmt->fetch();

if (!$profil_exists) {
    $stmt = db()->prepare("
        INSERT INTO profilpatient (idpatient, idcarnetsante, adresse, profession)
        VALUES (?, ?, '', '')
    ");
    $stmt->execute([$id_patient, $id_carnet]);
}

// Initialiser le tableau carnet
$carnet = [
    'adresse' => '', 'profession' => '', 'taille' => '', 'poids' => '',
    'groupesanguin' => '', 'allergie' => '', 'electrophorese' => ''
];

$stmt = db()->prepare("SELECT adresse, profession FROM profilpatient WHERE idpatient = ?");
$stmt->execute([$id_patient]);
$profil = $stmt->fetch(PDO::FETCH_ASSOC);
if ($profil) {
    $carnet['adresse'] = $profil['adresse'] ?? '';
    $carnet['profession'] = $profil['profession'] ?? '';
}

$stmt = db()->prepare("SELECT taille, poids, groupesanguin, allergie, electrophorese FROM carnetsante WHERE id_patient = ?");
$stmt->execute([$id_patient]);
$carnet_data = $stmt->fetch(PDO::FETCH_ASSOC);
if ($carnet_data) {
    $carnet = array_merge($carnet, $carnet_data);
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = db();
        $db->beginTransaction();
        $stmt = $db->prepare("UPDATE carnetsante SET groupesanguin = ?, taille = ?, poids = ?, allergie = ?, electrophorese = ? WHERE id_patient = ?");
        $stmt->execute([$_POST['groupesanguin'] ?? '', $_POST['taille'] ?? '', $_POST['poids'] ?? '', $_POST['allergie'] ?? '', $_POST['electrophorese'] ?? '', $id_patient]);
        $stmt = $db->prepare("UPDATE profilpatient SET adresse = ?, profession = ? WHERE idpatient = ?");
        $stmt->execute([$_POST['adresse'] ?? '', $_POST['profession'] ?? '', $id_patient]);
        $db->commit();
        $success = "Votre carnet de santé a été mis à jour avec succès.";
        $carnet = array_merge($carnet, $_POST);
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Erreur : " . $e->getMessage();
    }
}

function val($key) { global $carnet; return htmlspecialchars($carnet[$key] ?? ''); }
?>

<style>
    .health-card { transition: all 0.3s ease; }
    .health-card:hover { transform: translateY(-5px); }
</style>

<?php if (isset($success)): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4"><?php echo $success; ?></div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4"><?php echo $error; ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 fade-in">
    <!-- Formulaire d'informations -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-lg p-8 glass-effect">
            <h2 class="text-2xl font-bold text-[#1e40af] mb-6 flex items-center">
                <i class="fas fa-id-card mr-3"></i>Informations Personnelles
            </h2>
            <form action="carnet.php" method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Adresse</label>
                        <input type="text" name="adresse" value="<?php echo val('adresse'); ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Profession</label>
                        <input type="text" name="profession" value="<?php echo val('profession'); ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <h3 class="text-xl font-bold text-[#1e40af] mt-8 mb-4">Données Médicales</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Groupe Sanguin</label>
                        <select name="groupesanguin" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Sélectionner</option>
                            <?php foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $g): ?>
                                <option value="<?php echo $g; ?>" <?php echo val('groupesanguin') == $g ? 'selected' : ''; ?>><?php echo $g; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Taille (cm)</label>
                        <input type="number" name="taille" value="<?php echo val('taille'); ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Poids (kg)</label>
                        <input type="number" name="poids" value="<?php echo val('poids'); ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Allergies</label>
                    <textarea name="allergie" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" rows="3"><?php echo val('allergie'); ?></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Électrophorèse</label>
                    <input type="text" name="electrophorese" value="<?php echo val('electrophorese'); ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="bg-[#3b82f6] hover:bg-[#2563eb] text-white px-8 py-3 rounded-lg transition-all duration-300 transform hover:scale-105">
                        Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Résumé -->
    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-lg p-6 glass-effect health-card">
            <h3 class="text-xl font-bold text-[#1e40af] mb-4 flex items-center">
                <i class="fas fa-heartbeat mr-3 text-red-500"></i>Résumé Vital
            </h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg">
                    <span class="text-gray-600">Groupe Sanguin</span>
                    <span class="font-bold text-red-600 text-lg"><?php echo val('groupesanguin') ?: 'N/A'; ?></span>
                </div>
                <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                    <span class="text-gray-600">IMC</span>
                    <?php 
                    $taille = (float)val('taille') / 100;
                    $poids = (float)val('poids');
                    $imc = $taille > 0 ? round($poids / ($taille * $taille), 1) : 0;
                    ?>
                    <span class="font-bold text-blue-600"><?php echo $imc ?: 'N/A'; ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../components/patient_layout_bottom.php'; ?>
