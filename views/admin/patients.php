<?php
$page_title = "Gestion des Patients - MedAdmin";
$header_title = "Patients";
$header_icon = "fas fa-procedures";
include_once '../components/admin_layout_top.php';

function calculateAge($birthdate) {
    $birth = new DateTime($birthdate);
    $today = new DateTime();
    return $today->diff($birth)->y;
}

// Traitement des actions (Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['patient_id'])) {
    $pid = (int)$_POST['patient_id'];
    if ($_POST['action'] === 'delete') {
        try {
            $db->beginTransaction();
            $db->prepare("DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?")->execute([$pid, $pid]);
            $db->prepare("DELETE FROM rendezvous WHERE idpatient = ?")->execute([$pid]);
            $db->prepare("DELETE FROM consultation WHERE id_patient = ?")->execute([$pid]);
            $db->prepare("DELETE FROM ordonnance WHERE idpatient = ?")->execute([$pid]);
            $db->prepare("DELETE FROM profilpatient WHERE idpatient = ?")->execute([$pid]);
            $db->prepare("DELETE FROM carnetsante WHERE id_patient = ?")->execute([$pid]);
            $db->prepare("DELETE FROM dossiers_medicaux WHERE id_patient = ?")->execute([$pid]);
            $db->prepare("DELETE FROM patient WHERE id = ?")->execute([$pid]);
            $db->commit();
            $success = "Patient supprimé avec succès.";
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Impossible de supprimer ce patient : " . $e->getMessage();
        }
    }
}

// Récupérer les patients
$patients = $db->query("SELECT * FROM patient ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="fade-in space-y-8">
    <?php if (isset($success)): ?>
        <div class="p-4 bg-emerald-50 text-emerald-700 rounded-2xl border border-emerald-100 flex items-center gap-3">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="p-4 bg-red-50 text-red-700 rounded-2xl border border-red-100 flex items-center gap-3">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Table Section -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-8 border-b border-slate-50 flex justify-between items-center bg-slate-50/30">
            <div>
                <h3 class="text-lg font-bold text-slate-800">Base de Données Patients</h3>
                <p class="text-sm text-slate-500"><?= count($patients) ?> patients enregistrés</p>
            </div>
            <div class="flex gap-3">
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" id="patientSearch" placeholder="Rechercher..." class="pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-admin-500 outline-none w-64">
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                        <th class="px-8 py-4 border-b">Patient</th>
                        <th class="px-8 py-4 border-b">Sexe/Âge</th>
                        <th class="px-8 py-4 border-b">Contact</th>
                        <th class="px-8 py-4 border-b">Adresse</th>
                        <th class="px-8 py-4 border-b text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach ($patients as $p): ?>
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl <?= $p['sexe'] == 'M' ? 'bg-blue-50 text-blue-600' : 'bg-pink-50 text-pink-600' ?> flex items-center justify-center font-bold">
                                    <?= strtoupper(substr($p['nom'], 0, 1)) ?>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800"><?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?></p>
                                    <p class="text-xs text-slate-400"><?= htmlspecialchars($p['email']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase <?= $p['sexe'] == 'M' ? 'bg-blue-100 text-blue-700' : 'bg-pink-100 text-pink-700' ?>">
                                    <?= $p['sexe'] == 'M' ? 'Homme' : 'Femme' ?>
                                </span>
                                <span class="text-sm text-slate-600 font-medium"><?= calculateAge($p['datenais']) ?> ans</span>
                            </div>
                        </td>
                        <td class="px-8 py-5 text-sm text-slate-600 font-medium"><?= htmlspecialchars($p['contact'] ?? 'N/A') ?></td>
                        <td class="px-8 py-5 text-sm text-slate-500 max-w-[200px] truncate"><?= htmlspecialchars($p['adresse'] ?? 'Non renseignée') ?></td>
                        <td class="px-8 py-5 text-right">
                            <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <form method="POST" class="inline" onsubmit="return confirm('Supprimer ce patient ?')">
                                    <input type="hidden" name="patient_id" value="<?= $p['id'] ?>">
                                    <button type="submit" name="action" value="delete" class="p-2 text-slate-400 hover:text-red-500"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.getElementById('patientSearch').oninput = function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('tbody tr').forEach(tr => {
            const text = tr.innerText.toLowerCase();
            tr.style.display = text.includes(q) ? '' : 'none';
        });
    };
</script>

<?php include_once '../components/admin_layout_bottom.php'; ?>