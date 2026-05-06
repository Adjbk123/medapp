<?php
$page_title = "Consultations - MedConnect";
$header_title = "Historique des Consultations";
$header_icon = "fas fa-stethoscope";
include_once '../components/doctor_layout_top.php';

// Récupérer la liste des patients pour le filtre
try {
    $stmt = $db->prepare("SELECT id, nom, prenom FROM patient WHERE id_medecin = ? ORDER BY nom, prenom");
    $stmt->execute([$user_id]);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $patients = []; }

// Récupérer les consultations avec filtre
try {
    $query = "SELECT c.*, p.nom as patient_nom, p.prenom as patient_prenom FROM consultation c JOIN patient p ON c.id_patient = p.id WHERE c.id_medecin = ?";
    $params = [$user_id];
    if (isset($_GET['patient_id']) && !empty($_GET['patient_id'])) {
        $query .= " AND c.id_patient = ?";
        $params[] = $_GET['patient_id'];
    }
    $query .= " ORDER BY c.date_consultation DESC";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $consultations = []; }
?>

<div class="flex justify-between items-center mb-8 fade-in">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Mes Consultations</h2>
        <p class="text-gray-500">Consultez et gérez les dossiers de vos patients.</p>
    </div>
    <a href="nouvelle_consultation.php" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-xl flex items-center gap-2 shadow-lg transition-transform hover:scale-105">
        <i class="fas fa-plus"></i>Nouvelle Consultation
    </a>
</div>

<!-- Filtres -->
<div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mb-8 fade-in">
    <form method="GET" class="flex flex-wrap items-end gap-4">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-bold text-gray-700 mb-2">Filtrer par patient</label>
            <select name="patient_id" class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none appearance-none bg-white">
                <option value="">Tous les patients</option>
                <?php foreach ($patients as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= (isset($_GET['patient_id']) && $_GET['patient_id'] == $p['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="px-6 py-2 bg-gray-800 text-white rounded-xl hover:bg-gray-900 transition-colors">
            Filtrer
        </button>
        <?php if (isset($_GET['patient_id']) && $_GET['patient_id']): ?>
            <a href="consultations.php" class="px-6 py-2 bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200 transition-colors">
                Réinitialiser
            </a>
        <?php endif; ?>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden fade-in">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-4 font-bold">Date</th>
                    <th class="px-6 py-4 font-bold">Patient</th>
                    <th class="px-6 py-4 font-bold">Motif</th>
                    <th class="px-6 py-4 font-bold">Diagnostic</th>
                    <th class="px-6 py-4 font-bold text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($consultations)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                            <i class="fas fa-folder-open text-4xl mb-4 block"></i>
                            Aucune consultation trouvée.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($consultations as $c): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-800"><?= date('d/m/Y', strtotime($c['date_consultation'])) ?></div>
                                <div class="text-xs text-gray-500"><?= date('H:i', strtotime($c['date_consultation'])) ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-800"><?= htmlspecialchars($c['patient_prenom'] . ' ' . $c['patient_nom']) ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">
                                <?= htmlspecialchars($c['motif']) ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">
                                <?= htmlspecialchars($c['diagnostic']) ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-center gap-2">
                                    <a href="voir_consultation.php?id=<?= $c['id'] ?>" class="p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="modifier_consultation.php?id=<?= $c['id'] ?>" class="p-2 bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-100 transition-colors" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="imprimer_consultation.php?id=<?= $c['id'] ?>" class="p-2 bg-green-50 text-green-600 rounded-lg hover:bg-green-100 transition-colors" title="Imprimer">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once '../components/doctor_layout_bottom.php'; ?>