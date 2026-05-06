<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

requireLogin();
requireRole('admin');

if (!isset($_GET['id'])) exit;
$id = (int)$_GET['id'];
$db = db();
$stmt = $db->prepare("SELECT m.*, s.nomspecialite FROM medecin m LEFT JOIN specialite s ON m.idspecialite = s.id WHERE m.id = ?");
$stmt->execute([$id]);
$d = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$d) exit;
?>

<div class="space-y-6">
    <div class="flex items-center gap-6 p-6 bg-slate-50 rounded-2xl border border-slate-100">
        <div class="w-20 h-20 rounded-2xl bg-white text-admin-600 flex items-center justify-center text-3xl font-bold shadow-sm border border-slate-100">
            <?= strtoupper(substr($d['nom'], 0, 1)) ?>
        </div>
        <div>
            <h3 class="text-xl font-bold text-slate-800">Dr. <?= htmlspecialchars($d['prenom'] . ' ' . $d['nom']) ?></h3>
            <p class="text-admin-600 font-bold text-sm"><?= htmlspecialchars($d['nomspecialite'] ?? 'Généraliste') ?></p>
            <p class="text-xs text-slate-400 mt-1 uppercase tracking-wider font-bold">RPPS: <?= htmlspecialchars($d['num'] ?? 'N/A') ?></p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div class="p-4 bg-white rounded-xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Email</p>
            <p class="text-sm font-bold text-slate-700 truncate"><?= htmlspecialchars($d['email']) ?></p>
        </div>
        <div class="p-4 bg-white rounded-xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Téléphone</p>
            <p class="text-sm font-bold text-slate-700"><?= htmlspecialchars($d['contact'] ?? 'N/A') ?></p>
        </div>
        <div class="p-4 bg-white rounded-xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Naissance</p>
            <p class="text-sm font-bold text-slate-700"><?= $d['datenais'] ? date('d/m/Y', strtotime($d['datenais'])) : 'N/A' ?></p>
        </div>
        <div class="p-4 bg-white rounded-xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Inscription</p>
            <p class="text-sm font-bold text-slate-700"><?= date('d/m/Y', strtotime($d['created_at'])) ?></p>
        </div>
    </div>

    <?php if ($d['diplome']): ?>
    <div class="p-4 bg-indigo-50/50 rounded-2xl border border-indigo-100 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-white text-indigo-600 rounded-lg flex items-center justify-center shadow-sm">
                <i class="fas fa-file-pdf"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-indigo-900">Diplôme d'État</p>
                <p class="text-[10px] text-indigo-400 font-bold truncate max-w-[150px]"><?= htmlspecialchars($d['diplome']) ?></p>
            </div>
        </div>
        <a href="../../uploads/diplomes/<?= urlencode($d['diplome']) ?>" target="_blank" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-xs font-bold hover:bg-indigo-700 shadow-md shadow-indigo-200 transition-all">
            Visualiser
        </a>
    </div>
    <?php endif; ?>

    <div class="space-y-3">
        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Commentaire de validation</label>
        <textarea id="modalComment" class="w-full p-4 bg-slate-50 border-none rounded-2xl text-sm focus:ring-2 focus:ring-admin-500 outline-none min-h-[100px]" placeholder="Motif de validation ou de rejet..."></textarea>
    </div>
</div>

<script>
    // Link modal comment to parent form if needed
    const modalComment = document.getElementById('modalComment');
    if (modalComment) {
        modalComment.oninput = function() {
            // Can be used to sync with a hidden field in the parent form
            const parentHidden = document.getElementById('parentCommentHidden');
            if (parentHidden) parentHidden.value = this.value;
        };
    }
</script>
