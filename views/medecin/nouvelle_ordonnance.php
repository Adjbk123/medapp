<?php
$page_title = "Nouvelle Ordonnance - MedConnect";
$header_title = "Rédiger une Ordonnance";
$header_icon = "fas fa-prescription-bottle-alt";
include_once '../components/doctor_layout_top.php';

// Récupérer les patients
$stmt = $db->prepare("SELECT id, nom, prenom FROM patient WHERE id_medecin = ? ORDER BY nom, prenom");
$stmt->execute([$user_id]);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (empty($_POST['idpatient'])) throw new Exception("Sélectionnez un patient.");
        $medicaments_data = json_decode($_POST['medicaments_data'], true);
        
        $medicaments = []; $posologie = []; $quantites = []; $durees = [];
        foreach ($medicaments_data as $med) {
            $medicaments[] = $med['medicament'];
            $posologie[] = $med['posologie'];
            $quantites[] = $med['quantite'];
            $durees[] = $med['duree'];
        }

        $stmt = $db->prepare("INSERT INTO ordonnance (idmedecin, idpatient, date_validite, medicaments, posologie, quantite, duree_medicament, duree_traitement, instructions, renouvellement, nombre_renouvellements, signature) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id, $_POST['idpatient'], $_POST['date_validite'],
            implode("\n", $medicaments), implode("\n", $posologie), implode("\n", $quantites), implode("\n", $durees),
            $_POST['duree_traitement'], $_POST['instructions'],
            isset($_POST['renouvellement']) ? 1 : 0, $_POST['nombre_renouvellements'] ?? 0, $_POST['signature_data']
        ]);
        echo "<script>window.location.href='ordonnances.php';</script>";
        exit;
    } catch (Exception $e) { $error = $e->getMessage(); }
}
?>

<div class="max-w-5xl mx-auto fade-in">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Nouvelle Prescription</h2>
            <p class="text-gray-500">Générez une ordonnance sécurisée pour votre patient.</p>
        </div>
        <a href="ordonnances.php" class="text-gray-500 hover:text-gray-800 flex items-center gap-2">
            <i class="fas fa-times"></i> Annuler
        </a>
    </div>

    <form method="POST" id="ordonnanceForm" class="space-y-8 pb-12">
        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 space-y-8">
            <!-- Patient & Validité -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Patient</label>
                    <select name="idpatient" required class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none bg-gray-50">
                        <option value="">Choisir un patient</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Date de validité</label>
                    <input type="date" name="date_validite" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+3 months')) ?>" class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none">
                </div>
            </div>

            <!-- Médicaments Dynamiques -->
            <div>
                <div class="flex justify-between items-center mb-4">
                    <label class="block text-sm font-bold text-gray-700">Médicaments & Posologie</label>
                    <button type="button" id="addMed" class="text-sm font-bold text-green-600 hover:text-green-700 flex items-center gap-2">
                        <i class="fas fa-plus-circle"></i> Ajouter un médicament
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
                        <tbody id="medRows">
                            <!-- JS will inject rows here -->
                        </tbody>
                    </table>
                </div>
                <input type="hidden" name="medicaments_data" id="medData">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Durée totale du traitement</label>
                    <input type="text" name="duree_traitement" placeholder="Ex: 7 jours" required class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Instructions spéciales</label>
                    <textarea name="instructions" rows="2" class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none" placeholder="Ex: À prendre au milieu du repas..."></textarea>
                </div>
            </div>

            <!-- Signature -->
            <div class="pt-4 border-t">
                <label class="block text-sm font-bold text-gray-700 mb-2">Signature numérique</label>
                <div class="relative w-full max-w-md bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200">
                    <canvas id="sigCanvas" class="w-full h-48 cursor-crosshair"></canvas>
                    <button type="button" id="clearSig" class="absolute top-2 right-2 text-xs bg-white text-red-500 px-2 py-1 rounded-lg shadow-sm border">Effacer</button>
                </div>
                <input type="hidden" name="signature_data" id="sigData">
            </div>

            <!-- Renouvellement -->
            <div class="flex items-center gap-6 pt-4 border-t">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="renouvellement" class="w-5 h-5 rounded text-green-600 focus:ring-green-500">
                    <span class="text-sm font-bold text-gray-700">Autoriser renouvellement</span>
                </label>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">Nb fois:</span>
                    <input type="number" name="nombre_renouvellements" min="0" value="0" class="w-20 px-3 py-1 border rounded-lg focus:ring-green-500">
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-gray-900 hover:bg-black text-white px-12 py-4 rounded-2xl font-bold shadow-xl transition-transform hover:scale-105">
                Valider & Enregistrer l'ordonnance
            </button>
        </div>
    </form>
</div>

<script>
    // Medication management
    const medRows = document.getElementById('medRows');
    const addMedBtn = document.getElementById('addMed');
    const medDataInput = document.getElementById('medData');

    function createRow() {
        const tr = document.createElement('tr');
        tr.className = 'group';
        tr.innerHTML = `
            <td class="py-3 pr-4"><input type="text" class="med-name w-full px-3 py-2 bg-gray-50 border-none rounded-lg focus:ring-2 focus:ring-green-500" placeholder="Nom..." required></td>
            <td class="py-3 pr-4"><input type="text" class="med-poso w-full px-3 py-2 bg-gray-50 border-none rounded-lg focus:ring-2 focus:ring-green-500" placeholder="1 mat / 1 soir..." required></td>
            <td class="py-3 pr-4"><input type="text" class="med-qte w-full px-3 py-2 bg-gray-50 border-none rounded-lg focus:ring-2 focus:ring-green-500" placeholder="1 boîte"></td>
            <td class="py-3 pr-4"><input type="text" class="med-dur w-full px-3 py-2 bg-gray-50 border-none rounded-lg focus:ring-2 focus:ring-green-500" placeholder="7j"></td>
            <td class="py-3"><button type="button" class="text-gray-300 hover:text-red-500 transition-colors"><i class="fas fa-trash-alt"></i></button></td>
        `;
        tr.querySelector('button').onclick = () => { tr.remove(); updateData(); };
        tr.querySelectorAll('input').forEach(i => i.oninput = updateData);
        medRows.appendChild(tr);
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

    addMedBtn.onclick = createRow;
    createRow();

    // Signature Canvas
    const canvas = document.getElementById('sigCanvas');
    const ctx = canvas.getContext('2d');
    const sigDataInput = document.getElementById('sigData');
    const clearBtn = document.getElementById('clearSig');
    let drawing = false;

    // Responsive canvas
    function resizeCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        ctx.scale(ratio, ratio);
        ctx.lineWidth = 2;
        ctx.strokeStyle = '#111';
    }
    window.onresize = resizeCanvas;
    resizeCanvas();

    canvas.onmousedown = (e) => { drawing = true; ctx.beginPath(); ctx.moveTo(e.offsetX, e.offsetY); };
    canvas.onmousemove = (e) => { if(!drawing) return; ctx.lineTo(e.offsetX, e.offsetY); ctx.stroke(); };
    canvas.onmouseup = () => { drawing = false; sigDataInput.value = canvas.toDataURL(); };
    clearBtn.onclick = () => { ctx.clearRect(0, 0, canvas.width, canvas.height); sigDataInput.value = ''; };

    // Form Validation
    document.getElementById('ordonnanceForm').onsubmit = function(e) {
        if(!sigDataInput.value) {
            alert("La signature est requise.");
            e.preventDefault();
        }
    };
</script>

<?php include_once '../components/doctor_layout_bottom.php'; ?>