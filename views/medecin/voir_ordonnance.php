<?php
$page_title = "Détails Ordonnance - MedConnect";
$header_title = "Détails de l'Ordonnance";
$header_icon = "fas fa-file-prescription";
include_once '../components/doctor_layout_top.php';

$ordonnance_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$ordonnance_id) { echo "<script>window.location.href='ordonnances.php';</script>"; exit; }

$stmt = $db->prepare('SELECT o.*, p.nom as patient_nom, p.prenom as patient_prenom, p.email as patient_email, p.contact as patient_contact FROM ordonnance o JOIN patient p ON o.idpatient = p.id WHERE o.id = ? AND o.idmedecin = ?');
$stmt->execute([$ordonnance_id, $user_id]);
$o = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$o) { echo "<script>window.location.href='ordonnances.php';</script>"; exit; }
?>

<div class="max-w-4xl mx-auto fade-in">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Ordonnance #<?= $o['id'] ?></h2>
            <p class="text-gray-500">Délivrée le <?= date('d/m/Y', strtotime($o['date_creation'])) ?></p>
        </div>
        <div class="flex gap-3">
            <a href="telecharger_ordonnance.php?id=<?= $o['id'] ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-xl flex items-center gap-2 shadow-lg transition-transform hover:scale-105">
                <i class="fas fa-download"></i> PDF
            </a>
            <a href="modifier_ordonnance.php?id=<?= $o['id'] ?>" class="p-2 bg-amber-50 text-amber-600 rounded-xl hover:bg-amber-100 transition-colors" title="Modifier">
                <i class="fas fa-edit"></i>
            </a>
            <a href="ordonnances.php" class="bg-gray-100 text-gray-600 px-6 py-2 rounded-xl hover:bg-gray-200 transition-colors">
                Retour
            </a>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
        <div class="p-8 space-y-10">
            <!-- Header Ordonnance -->
            <div class="flex justify-between border-b pb-8">
                <div>
                    <h3 class="text-xl font-bold text-gray-800">Dr. <?= $_SESSION['prenom'] . ' ' . $_SESSION['nom'] ?></h3>
                    <p class="text-sm text-gray-500">Médecin traitant</p>
                </div>
                <div class="text-right">
                    <p class="font-bold text-gray-800">PATIENT</p>
                    <p class="text-gray-600"><?= htmlspecialchars($o['patient_prenom'] . ' ' . $o['patient_nom']) ?></p>
                    <p class="text-xs text-gray-400"><?= htmlspecialchars($o['patient_contact']) ?></p>
                </div>
            </div>

            <!-- Médicaments -->
            <section>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="text-xs text-gray-400 uppercase tracking-wider border-b">
                            <tr>
                                <th class="pb-3">Désignation</th>
                                <th class="pb-3">Posologie</th>
                                <th class="pb-3">Qté</th>
                                <th class="pb-3">Durée</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php
                            $m = explode("\n", $o['medicaments']);
                            $p = explode("\n", $o['posologie']);
                            $q = explode("\n", $o['quantite']);
                            $d = explode("\n", $o['duree_medicament']);
                            for ($i = 0; $i < count($m); $i++): ?>
                                <tr>
                                    <td class="py-4 font-bold text-gray-800"><?= htmlspecialchars($m[$i]) ?></td>
                                    <td class="py-4 text-gray-600"><?= htmlspecialchars($p[$i] ?? '') ?></td>
                                    <td class="py-4 text-gray-600"><?= htmlspecialchars($q[$i] ?? '') ?></td>
                                    <td class="py-4 text-gray-600"><?= htmlspecialchars($d[$i] ?? '') ?></td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Pied de page ordonnance -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 pt-8">
                <div>
                    <h4 class="text-sm font-bold text-gray-400 uppercase mb-3">Instructions</h4>
                    <p class="text-gray-700 leading-relaxed italic">
                        <?= $o['instructions'] ? nl2br(htmlspecialchars($o['instructions'])) : 'Aucune instruction particulière.' ?>
                    </p>
                </div>
                <div class="flex flex-col items-center justify-center p-6 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200">
                    <h4 class="text-xs font-bold text-gray-400 uppercase mb-4">Signature du médecin</h4>
                    <?php if ($o['signature']): ?>
                        <img src="<?= htmlspecialchars($o['signature']) ?>" class="max-h-24 mix-blend-multiply" alt="Signature">
                    <?php else: ?>
                        <p class="text-xs text-gray-300">Non signée</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="pt-8 border-t flex justify-between text-xs text-gray-400">
                <p>Date de validité : <?= date('d/m/Y', strtotime($o['date_validite'])) ?></p>
                <p>Renouvelable : <?= $o['renouvellement'] ? 'OUI (' . $o['nombre_renouvellements'] . ' fois)' : 'NON' ?></p>
            </div>
        </div>
    </div>
</div>

<?php include_once '../components/doctor_layout_bottom.php'; ?>