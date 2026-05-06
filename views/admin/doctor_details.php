<?php
$page_title = "Détails Médecin - MedAdmin";
$header_title = "Détails du Praticien";
$header_icon = "fas fa-user-md";
include_once '../components/admin_layout_top.php';

$medecin_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$medecin_id) { echo "<script>window.location.href='doctors.php';</script>"; exit; }

$stmt = $db->prepare("SELECT m.*, s.nomspecialite FROM medecin m LEFT JOIN specialite s ON m.idspecialite = s.id WHERE m.id = ?");
$stmt->execute([$medecin_id]);
$d = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$d) { echo "<script>window.location.href='doctors.php';</script>"; exit; }

// Traitement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $status = ($_POST['action'] === 'verify') ? 'verified' : 'rejected';
    $db->prepare("UPDATE medecin SET verification_status = ? WHERE id = ?")->execute([$status, $medecin_id]);
    $success = "Statut mis à jour.";
    $d['verification_status'] = $status;
}
?>

<div class="fade-in space-y-8">
    <div class="flex justify-between items-center">
        <a href="doctors.php" class="text-slate-500 hover:text-admin-600 flex items-center gap-2 font-bold transition-colors">
            <i class="fas fa-arrow-left"></i> Retour à la liste
        </a>
        <div class="flex gap-3">
            <?php if ($d['verification_status'] === 'pending'): ?>
                <form method="POST" class="flex gap-3">
                    <button type="submit" name="action" value="reject" class="px-6 py-2.5 bg-red-50 text-red-600 rounded-xl font-bold hover:bg-red-100 transition-colors">Rejeter</button>
                    <button type="submit" name="action" value="verify" class="px-6 py-2.5 bg-emerald-600 text-white rounded-xl font-bold hover:bg-emerald-700 shadow-lg shadow-emerald-500/20 transition-all">Approuver</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <div class="p-4 bg-emerald-50 text-emerald-700 rounded-2xl border border-emerald-100"><?= $success ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Profile Card -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 text-center">
                <div class="w-24 h-24 rounded-3xl bg-admin-50 text-admin-600 mx-auto mb-6 flex items-center justify-center text-3xl font-bold">
                    <?= strtoupper(substr($d['nom'], 0, 1)) ?>
                </div>
                <h3 class="text-xl font-bold text-slate-800">Dr. <?= htmlspecialchars($d['prenom'] . ' ' . $d['nom']) ?></h3>
                <p class="text-sm font-bold text-admin-600 mb-6"><?= htmlspecialchars($d['nomspecialite'] ?? 'Généraliste') ?></p>
                
                <div class="flex flex-col gap-2">
                    <?php if ($d['verification_status'] === 'verified'): ?>
                        <span class="w-full py-2 bg-emerald-50 text-emerald-600 rounded-xl text-xs font-bold uppercase tracking-wider">Compte Vérifié</span>
                    <?php elseif ($d['verification_status'] === 'pending'): ?>
                        <span class="w-full py-2 bg-amber-50 text-amber-600 rounded-xl text-xs font-bold uppercase tracking-wider">En attente de validation</span>
                    <?php else: ?>
                        <span class="w-full py-2 bg-red-50 text-red-600 rounded-xl text-xs font-bold uppercase tracking-wider">Compte Rejeté</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
                <h4 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-6">Documents</h4>
                <?php if ($d['diplome']): ?>
                <a href="view_diploma.php?filename=<?= urlencode($d['diplome']) ?>" target="_blank" class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl border border-dashed border-slate-200 hover:border-admin-400 transition-colors group">
                    <div class="w-10 h-10 rounded-xl bg-white text-admin-600 flex items-center justify-center shadow-sm">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-slate-800 truncate">Diplôme Certifié</p>
                        <p class="text-[10px] text-slate-400 uppercase font-bold">Cliquez pour voir</p>
                    </div>
                    <i class="fas fa-external-link-alt text-slate-300 group-hover:text-admin-500 transition-colors"></i>
                </a>
                <?php else: ?>
                    <p class="text-sm text-red-500 font-medium italic"><i class="fas fa-exclamation-triangle mr-2"></i>Aucun document fourni</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Details -->
        <div class="lg:col-span-2 space-y-8">
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
                <h3 class="text-lg font-bold text-slate-800 mb-8 border-b pb-4">Dossier Professionnel</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Numéro RPPS</label>
                        <p class="text-slate-800 font-bold"><?= htmlspecialchars($d['num'] ?? 'N/A') ?></p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Date d'inscription</label>
                        <p class="text-slate-800 font-bold"><?= date('d/m/Y à H:i', strtotime($d['created_at'])) ?></p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Email Contact</label>
                        <p class="text-slate-800 font-bold"><?= htmlspecialchars($d['email']) ?></p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Téléphone</label>
                        <p class="text-slate-800 font-bold"><?= htmlspecialchars($d['contact'] ?? 'N/A') ?></p>
                    </div>
                    <div class="space-y-1 md:col-span-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Date de naissance</label>
                        <p class="text-slate-800 font-bold"><?= $d['datenais'] ? date('d/m/Y', strtotime($d['datenais'])) : 'N/A' ?></p>
                    </div>
                </div>
            </div>

            <!-- Timeline/History can be added here if needed -->
            <div class="bg-slate-50 p-8 rounded-3xl border-2 border-dashed border-slate-200">
                <h3 class="text-lg font-bold text-slate-400 mb-6 flex items-center gap-3">
                    <i class="fas fa-history"></i> Historique des Actions
                </h3>
                <div class="space-y-6">
                    <div class="flex gap-4">
                        <div class="w-px bg-slate-200 relative">
                            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-2 h-2 rounded-full bg-admin-400 ring-4 ring-admin-50"></div>
                        </div>
                        <div class="pb-4">
                            <p class="text-xs font-bold text-slate-400 mb-1">AUJOURD'HUI</p>
                            <p class="text-sm text-slate-600 font-medium italic">Aucun changement récent enregistré.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../components/admin_layout_bottom.php'; ?>
