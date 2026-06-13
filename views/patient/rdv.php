<?php
// ── Traitement POST avant tout output ─────────────────────────────────────────
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/session.php';
requireLogin();
requireRole('patient');

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $idmedecin   = $_POST['medecin']    ?? null;
        $idspecialite = $_POST['specialite'] ?? null;
        $date        = $_POST['date']        ?? null;
        $heure       = $_POST['heure']       ?? null;

        if (!$idmedecin || !$idspecialite || !$date || !$heure) {
            throw new Exception("Veuillez remplir tous les champs requis.");
        }

        $dateheure = $date . ' ' . $heure;

        // Anti-conflit de créneau
        $stmt = db()->prepare("SELECT COUNT(*) FROM rendezvous WHERE idmedecin = ? AND dateheure = ? AND statut != 'annulé'");
        $stmt->execute([$idmedecin, $dateheure]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Ce créneau est déjà réservé. Veuillez en choisir un autre.");
        }

        $stmt = db()->prepare("INSERT INTO rendezvous (dateheure, statut, idmedecin, idpatient, idspecialite) VALUES (:dateheure, 'en attente', :idmedecin, :idpatient, :idspecialite)");
        $stmt->execute([
            'dateheure'    => $dateheure,
            'idmedecin'    => $idmedecin,
            'idpatient'    => $user_id,
            'idspecialite' => $idspecialite,
        ]);

        $_SESSION['success'] = "Votre rendez-vous a été pris avec succès.";
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }

    header('Location: rdv.php');
    exit();
}

// ── Affichage ──────────────────────────────────────────────────────────────────
$page_title   = "Mes Rendez-vous - MedConnect";
$header_title = "Mes Rendez-vous";
$header_icon  = "fas fa-calendar-alt";
include_once '../components/patient_layout_top.php';

// Récupérer les rendez-vous
$stmt = db()->prepare("
    SELECT r.id, r.dateheure, r.statut, m.nom AS nom_medecin, m.prenom AS prenom_medecin, s.nomspecialite
    FROM rendezvous r
    JOIN medecin m ON r.idmedecin = m.id
    LEFT JOIN specialite s ON r.idspecialite = s.id
    WHERE r.idpatient = :idpatient
    ORDER BY r.dateheure DESC
");
$stmt->execute(['idpatient' => $user_id]);
$rendezvous = $stmt->fetchAll(PDO::FETCH_ASSOC);

$rdvs_a_venir  = array_filter($rendezvous, fn($r) => strtotime($r['dateheure']) >= strtotime('today'));
$rdvs_confirmes = array_filter($rdvs_a_venir, fn($r) => $r['statut'] === 'confirmé');
$rdvs_en_attente = array_filter($rdvs_a_venir, fn($r) => $r['statut'] === 'en attente');
?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
        <span><?= htmlspecialchars($_SESSION['success']) ?></span>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
        <span><?= htmlspecialchars($_SESSION['error']) ?></span>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- Statistiques -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-[#3b82f6]">Rendez-vous à venir</p>
                <h3 class="text-2xl font-bold text-[#1e40af]"><?= count($rdvs_a_venir) ?></h3>
            </div>
            <div class="w-12 h-12 rounded-full bg-[#EFF6FF] flex items-center justify-center">
                <i class="fas fa-calendar-check text-xl text-[#3b82f6]"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-[#10b981]">Confirmés</p>
                <h3 class="text-2xl font-bold text-[#1e40af]"><?= count($rdvs_confirmes) ?></h3>
            </div>
            <div class="w-12 h-12 rounded-full bg-[#ECFDF5] flex items-center justify-center">
                <i class="fas fa-check-circle text-xl text-[#10b981]"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-[#f59e0b]">En attente</p>
                <h3 class="text-2xl font-bold text-[#1e40af]"><?= count($rdvs_en_attente) ?></h3>
            </div>
            <div class="w-12 h-12 rounded-full bg-[#FFFBEB] flex items-center justify-center">
                <i class="fas fa-clock text-xl text-[#f59e0b]"></i>
            </div>
        </div>
    </div>
</div>

<!-- Bouton formulaire -->
<button onclick="toggleForm()" class="mb-6 bg-[#3b82f6] hover:bg-[#2563eb] text-white px-6 py-3 rounded-lg transition-colors duration-300 flex items-center gap-2">
    <i class="fas fa-plus"></i> Prendre un rendez-vous
</button>

<!-- Formulaire -->
<div id="formRdv" class="bg-white rounded-xl shadow-lg p-6 mb-8 glass-effect hidden">
    <form method="POST" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-[#1e40af] mb-2">Spécialité :</label>
                <select name="specialite" id="specialite" required class="w-full border border-gray-200 rounded-lg px-4 py-3 focus:outline-none focus:border-[#3b82f6]">
                    <option value="">-- Choisir une spécialité --</option>
                    <?php
                    $specialites = db()->query("SELECT id, nomspecialite FROM specialite")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($specialites as $s) {
                        echo "<option value='" . (int)$s['id'] . "'>" . htmlspecialchars($s['nomspecialite']) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#1e40af] mb-2">Médecin :</label>
                <select name="medecin" id="medecin" required disabled class="w-full border border-gray-200 rounded-lg px-4 py-3 focus:outline-none focus:border-[#3b82f6]">
                    <option value="">-- Sélectionner une spécialité d'abord --</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#1e40af] mb-2">Date :</label>
                <input type="date" name="date" id="date" required min="<?= date('Y-m-d') ?>" class="w-full border border-gray-200 rounded-lg px-4 py-3 focus:outline-none focus:border-[#3b82f6]">
            </div>
            <div>
                <label class="block text-sm font-medium text-[#1e40af] mb-2">Heure :</label>
                <select name="heure" id="heure" required disabled class="w-full border border-gray-200 rounded-lg px-4 py-3 focus:outline-none focus:border-[#3b82f6]">
                    <option value="">-- Sélectionner une date d'abord --</option>
                </select>
            </div>
        </div>
        <div class="flex justify-end">
            <button type="submit" class="bg-[#10b981] hover:bg-[#059669] text-white px-6 py-3 rounded-lg transition-colors duration-300 flex items-center gap-2">
                <i class="fas fa-check"></i> Confirmer
            </button>
        </div>
    </form>
</div>

<!-- Liste des rendez-vous -->
<div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
    <h2 class="text-xl font-semibold text-[#1e40af] flex items-center mb-6">
        <i class="fas fa-calendar-alt mr-2"></i> Mes rendez-vous
    </h2>

    <?php if (count($rendezvous) > 0): ?>
        <div class="space-y-4">
            <?php foreach ($rendezvous as $rdv): ?>
                <div class="p-4 bg-[#F1F8E9] rounded-lg hover:bg-[#E8F5E9]" data-rdv-id="<?= $rdv['id'] ?>">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="font-medium text-[#1B5E20]">Dr. <?= htmlspecialchars($rdv['nom_medecin'] . ' ' . $rdv['prenom_medecin']) ?></p>
                            <p class="text-sm text-[#558B2F]"><?= date('d/m/Y H:i', strtotime($rdv['dateheure'])) ?></p>
                            <?php if ($rdv['nomspecialite']): ?>
                                <p class="text-sm text-[#558B2F]">Spécialité : <?= htmlspecialchars($rdv['nomspecialite']) ?></p>
                            <?php endif; ?>
                        </div>
                        <span class="px-3 py-1 rounded-full text-sm status-badge
                            <?php
                            $s = $rdv['statut'];
                            if (in_array($s, ['confirmé', 'accepté'])) echo 'bg-green-200 text-green-800';
                            elseif (in_array($s, ['annulé', 'refusé'])) echo 'bg-red-200 text-red-800';
                            else echo 'bg-yellow-200 text-yellow-800';
                            ?>">
                            <?= ucfirst($rdv['statut']) ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-8">
            <div class="w-16 h-16 rounded-full bg-[#FEE2E2] flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-calendar-times text-[#991B1B] text-2xl"></i>
            </div>
            <h3 class="text-xl font-semibold text-[#1e40af] mb-2">Aucun rendez-vous</h3>
            <p class="text-[#3b82f6]">Prenez votre premier rendez-vous en cliquant sur le bouton ci-dessus.</p>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleForm() {
    const form = document.getElementById('formRdv');
    form.classList.toggle('hidden');
    if (!form.classList.contains('hidden')) {
        document.getElementById('specialite').value = '';
        document.getElementById('medecin').innerHTML = '<option value="">Sélectionnez d\'abord une spécialité</option>';
        document.getElementById('medecin').disabled = true;
        document.getElementById('date').value = '';
        document.getElementById('heure').innerHTML = '<option value="">Sélectionnez d\'abord une date</option>';
        document.getElementById('heure').disabled = true;
    }
}

document.getElementById('specialite').addEventListener('change', function () {
    const id = this.value;
    const sel = document.getElementById('medecin');
    if (!id) { sel.innerHTML = '<option value="">Sélectionnez d\'abord une spécialité</option>'; sel.disabled = true; return; }
    sel.innerHTML = '<option value="">Chargement...</option>';
    sel.disabled = true;
    fetch(`../../get_medecins.php?specialite_id=${id}`)
        .then(r => r.json())
        .then(medecins => {
            sel.disabled = false;
            if (medecins.length > 0) {
                sel.innerHTML = '<option value="">Sélectionnez un médecin</option>' +
                    medecins.map(m => `<option value="${m.id}">Dr. ${m.prenom} ${m.nom} - ${m.nomspecialite}</option>`).join('');
            } else {
                sel.innerHTML = '<option value="">Aucun médecin disponible</option>';
            }
        })
        .catch(() => { sel.disabled = false; sel.innerHTML = '<option value="">Erreur de chargement</option>'; });
});

function checkDisponibilites() {
    const medecinId = document.getElementById('medecin').value;
    const date = document.getElementById('date').value;
    const sel = document.getElementById('heure');
    if (!medecinId || !date) { sel.innerHTML = '<option value="">Sélectionnez médecin et date</option>'; sel.disabled = true; return; }
    sel.innerHTML = '<option value="">Chargement...</option>';
    sel.disabled = true;
    fetch(`../../check_disponibilite.php?medecin_id=${medecinId}&date=${date}`)
        .then(r => r.json())
        .then(creneaux => {
            sel.disabled = false;
            if (creneaux.length > 0) {
                sel.innerHTML = '<option value="">Sélectionnez une heure</option>' +
                    creneaux.map(c => `<option value="${c}">${c}</option>`).join('');
            } else {
                sel.innerHTML = '<option value="">Aucun créneau disponible</option>';
            }
        })
        .catch(() => { sel.disabled = false; sel.innerHTML = '<option value="">Erreur de chargement</option>'; });
}

document.getElementById('medecin').addEventListener('change', checkDisponibilites);
document.getElementById('date').addEventListener('change', checkDisponibilites);
</script>

<?php include_once '../components/patient_layout_bottom.php'; ?>
