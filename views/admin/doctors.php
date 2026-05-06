<?php
$page_title = "Gestion des Médecins - MedAdmin";
$header_title = "Médecins";
$header_icon = "fas fa-user-md";
include_once '../components/admin_layout_top.php';

// Traitement des actions (Verify/Reject/Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['medecin_id'])) {
    $mid = (int)$_POST['medecin_id'];
    if ($_POST['action'] === 'verify') {
        $db->prepare("UPDATE medecin SET verification_status = 'verified' WHERE id = ?")->execute([$mid]);
        $success = "Médecin vérifié avec succès.";
    } elseif ($_POST['action'] === 'reject') {
        $db->prepare("UPDATE medecin SET verification_status = 'rejected' WHERE id = ?")->execute([$mid]);
        $success = "Médecin rejeté.";
    } elseif ($_POST['action'] === 'delete') {
        $db->prepare("DELETE FROM medecin WHERE id = ?")->execute([$mid]);
        $success = "Médecin supprimé.";
    }
}

// Récupérer les médecins
$doctors = $db->query("SELECT m.*, s.nomspecialite FROM medecin m LEFT JOIN specialite s ON m.idspecialite = s.id ORDER BY m.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="fade-in space-y-8">
    <?php if (isset($success)): ?>
        <div class="p-4 bg-emerald-50 text-emerald-700 rounded-2xl border border-emerald-100 flex items-center gap-3">
            <i class="fas fa-check-circle"></i> <?= $success ?>
        </div>
    <?php endif; ?>

    <!-- Table Section -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-8 border-b border-slate-50 flex justify-between items-center bg-slate-50/30">
            <div>
                <h3 class="text-lg font-bold text-slate-800">Liste du Personnel Médical</h3>
                <p class="text-sm text-slate-500"><?= count($doctors) ?> médecins enregistrés</p>
            </div>
            <div class="flex gap-3">
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" id="doctorSearch" placeholder="Rechercher..." class="pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-admin-500 outline-none w-64">
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                        <th class="px-8 py-4 border-b">Médecin</th>
                        <th class="px-8 py-4 border-b">Spécialité</th>
                        <th class="px-8 py-4 border-b">Statut</th>
                        <th class="px-8 py-4 border-b">Inscription</th>
                        <th class="px-8 py-4 border-b text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach ($doctors as $doc): ?>
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-admin-50 text-admin-600 flex items-center justify-center font-bold">
                                    <?= strtoupper(substr($doc['nom'], 0, 1)) ?>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800">Dr. <?= htmlspecialchars($doc['prenom'] . ' ' . $doc['nom']) ?></p>
                                    <p class="text-xs text-slate-400"><?= htmlspecialchars($doc['email']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-5 text-sm text-slate-600">
                            <span class="px-3 py-1 bg-slate-100 rounded-lg"><?= htmlspecialchars($doc['nomspecialite'] ?? 'Généraliste') ?></span>
                        </td>
                        <td class="px-8 py-5">
                            <?php if ($doc['verification_status'] === 'verified'): ?>
                                <span class="px-3 py-1 bg-emerald-50 text-emerald-600 rounded-full text-[10px] font-bold uppercase tracking-wide">Vérifié</span>
                            <?php elseif ($doc['verification_status'] === 'pending'): ?>
                                <span class="px-3 py-1 bg-amber-50 text-amber-600 rounded-full text-[10px] font-bold uppercase tracking-wide">En attente</span>
                            <?php else: ?>
                                <span class="px-3 py-1 bg-red-50 text-red-600 rounded-full text-[10px] font-bold uppercase tracking-wide">Rejeté</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-8 py-5 text-sm text-slate-500"><?= date('d/m/Y', strtotime($doc['created_at'])) ?></td>
                        <td class="px-8 py-5 text-right">
                            <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="doctor_details.php?id=<?= $doc['id'] ?>" class="p-2 text-slate-400 hover:text-admin-600" title="Détails"><i class="fas fa-eye"></i></a>
                                <form method="POST" class="inline" onsubmit="return confirm('Supprimer ce médecin ?')">
                                    <input type="hidden" name="medecin_id" value="<?= $doc['id'] ?>">
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
    document.getElementById('doctorSearch').oninput = function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('tbody tr').forEach(tr => {
            const text = tr.innerText.toLowerCase();
            tr.style.display = text.includes(q) ? '' : 'none';
        });
    };
</script>

<?php include_once '../components/admin_layout_bottom.php'; ?>