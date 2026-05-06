<?php
$page_title = "Vérification des Médecins - MedAdmin";
$header_title = "Vérifications";
$header_icon = "fas fa-check-double";
include_once '../components/admin_layout_top.php';

// Traitement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['medecin_id'])) {
    $mid = (int)$_POST['medecin_id'];
    $status = ($_POST['action'] === 'verify') ? 'verified' : 'rejected';
    $db->prepare("UPDATE medecin SET verification_status = ? WHERE id = ?")->execute([$status, $mid]);
    $success = "Statut mis à jour avec succès.";
}

// Récupérer les médecins en attente
$pending = $db->query("SELECT m.*, s.nomspecialite FROM medecin m LEFT JOIN specialite s ON m.idspecialite = s.id WHERE verification_status = 'pending' ORDER BY created_at ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="fade-in space-y-8">
    <?php if (isset($success)): ?>
        <div class="p-4 bg-emerald-50 text-emerald-700 rounded-2xl border border-emerald-100 flex items-center gap-3">
            <i class="fas fa-check-circle"></i> <?= $success ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 gap-6">
        <?php if (empty($pending)): ?>
            <div class="bg-white p-12 rounded-3xl shadow-sm border border-slate-100 text-center">
                <div class="w-20 h-20 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">Tout est à jour !</h3>
                <p class="text-slate-500">Il n'y a aucune demande de vérification en attente pour le moment.</p>
                <a href="doctors.php" class="inline-block mt-6 text-sm font-bold text-admin-600 hover:underline">Gérer les médecins existants</a>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-8 border-b border-slate-50 bg-slate-50/30">
                    <h3 class="text-lg font-bold text-slate-800">Demandes en Attente</h3>
                    <p class="text-sm text-slate-500">Examinez les diplômes et validez les comptes praticiens.</p>
                </div>
                
                <div class="divide-y divide-slate-50">
                    <?php foreach ($pending as $doc): ?>
                    <div class="p-8 flex flex-col md:flex-row items-center justify-between gap-6 hover:bg-slate-50/50 transition-colors">
                        <div class="flex items-center gap-6">
                            <div class="w-16 h-16 rounded-2xl bg-admin-50 text-admin-600 flex items-center justify-center text-2xl font-bold">
                                <?= strtoupper(substr($doc['nom'], 0, 1)) ?>
                            </div>
                            <div>
                                <h4 class="text-lg font-bold text-slate-800">Dr. <?= htmlspecialchars($doc['prenom'] . ' ' . $doc['nom']) ?></h4>
                                <p class="text-sm text-admin-600 font-semibold mb-1"><?= htmlspecialchars($doc['nomspecialite'] ?? 'Généraliste') ?></p>
                                <div class="flex gap-4 text-xs text-slate-400">
                                    <span><i class="fas fa-id-card mr-1"></i> RPPS: <?= htmlspecialchars($doc['num'] ?? 'N/A') ?></span>
                                    <span><i class="fas fa-calendar-alt mr-1"></i> Inscrit le <?= date('d/m/Y', strtotime($doc['created_at'])) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <a href="doctor_details.php?id=<?= $doc['id'] ?>" class="px-5 py-2.5 bg-slate-100 text-slate-600 rounded-xl text-sm font-bold hover:bg-slate-200 transition-colors">
                                Examiner
                            </a>
                            <form method="POST" class="flex gap-2">
                                <input type="hidden" name="medecin_id" value="<?= $doc['id'] ?>">
                                <button type="submit" name="action" value="reject" class="px-5 py-2.5 bg-red-50 text-red-600 rounded-xl text-sm font-bold hover:bg-red-100 transition-colors">
                                    Rejeter
                                </button>
                                <button type="submit" name="action" value="verify" class="px-5 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-bold hover:bg-emerald-700 transition-shadow shadow-lg shadow-emerald-500/20">
                                    Approuver
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../components/admin_layout_bottom.php'; ?>