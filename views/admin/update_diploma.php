<?php
$page_title = "Gestion des Diplômes - MedAdmin";
$header_title = "Outil Diplômes";
$header_icon = "fas fa-file-signature";
include_once '../components/admin_layout_top.php';

$diplomes_dir = __DIR__ . '/../../uploads/diplomes/';
$available_files = is_dir($diplomes_dir) ? array_diff(scandir($diplomes_dir), ['.', '..']) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['doctor_id'], $_POST['new_diploma'])) {
    $db->prepare("UPDATE medecin SET diplome = ? WHERE id = ?")->execute([$_POST['new_diploma'], $_POST['doctor_id']]);
    $success = "Diplôme mis à jour.";
}

$doctors = $db->query("SELECT id, nom, prenom, diplome FROM medecin WHERE diplome IS NOT NULL AND diplome != ''")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="fade-in space-y-8">
    <?php if (isset($success)): ?>
        <div class="p-4 bg-emerald-50 text-emerald-700 rounded-2xl border border-emerald-100"><?= $success ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-8 border-b border-slate-50 bg-slate-50/30">
            <h3 class="text-lg font-bold text-slate-800">Réparation des Liens Diplômes</h3>
            <p class="text-sm text-slate-500">Associez les fichiers orphelins aux comptes médecins correspondants.</p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                        <th class="px-8 py-4 border-b">ID</th>
                        <th class="px-8 py-4 border-b">Médecin</th>
                        <th class="px-8 py-4 border-b">Fichier Actuel</th>
                        <th class="px-8 py-4 border-b text-right">Nouvelle Association</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach ($doctors as $doc): 
                        $exists = in_array($doc['diplome'], $available_files);
                    ?>
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-8 py-5 text-sm text-slate-400 font-mono">#<?= $doc['id'] ?></td>
                        <td class="px-8 py-5 font-bold text-slate-800"><?= htmlspecialchars($doc['prenom'] . ' ' . $doc['nom']) ?></td>
                        <td class="px-8 py-5">
                            <span class="px-3 py-1 rounded-lg text-xs font-bold <?= $exists ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600 animate-pulse' ?>">
                                <?= htmlspecialchars($doc['diplome']) ?>
                                <?= $exists ? '' : ' (MANQUANT)' ?>
                            </span>
                        </td>
                        <td class="px-8 py-5 text-right">
                            <form method="POST" class="flex justify-end gap-2">
                                <input type="hidden" name="doctor_id" value="<?= $doc['id'] ?>">
                                <select name="new_diploma" class="px-3 py-1.5 bg-slate-50 border-none rounded-lg text-xs font-bold outline-none ring-1 ring-slate-200 focus:ring-admin-500">
                                    <?php foreach ($available_files as $file): ?>
                                        <option value="<?= htmlspecialchars($file) ?>" <?= $file === $doc['diplome'] ? 'selected' : '' ?>><?= htmlspecialchars($file) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="p-2 bg-admin-600 text-white rounded-lg hover:bg-admin-700 shadow-md shadow-admin-500/20">
                                    <i class="fas fa-save"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once '../components/admin_layout_bottom.php'; ?>
