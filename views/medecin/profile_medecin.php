<?php
$page_title = "Mon Profil - MedConnect";
$header_title = "Mon Profil";
$header_icon = "fas fa-user-md";
include_once '../components/doctor_layout_top.php';

// Récupérer les informations du médecin
$stmt = $db->prepare("
    SELECT m.*, pm.adresse, pm.profession, pm.imgdiplome, pm.disponibilite, s.nomspecialite
    FROM medecin m
    LEFT JOIN profilmedecin pm ON m.id = pm.idmedecin
    LEFT JOIN specialite s ON m.idspecialite = s.id
    WHERE m.id = ?
");
$stmt->execute([$user_id]);
$medecin = $stmt->fetch(PDO::FETCH_ASSOC);

// Traitement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        $stmt = $db->prepare("UPDATE medecin SET nom = ?, prenom = ?, email = ?, contact = ? WHERE id = ?");
        $stmt->execute([$_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['contact'], $user_id]);

        $stmt = $db->prepare("INSERT INTO profilmedecin (idmedecin, adresse, disponibilite) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE adresse = VALUES(adresse), disponibilite = VALUES(disponibilite)");
        $stmt->execute([$user_id, $_POST['adresse'], $_POST['disponibilite']]);

        if (isset($_FILES['imgdiplome']) && $_FILES['imgdiplome']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../uploads/diplomes/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            $new_filename = 'diplome_' . $user_id . '_' . time() . '.' . pathinfo($_FILES['imgdiplome']['name'], PATHINFO_EXTENSION);
            if (move_uploaded_file($_FILES['imgdiplome']['tmp_name'], $upload_dir . $new_filename)) {
                $db->prepare("UPDATE profilmedecin SET imgdiplome = ? WHERE idmedecin = ?")->execute([$new_filename, $user_id]);
            }
        }
        $db->commit();
        $success = "Profil mis à jour.";
        // Refresh data
        $refresh = $db->prepare("SELECT m.*, pm.adresse, pm.profession, pm.imgdiplome, pm.disponibilite, s.nomspecialite FROM medecin m LEFT JOIN profilmedecin pm ON m.id = pm.idmedecin LEFT JOIN specialite s ON m.idspecialite = s.id WHERE m.id = ?");
        $refresh->execute([$user_id]);
        $medecin = $refresh->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        $error = "Erreur lors de la mise à jour : " . $e->getMessage();
    }
}
?>

<div class="max-w-4xl mx-auto fade-in">
    <?php if (isset($success)): ?>
        <div class="mb-6 p-4 bg-green-50 text-green-700 rounded-xl border border-green-200 flex items-center gap-3">
            <i class="fas fa-check-circle"></i> <?= $success ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-8">
        <!-- Informations de base -->
        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                <i class="fas fa-id-card text-green-600"></i> Informations Personnelles
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Nom</label>
                    <input type="text" name="nom" value="<?= htmlspecialchars($medecin['nom']) ?>" class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Prénom</label>
                    <input type="text" name="prenom" value="<?= htmlspecialchars($medecin['prenom']) ?>" class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Email professionnel</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($medecin['email']) ?>" class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Téléphone / Contact</label>
                    <input type="text" name="contact" value="<?= htmlspecialchars($medecin['contact']) ?>" class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none">
                </div>
            </div>
        </div>

        <!-- Profil Professionnel -->
        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                <i class="fas fa-user-tie text-blue-600"></i> Profil Professionnel
            </h3>
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Spécialité</label>
                    <div class="p-3 bg-gray-50 rounded-xl text-gray-600 font-medium">
                        <?= htmlspecialchars($medecin['nomspecialite'] ?? 'Non spécifiée') ?>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Adresse du cabinet</label>
                    <textarea name="adresse" rows="3" class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none"><?= htmlspecialchars($medecin['adresse'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Disponibilités & Horaires</label>
                    <textarea name="disponibilite" rows="3" class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none" placeholder="Ex: Lun-Ven 09:00 - 18:00"><?= htmlspecialchars($medecin['disponibilite'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Justificatif / Diplôme</label>
                    <?php if (!empty($medecin['imgdiplome'])): ?>
                        <div class="mb-4 p-3 bg-blue-50 rounded-xl flex items-center justify-between">
                            <span class="text-sm text-blue-700"><i class="fas fa-file-alt mr-2"></i> Document chargé</span>
                            <a href="../../uploads/diplomes/<?= $medecin['imgdiplome'] ?>" target="_blank" class="text-xs font-bold text-blue-600 underline">Voir</a>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="imgdiplome" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-10 py-3 rounded-xl font-bold shadow-lg transition-transform hover:scale-105">
                Enregistrer les modifications
            </button>
        </div>
    </form>
</div>

<?php include_once '../components/doctor_layout_bottom.php'; ?>