<?php
$page_title = "Nouvelle Consultation - MedConnect";
$header_title = "Nouvelle Consultation";
$header_icon = "fas fa-plus-circle";
include_once '../components/doctor_layout_top.php';

$patient_id_url = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : null;

// Récupérer les patients
$stmt = $db->prepare("SELECT id, nom, prenom FROM patient WHERE id_medecin = ? ORDER BY nom, prenom");
$stmt->execute([$user_id]);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (empty($_POST['patient_id'])) throw new Exception("Sélectionnez un patient.");
        $stmt = $db->prepare("INSERT INTO consultation (id_medecin, id_patient, date_consultation, motif, antecedents, examen_clinique, diagnostic, traitement, recommandations, prochain_rdv) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $_POST['patient_id'], $_POST['motif'], $_POST['antecedents'], $_POST['examen_clinique'], $_POST['diagnostic'], $_POST['traitement'], $_POST['recommandations'], !empty($_POST['prochain_rdv']) ? $_POST['prochain_rdv'] : null]);
        echo "<script>window.location.href='consultations.php';</script>";
        exit;
    } catch (Exception $e) { $error = $e->getMessage(); }
}
?>

<div class="max-w-4xl mx-auto fade-in">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Enregistrer une consultation</h2>
            <p class="text-gray-500">Remplissez les détails cliniques de la visite.</p>
        </div>
        <a href="consultations.php" class="text-gray-500 hover:text-gray-800 flex items-center gap-2 transition-colors">
            <i class="fas fa-times"></i> Annuler
        </a>
    </div>

    <?php if (isset($error)): ?>
        <div class="mb-6 p-4 bg-red-50 text-red-700 rounded-xl border border-red-200"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 space-y-6">
            <!-- Patient -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Patient concerné</label>
                <select name="patient_id" required class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none bg-gray-50">
                    <option value="">Sélectionnez un patient</option>
                    <?php foreach ($patients as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= ($patient_id_url == $p['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Motif & Antécédents -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Motif de consultation</label>
                    <textarea name="motif" rows="3" required class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none" placeholder="Ex: Douleurs abdominales..."></textarea>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Antécédents pertinents</label>
                    <textarea name="antecedents" rows="3" class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none" placeholder="Ex: Diabète type 2..."></textarea>
                </div>
            </div>

            <!-- Examen & Diagnostic -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Examen Clinique</label>
                <textarea name="examen_clinique" rows="4" required class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none" placeholder="Détaillez vos observations..."></textarea>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Diagnostic</label>
                <input type="text" name="diagnostic" required class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none" placeholder="Conclusion médicale">
            </div>

            <!-- Traitement & Recommandations -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Traitement (si hors ordonnance)</label>
                    <textarea name="traitement" rows="3" class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none" placeholder="Ex: Repos, massages..."></textarea>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Recommandations</label>
                    <textarea name="recommandations" rows="3" class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none" placeholder="Conseils au patient..."></textarea>
                </div>
            </div>

            <!-- Prochain RDV -->
            <div class="md:w-1/2">
                <label class="block text-sm font-bold text-gray-700 mb-2">Prochain rendez-vous suggéré</label>
                <input type="datetime-local" name="prochain_rdv" class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none">
            </div>
        </div>

        <div class="flex justify-end gap-4">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-10 py-3 rounded-xl font-bold shadow-lg transition-transform hover:scale-105">
                Enregistrer la consultation
            </button>
        </div>
    </form>
</div>

<?php include_once '../components/doctor_layout_bottom.php'; ?>