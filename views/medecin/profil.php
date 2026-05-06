<?php
$page_title = "Dossier Professionnel - MedConnect";
$header_title = "Dossier Professionnel";
$header_icon = "fas fa-user-graduate";
include_once '../components/doctor_layout_top.php';

require_once '../models/ProfilMedecin.php';
$profilMedecin = new ProfilMedecin($db);
$profil = $profilMedecin->getProfilByMedecinId($user_id);

// Traitement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'id_medecin' => $user_id,
        'diplome' => $profil['diplome'] ?? '',
        'specialite' => $_POST['specialite'],
        'annees_experience' => $_POST['annees_experience'],
        'hopital_actuel' => $_POST['hopital_actuel'],
        'adresse_cabinet' => $_POST['adresse_cabinet'],
        'horaires_travail' => $_POST['horaires_travail']
    ];

    // Upload diplôme
    if (isset($_FILES['diplome']) && $_FILES['diplome']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../../uploads/diplomes/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        $ext = pathinfo($_FILES["diplome"]["name"], PATHINFO_EXTENSION);
        $filename = "diplome_" . $user_id . "_" . time() . "." . $ext;
        if (move_uploaded_file($_FILES["diplome"]["tmp_name"], $target_dir . $filename)) {
            $data['diplome'] = $filename;
        }
    }

    if ($profil) {
        $profilMedecin->updateProfil($user_id, $data);
    } else {
        $profilMedecin->createProfil($data);
    }
    $success = "Informations professionnelles mises à jour.";
    $profil = $profilMedecin->getProfilByMedecinId($user_id);
}
?>

<div class="max-w-4xl mx-auto fade-in">
    <?php if (isset($success)): ?>
        <div class="mb-6 p-4 bg-emerald-50 text-emerald-700 rounded-2xl border border-emerald-100 flex items-center gap-3">
            <i class="fas fa-check-circle"></i> <?= $success ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-8 border-b border-slate-50 bg-slate-50/30">
            <h3 class="text-lg font-bold text-slate-800">Validation des Compétences</h3>
            <p class="text-sm text-slate-500">Ces informations sont essentielles pour la vérification de votre compte par l'administration.</p>
        </div>

        <form method="POST" enctype="multipart/form-data" class="p-8 space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Spécialité -->
                <div class="space-y-2">
                    <label class="text-sm font-bold text-slate-700 ml-1">Spécialité</label>
                    <input type="text" name="specialite" value="<?= htmlspecialchars($profil['specialite'] ?? '') ?>" required
                        class="w-full px-4 py-3 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-green-500 outline-none transition-all">
                </div>

                <!-- Expérience -->
                <div class="space-y-2">
                    <label class="text-sm font-bold text-slate-700 ml-1">Années d'expérience</label>
                    <input type="number" name="annees_experience" value="<?= htmlspecialchars($profil['annees_experience'] ?? '') ?>" required
                        class="w-full px-4 py-3 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-green-500 outline-none transition-all">
                </div>

                <!-- Hôpital -->
                <div class="md:col-span-2 space-y-2">
                    <label class="text-sm font-bold text-slate-700 ml-1">Hôpital ou Clinique actuelle</label>
                    <input type="text" name="hopital_actuel" value="<?= htmlspecialchars($profil['hopital_actuel'] ?? '') ?>" required
                        class="w-full px-4 py-3 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-green-500 outline-none transition-all">
                </div>

                <!-- Adresse -->
                <div class="md:col-span-2 space-y-2">
                    <label class="text-sm font-bold text-slate-700 ml-1">Adresse du cabinet</label>
                    <textarea name="adresse_cabinet" rows="3" required
                        class="w-full px-4 py-3 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-green-500 outline-none transition-all"><?= htmlspecialchars($profil['adresse_cabinet'] ?? '') ?></textarea>
                </div>

                <!-- Horaires -->
                <div class="md:col-span-2 space-y-2">
                    <label class="text-sm font-bold text-slate-700 ml-1">Horaires de consultation</label>
                    <textarea name="horaires_travail" rows="2" required placeholder="Ex: Lundi au Vendredi, 08:00 - 17:00"
                        class="w-full px-4 py-3 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-green-500 outline-none transition-all"><?= htmlspecialchars($profil['horaires_travail'] ?? '') ?></textarea>
                </div>

                <!-- Diplôme -->
                <div class="md:col-span-2 space-y-3">
                    <label class="text-sm font-bold text-slate-700 ml-1">Diplôme d'État (PDF ou Image)</label>
                    <div class="flex items-center gap-4 p-6 bg-slate-50 border-2 border-dashed border-slate-200 rounded-3xl group hover:border-green-400 transition-colors">
                        <div class="w-12 h-12 rounded-2xl bg-white flex items-center justify-center text-slate-400 group-hover:text-green-500 shadow-sm transition-colors">
                            <i class="fas fa-cloud-upload-alt text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <input type="file" name="diplome" class="text-sm text-slate-500 file:hidden cursor-pointer w-full">
                            <?php if (!empty($profil['diplome'])): ?>
                                <p class="text-[10px] font-bold text-green-600 mt-1 uppercase tracking-wider">
                                    <i class="fas fa-check-circle mr-1"></i> Document actuel : <?= htmlspecialchars($profil['diplome']) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-6 flex justify-end">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-10 py-4 rounded-2xl font-bold shadow-lg shadow-green-500/20 transition-all hover:scale-[1.02] active:scale-95">
                    Enregistrer le Dossier
                </button>
            </div>
        </form>
    </div>
</div>

<?php include_once '../components/doctor_layout_bottom.php'; ?>