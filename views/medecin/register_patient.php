<?php
$page_title = "Nouveau Patient - MedConnect";
$header_title = "Ajouter un Patient";
$header_icon = "fas fa-user-plus";
include_once '../components/doctor_layout_top.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nom      = trim($_POST['nom'] ?? '');
        $prenom   = trim($_POST['prenom'] ?? '');
        $datenais = $_POST['datenais'] ?? '';
        $sexe     = $_POST['sexe'] ?? '';
        $email    = trim($_POST['email'] ?? '');
        $contact  = trim($_POST['contact'] ?? '');
        $adresse  = trim($_POST['adresse'] ?? '');

        if (!$nom || !$prenom || !$datenais || !$sexe || !$email) {
            throw new Exception("Veuillez remplir tous les champs obligatoires.");
        }

        // Vérifier si l'email existe déjà
        $stmt = $db->prepare("SELECT id FROM patient WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception("Un patient avec cet email existe déjà.");
        }

        // Créer le compte patient (mot de passe temporaire)
        $temp_password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT INTO patient (nom, prenom, datenais, sexe, email, contact, password, role, id_medecin, verification_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'patient', ?, 'verified', NOW())");
        $stmt->execute([$nom, $prenom, $datenais, $sexe, $email, $contact, $temp_password, $user_id]);

        $patient_id = $db->lastInsertId();

        if ($adresse) {
            $stmt2 = $db->prepare("INSERT INTO profilpatient (idpatient, adresse, profession) VALUES (?, ?, '') ON DUPLICATE KEY UPDATE adresse = VALUES(adresse)");
            $stmt2->execute([$patient_id, $adresse]);
        }

        $success = "Patient $prenom $nom ajouté avec succès.";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<div class="max-w-2xl mx-auto fade-in">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Nouveau Patient</h2>
            <p class="text-gray-500">Enregistrez un nouveau patient dans votre liste.</p>
        </div>
        <a href="patients.php" class="text-gray-500 hover:text-gray-800 flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    <?php if ($success): ?>
        <div class="mb-6 p-4 bg-emerald-50 text-emerald-700 rounded-2xl border border-emerald-100 flex items-center gap-3">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            <a href="patients.php" class="ml-auto underline text-sm">Voir la liste</a>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-50 text-red-700 rounded-2xl border border-red-100 flex items-center gap-3">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Nom <span class="text-red-500">*</span></label>
                <input type="text" name="nom" required value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
                    class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Prénom <span class="text-red-500">*</span></label>
                <input type="text" name="prenom" required value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>"
                    class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Date de naissance <span class="text-red-500">*</span></label>
                <input type="date" name="datenais" required value="<?= htmlspecialchars($_POST['datenais'] ?? '') ?>"
                    max="<?= date('Y-m-d') ?>"
                    class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Sexe <span class="text-red-500">*</span></label>
                <select name="sexe" required class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none bg-white">
                    <option value="">Choisir...</option>
                    <option value="M" <?= (($_POST['sexe'] ?? '') === 'M') ? 'selected' : '' ?>>Masculin</option>
                    <option value="F" <?= (($_POST['sexe'] ?? '') === 'F') ? 'selected' : '' ?>>Féminin</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Téléphone</label>
                <input type="text" name="contact" value="<?= htmlspecialchars($_POST['contact'] ?? '') ?>"
                    class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-2">Adresse</label>
                <input type="text" name="adresse" value="<?= htmlspecialchars($_POST['adresse'] ?? '') ?>"
                    class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none">
            </div>
        </div>

        <div class="flex justify-end pt-4 border-t">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-10 py-3 rounded-xl font-bold shadow-lg transition-transform hover:scale-105">
                <i class="fas fa-user-plus mr-2"></i>Enregistrer le Patient
            </button>
        </div>
    </form>
</div>

<?php include_once '../components/doctor_layout_bottom.php'; ?>
