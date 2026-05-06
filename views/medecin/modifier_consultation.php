<?php
$page_title = "Modifier Consultation - MedConnect";
$header_title = "Modifier Consultation";
$header_icon = "fas fa-edit";
include_once '../components/doctor_layout_top.php';

$consultation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$consultation_id) { echo "<script>window.location.href='consultations.php';</script>"; exit; }

$stmt = $db->prepare("SELECT c.*, p.nom as patient_nom, p.prenom as patient_prenom FROM consultation c JOIN patient p ON c.id_patient = p.id WHERE c.id = ? AND c.id_medecin = ?");
$stmt->execute([$consultation_id, $user_id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$c) { echo "<script>window.location.href='consultations.php';</script>"; exit; }

// Traitement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $db->prepare("UPDATE consultation SET motif = ?, antecedents = ?, examen_clinique = ?, diagnostic = ?, traitement = ?, recommandations = ?, prochain_rdv = ? WHERE id = ? AND id_medecin = ?");
        $stmt->execute([$_POST['motif'], $_POST['antecedents'], $_POST['examen_clinique'], $_POST['diagnostic'], $_POST['traitement'], $_POST['recommandations'], !empty($_POST['prochain_rdv']) ? $_POST['prochain_rdv'] : null, $consultation_id, $user_id]);
        echo "<script>window.location.href='voir_consultation.php?id=$consultation_id';</script>";
        exit;
    } catch (Exception $e) { $error = $e->getMessage(); }
}
?>

<div class="max-w-4xl mx-auto fade-in">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Modifier la consultation</h2>
            <p class="text-gray-500">Mise à jour du dossier de <?= htmlspecialchars($c['patient_prenom'] . ' ' . $c['patient_nom']) ?></p>
        </div>
        <a href="voir_consultation.php?id=<?= $consultation_id ?>" class="text-gray-500 hover:text-gray-800 flex items-center gap-2 transition-colors">
            <i class="fas fa-times"></i> Annuler
        </a>
    </div>

    <?php if (isset($error)): ?>
        <div class="mb-6 p-4 bg-red-50 text-red-700 rounded-xl border border-red-200"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 space-y-6">
            <!-- Motif & Antécédents -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Motif de consultation</label>
                    <textarea name="motif" rows="3" required class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none"><?= htmlspecialchars($c['motif']) ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Antécédents</label>
                    <textarea name="antecedents" rows="3" class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none"><?= htmlspecialchars($c['antecedents'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Examen & Diagnostic -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Examen Clinique</label>
                <textarea name="examen_clinique" rows="4" required class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none"><?= htmlspecialchars($c['examen_clinique']) ?></textarea>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Diagnostic</label>
                <input type="text" name="diagnostic" value="<?= htmlspecialchars($c['diagnostic']) ?>" required class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none">
            </div>

            <!-- Traitement & Recommandations -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Traitement</label>
                    <textarea name="traitement" rows="3" class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none"><?= htmlspecialchars($c['traitement'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Recommandations</label>
                    <textarea name="recommandations" rows="3" class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none"><?= htmlspecialchars($c['recommandations'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Prochain RDV -->
            <div class="md:w-1/2">
                <label class="block text-sm font-bold text-gray-700 mb-2">Prochain rendez-vous</label>
                <input type="datetime-local" name="prochain_rdv" value="<?= $c['prochain_rdv'] ? date('Y-m-d\TH:i', strtotime($c['prochain_rdv'])) : '' ?>" class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none">
            </div>
        </div>

        <div class="flex justify-end gap-4">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-10 py-3 rounded-xl font-bold shadow-lg transition-transform hover:scale-105">
                Enregistrer les modifications
            </button>
        </div>
    </form>
</div>

<?php include_once '../components/doctor_layout_bottom.php'; ?>