<?php
$page_title = "Mon Profil - MedConnect";
$header_title = "Mon Profil";
$header_icon = "fas fa-user";
include_once '../components/patient_layout_top.php';

$success = "";
$error = "";

// Pré-remplissage des données
$stmt =  db()->prepare("
    SELECT 
        p.nom, p.prenom, p.datenais, p.email, p.contact, p.sexe,
        pr.adresse, pr.profession,
        cs.groupesanguin, cs.taille, cs.poids, cs.allergie, cs.electrophorese,
        pr.id AS profil_id, cs.id AS carnet_id
    FROM patient p  
    LEFT JOIN profilpatient pr ON p.id = pr.idpatient
    LEFT JOIN carnetsante cs ON pr.idcarnetsante = cs.id
    WHERE p.id = ?
");
$stmt->execute([$id_patient]);
$data = $stmt->fetch();

// Pré-remplissage
$nom = $data['nom'] ?? '';
$prenom = $data['prenom'] ?? '';
$email = $data['email'] ?? '';
$contact = $data['contact'] ?? '';
$datenais = $data['datenais'] ?? '';
$sexe = $data['sexe'] ?? '';
$adresse = $data['adresse'] ?? '';
$profession = $data['profession'] ?? '';
$groupesanguin = $data['groupesanguin'] ?? '';
$taille = $data['taille'] ?? '';
$poids = $data['poids'] ?? '';
$allergie = $data['allergie'] ?? '';
$electrophorese = $data['electrophorese'] ?? '';
$profil_id = $data['profil_id'] ?? null;
$carnet_id = $data['carnet_id'] ?? null;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $datenais = $_POST['datenais'];
    $email = $_POST['email'];
    $contact = $_POST['telephone'];
    $sexe = $_POST['sexe'];
    $adresse = $_POST['adresse'];
    $profession = $_POST['profession'];

    $groupesanguin = $_POST['groupesanguin'];
    $taille = $_POST['taille'];
    $poids = $_POST['poids'];
    $allergie = $_POST['allergie'];
    $electrophorese = $_POST['electrophorese'];

    // MAJ patient
    $stmt =  db()->prepare("UPDATE patient SET nom = ?, prenom = ?, datenais = ?, email = ?, contact = ?, sexe = ? WHERE id = ?");
    $stmt->execute([$nom, $prenom, $datenais, $email, $contact, $sexe, $id_patient]);

    // MAJ ou insertion profilpatient
    if ($profil_id) {
        $stmt =  db()->prepare("UPDATE profilpatient SET adresse = ?, profession = ? WHERE id = ?");
        $stmt->execute([$adresse, $profession, $profil_id]);
    } else {
        $stmt = db()->prepare("INSERT INTO carnetsante (id_patient, groupesanguin, taille, poids, allergie, electrophorese) VALUES (?, '', '', '', '', '')");
        $stmt->execute([$id_patient]);
        $new_carnet_id = db()->lastInsertId();
        $stmt = db()->prepare("INSERT INTO profilpatient (adresse, profession, idpatient, idcarnetsante) VALUES (?, ?, ?, ?)");
        $stmt->execute([$adresse, $profession, $id_patient, $new_carnet_id]);
    }

    // MAJ carnet de santé
    if ($carnet_id) {
        $stmt =  db()->prepare("UPDATE carnetsante SET groupesanguin = ?, taille = ?, poids = ?, allergie = ?, electrophorese = ? WHERE id = ?");
        $stmt->execute([$groupesanguin, $taille, $poids, $allergie, $electrophorese, $carnet_id]);
    }

    $success = "Profil mis à jour avec succès.";
}
?>

<style>
    .form-input { transition: all 0.3s ease; }
    .form-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1); }
</style>

<div class="max-w-4xl mx-auto fade-in">
    <div class="bg-white rounded-xl shadow-lg p-8 glass-effect">
        <div class="flex items-center space-x-6 mb-8">
            <div class="w-24 h-24 rounded-full bg-gradient-to-br from-[#3b82f6] to-[#60a5fa] flex items-center justify-center shadow-lg">
                <i class="fas fa-user text-white text-4xl"></i>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-[#1e40af]"><?= htmlspecialchars($prenom . ' ' . $nom) ?></h2>
                <p class="text-[#3b82f6] text-lg"><?= htmlspecialchars($email) ?></p>
            </div>
        </div>

        <?php if (!empty($success)) : ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline"><?= htmlspecialchars($success) ?></span>
            </div>
        <?php endif; ?>

        <form action="profile_patient.php" method="post" class="space-y-8">
            <!-- Section 1: État Civil -->
            <div>
                <h3 class="text-xl font-bold text-[#1e40af] mb-4 border-b pb-2">
                    <i class="fas fa-id-card mr-2"></i>État Civil
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Nom</label>
                        <input type="text" name="nom" value="<?= htmlspecialchars($nom) ?>" class="form-input w-full border rounded-lg px-4 py-2">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Prénom</label>
                        <input type="text" name="prenom" value="<?= htmlspecialchars($prenom) ?>" class="form-input w-full border rounded-lg px-4 py-2">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" class="form-input w-full border rounded-lg px-4 py-2">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Téléphone</label>
                        <input type="text" name="telephone" value="<?= htmlspecialchars($contact) ?>" class="form-input w-full border rounded-lg px-4 py-2">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Date de naissance</label>
                        <input type="date" name="datenais" value="<?= htmlspecialchars($datenais) ?>" class="form-input w-full border rounded-lg px-4 py-2">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Sexe</label>
                        <select name="sexe" class="form-input w-full border rounded-lg px-4 py-2">
                            <option value="M" <?= $sexe === 'M' ? 'selected' : '' ?>>Masculin</option>
                            <option value="F" <?= $sexe === 'F' ? 'selected' : '' ?>>Féminin</option>
                            <option value="A" <?= $sexe === 'A' ? 'selected' : '' ?>>Autre</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 2: Profession & Adresse -->
            <div>
                <h3 class="text-xl font-bold text-[#1e40af] mb-4 border-b pb-2">
                    <i class="fas fa-briefcase mr-2"></i>Profession & Contact
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Profession</label>
                        <input type="text" name="profession" value="<?= htmlspecialchars($profession) ?>" class="form-input w-full border rounded-lg px-4 py-2">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Adresse</label>
                        <input type="text" name="adresse" value="<?= htmlspecialchars($adresse) ?>" class="form-input w-full border rounded-lg px-4 py-2">
                    </div>
                </div>
            </div>

            <!-- Section 3: Informations Médicales -->
            <div>
                <h3 class="text-xl font-bold text-[#1e40af] mb-4 border-b pb-2">
                    <i class="fas fa-heartbeat mr-2"></i>Informations Médicales
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Groupe sanguin</label>
                        <input type="text" name="groupesanguin" value="<?= htmlspecialchars($groupesanguin) ?>" class="form-input w-full border rounded-lg px-4 py-2">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Taille (cm)</label>
                        <input type="text" name="taille" value="<?= htmlspecialchars($taille) ?>" class="form-input w-full border rounded-lg px-4 py-2">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Poids (kg)</label>
                        <input type="text" name="poids" value="<?= htmlspecialchars($poids) ?>" class="form-input w-full border rounded-lg px-4 py-2">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Allergies</label>
                        <input type="text" name="allergie" value="<?= htmlspecialchars($allergie) ?>" class="form-input w-full border rounded-lg px-4 py-2">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Électrophorèse</label>
                        <input type="text" name="electrophorese" value="<?= htmlspecialchars($electrophorese) ?>" class="form-input w-full border rounded-lg px-4 py-2">
                    </div>
                </div>
            </div>

            <div class="pt-6">
                <button type="submit" class="w-full bg-[#3b82f6] hover:bg-[#2563eb] text-white font-bold py-4 px-6 rounded-xl transition-all duration-300 shadow-lg transform hover:-translate-y-1">
                    <i class="fas fa-save mr-2"></i>Mettre à jour mon profil
                </button>
            </div>
        </form>
    </div>
</div>

<?php include_once '../components/patient_layout_bottom.php'; ?>
