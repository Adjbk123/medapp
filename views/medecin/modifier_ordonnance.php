<?php
$page_title = "Modifier Ordonnance - MedConnect";
$header_title = "Modifier l'Ordonnance";
$header_icon = "fas fa-file-edit";
include_once '../components/doctor_layout_top.php';

$ordonnance_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$ordonnance_id) { echo "<script>window.location.href='ordonnances.php';</script>"; exit; }

$stmt = $db->prepare('SELECT o.*, p.nom as patient_nom, p.prenom as patient_prenom FROM ordonnance o JOIN patient p ON o.idpatient = p.id WHERE o.id = ? AND o.idmedecin = ?');
$stmt->execute([$ordonnance_id, $user_id]);
$o = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$o) { echo "<script>window.location.href='ordonnances.php';</script>"; exit; }

// Traitement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $medicaments_data = json_decode($_POST['medicaments_data'], true);
        $m = []; $p = []; $q = []; $d = [];
        foreach ($medicaments_data as $med) {
            $m[] = $med['medicament']; $p[] = $med['posologie']; $q[] = $med['quantite']; $d[] = $med['duree'];
        }
        $stmt = $db->prepare('UPDATE ordonnance SET date_validite = ?, medicaments = ?, posologie = ?, quantite = ?, duree_medicament = ?, duree_traitement = ?, instructions = ?, renouvellement = ?, nombre_renouvellements = ? WHERE id = ? AND idmedecin = ?');
        $stmt->execute([$_POST['date_validite'], implode("\n", $m), implode("\n", $p), implode("\n", $q), implode("\n", $d), $_POST['duree_traitement'], $_POST['instructions'], isset($_POST['renouvellement']) ? 1 : 0, $_POST['nombre_renouvellements'] ?? 0, $ordonnance_id, $user_id]);
        echo "<script>window.location.href='voir_ordonnance.php?id=$ordonnance_id';</script>";
        exit;
    } catch (Exception $e) { $error = $e->getMessage(); }
}
?>

<div class="max-w-5xl mx-auto fade-in">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Modifier l'ordonnance #<?= $o['id'] ?></h2>
            <p class="text-gray-500">Patient: <?= htmlspecialchars($o['patient_prenom'] . ' ' . $o['patient_nom']) ?></p>
        </div>
        <a href="voir_ordonnance.php?id=<?= $ordonnance_id ?>" class="text-gray-500 hover:text-gray-800 flex items-center gap-2">
            <i class="fas fa-times"></i> Annuler
        </a>
    </div>

    <form method="POST" id="ordonnanceForm" class="space-y-8 pb-12">
        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 space-y-8">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Date de validité</label>
                <input type="date" name="date_validite" required min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($o['date_validite']) ?>" class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none max-w-xs">
            </div>

            <!-- Médicaments Dynamiques -->
            <div>
                <div class="flex justify-between items-center mb-4">
                    <label class="block text-sm font-bold text-gray-700">Médicaments & Posologie</label>
                    <button type="button" id="addMed" class="text-sm font-bold text-green-600 hover:text-green-700 flex items-center gap-2">
                        <i class="fas fa-plus-circle"></i> Ajouter
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="text-xs text-gray-400 uppercase tracking-wider">
                            <tr>
                                <th class="pb-3 pr-4">Médicament</th>
                                <th class="pb-3 pr-4">Posologie</th>
                                <th class="pb-3 pr-4">Qté</th>
                                <th class="pb-3 pr-4">Durée</th>
                                <th class="pb-3"></th>
                            </tr>
                        </thead>
                        <tbody id="medRows"></tbody>
                    </table>
                </div>
                <input type="hidden" name="medicaments_data" id="medData">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Durée totale du traitement</label>
                    <input type="text" name="duree_traitement" value="<?= htmlspecialchars($o['duree_traitement']) ?>" required class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Instructions spéciales</label>
                    <textarea name="instructions" rows="2" class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none"><?= htmlspecialchars($o['instructions']) ?></textarea>
                </div>
            </div>

            <!-- Renouvellement -->
            <div class="flex items-center gap-6 pt-4 border-t">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="renouvellement" <?= $o['renouvellement'] ? 'checked' : '' ?> class="w-5 h-5 rounded text-green-600 focus:ring-green-500">
                    <span class="text-sm font-bold text-gray-700">Autoriser renouvellement</span>
                </label>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">Nb fois:</span>
                    <input type="number" name="nombre_renouvellements" min="0" value="<?= (int)$o['nombre_renouvellements'] ?>" class="w-20 px-3 py-1 border rounded-lg focus:ring-green-500">
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-12 py-4 rounded-2xl font-bold shadow-xl transition-transform hover:scale-105">
                Enregistrer les modifications
            </button>
        </div>
    </form>
</div>

<script>
    const medRows = document.getElementById('medRows');
    const addMedBtn = document.getElementById('addMed');
    const medDataInput = document.getElementById('medData');

    const initData = {
        m: <?= json_encode(explode("\n", $o['medicaments'])) ?>,
        p: <?= json_encode(explode("\n", $o['posologie'])) ?>,
        q: <?= json_encode(explode("\n", $o['quantite'])) ?>,
        d: <?= json_encode(explode("\n", $o['duree_medicament'])) ?>
    };

    function createRow(med='', poso='', qte='', dur='') {
        const tr = document.createElement('tr');
        tr.className = 'group';
        tr.innerHTML = `
            <td class="py-3 pr-4"><input type="text" class="med-name w-full px-3 py-2 bg-gray-50 border-none rounded-lg focus:ring-2 focus:ring-green-500" value="${med}" required></td>
            <td class="py-3 pr-4"><input type="text" class="med-poso w-full px-3 py-2 bg-gray-50 border-none rounded-lg focus:ring-2 focus:ring-green-500" value="${poso}" required></td>
            <td class="py-3 pr-4"><input type="text" class="med-qte w-full px-3 py-2 bg-gray-50 border-none rounded-lg focus:ring-2 focus:ring-green-500" value="${qte}"></td>
            <td class="py-3 pr-4"><input type="text" class="med-dur w-full px-3 py-2 bg-gray-50 border-none rounded-lg focus:ring-2 focus:ring-green-500" value="${dur}"></td>
            <td class="py-3 text-right"><button type="button" class="text-gray-300 hover:text-red-500 transition-colors"><i class="fas fa-trash-alt"></i></button></td>
        `;
        tr.querySelector('button').onclick = () => { tr.remove(); updateData(); };
        tr.querySelectorAll('input').forEach(i => i.oninput = updateData);
        medRows.appendChild(tr);
        updateData();
    }

    function updateData() {
        const rows = [...medRows.querySelectorAll('tr')];
        const data = rows.map(r => ({
            medicament: r.querySelector('.med-name').value,
            posologie: r.querySelector('.med-poso').value,
            quantite: r.querySelector('.med-qte').value,
            duree: r.querySelector('.med-dur').value
        }));
        medDataInput.value = JSON.stringify(data);
    }

    addMedBtn.onclick = () => createRow();

    // Init rows
    initData.m.forEach((val, i) => createRow(val, initData.p[i]||'', initData.q[i]||'', initData.d[i]||''));
</script>

<?php include_once '../components/doctor_layout_bottom.php'; ?>