<?php
$page_title = "Détails Patient - MedConnect";
$header_title = "Dossier Médical";
$header_icon = "fas fa-user-injured";
include_once '../components/doctor_layout_top.php';

$patient_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$patient_id) { echo "<script>window.location.href='patients.php';</script>"; exit; }

// Récupérer les informations du patient
$stmt = $db->prepare("
    SELECT p.*, pp.adresse, pp.profession, cs.groupesanguin, cs.taille, cs.poids, cs.allergie, cs.electrophorese
    FROM patient p
    LEFT JOIN profilpatient pp ON p.id = pp.idpatient
    LEFT JOIN carnetsante cs ON p.id = cs.id_patient
    WHERE p.id = ?
");
$stmt->execute([$patient_id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$p) { echo "<script>window.location.href='patients.php';</script>"; exit; }

// Consultations
$stmt = $db->prepare("SELECT c.*, m.nom as medecin_nom, m.prenom as medecin_prenom FROM consultation c JOIN medecin m ON c.id_medecin = m.id WHERE c.id_patient = ? ORDER BY c.date_consultation DESC");
$stmt->execute([$patient_id]);
$consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ordonnances
$stmt = $db->prepare("SELECT o.*, m.nom as medecin_nom, m.prenom as medecin_prenom FROM ordonnance o JOIN medecin m ON o.idmedecin = m.id WHERE o.idpatient = ? ORDER BY o.date_creation DESC");
$stmt->execute([$patient_id]);
$ordonnances = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex justify-between items-center mb-8 fade-in">
    <div class="flex items-center gap-4">
        <div class="w-16 h-16 rounded-2xl bg-green-600 text-white flex items-center justify-center text-2xl font-bold shadow-lg">
            <?= strtoupper(substr($p['prenom'], 0, 1) . substr($p['nom'], 0, 1)) ?>
        </div>
        <div>
            <h2 class="text-3xl font-bold text-gray-800"><?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?></h2>
            <p class="text-gray-500">ID Patient: #<?= $p['id'] ?> • <?= $p['sexe'] === 'M' ? 'Homme' : 'Femme' ?></p>
        </div>
    </div>
    <div class="flex gap-3">
        <a href="nouvelle_consultation.php?patient_id=<?= $p['id'] ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-xl flex items-center gap-2 shadow-lg transition-transform hover:scale-105">
            <i class="fas fa-stethoscope"></i>Nouvelle Consultation
        </a>
        <a href="patients.php" class="bg-gray-100 text-gray-600 px-6 py-2 rounded-xl hover:bg-gray-200 transition-colors">
            Retour
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 fade-in">
    <!-- Colonne Gauche: Infos & Carnet -->
    <div class="lg:col-span-1 space-y-8">
        <!-- Infos Personnelles -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-info-circle text-green-500"></i> Informations Personnelles
            </h3>
            <div class="space-y-3">
                <div class="flex justify-between py-2 border-b border-gray-50">
                    <span class="text-gray-500 text-sm">Né(e) le</span>
                    <span class="font-medium text-gray-800"><?= date('d/m/Y', strtotime($p['datenais'])) ?></span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-50">
                    <span class="text-gray-500 text-sm">Contact</span>
                    <span class="font-medium text-gray-800"><?= htmlspecialchars($p['contact']) ?></span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-50">
                    <span class="text-gray-500 text-sm">Email</span>
                    <span class="font-medium text-gray-800 truncate max-w-[150px]"><?= htmlspecialchars($p['email']) ?></span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-50">
                    <span class="text-gray-500 text-sm">Profession</span>
                    <span class="font-medium text-gray-800"><?= htmlspecialchars($p['profession'] ?? 'N/A') ?></span>
                </div>
            </div>
        </div>

        <!-- Carnet de Santé -->
        <div class="bg-green-600 rounded-2xl shadow-lg p-6 text-white">
            <h3 class="font-bold mb-4 flex items-center gap-2">
                <i class="fas fa-heartbeat"></i> Carnet de Santé
            </h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white/10 p-3 rounded-xl backdrop-blur-sm">
                    <p class="text-xs opacity-70">Groupe Sanguin</p>
                    <p class="text-lg font-bold"><?= $p['groupesanguin'] ?? '--' ?></p>
                </div>
                <div class="bg-white/10 p-3 rounded-xl backdrop-blur-sm">
                    <p class="text-xs opacity-70">Poids / Taille</p>
                    <p class="text-lg font-bold"><?= $p['poids'] ?? '--' ?>kg / <?= $p['taille'] ?? '--' ?>cm</p>
                </div>
                <div class="col-span-2 bg-white/10 p-3 rounded-xl backdrop-blur-sm">
                    <p class="text-xs opacity-70">Allergies</p>
                    <p class="text-sm font-medium"><?= htmlspecialchars($p['allergie'] ?? 'Aucune allergie signalée') ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Colonne Droite: Historique -->
    <div class="lg:col-span-2 space-y-8">
        <!-- Historique Consultations -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b flex justify-between items-center">
                <h3 class="font-bold text-gray-800">Historique des Consultations</h3>
                <span class="text-xs font-bold bg-blue-100 text-blue-600 px-3 py-1 rounded-full uppercase"><?= count($consultations) ?> total</span>
            </div>
            <div class="divide-y divide-gray-50 max-h-[400px] overflow-y-auto">
                <?php if (empty($consultations)): ?>
                    <div class="p-10 text-center text-gray-400 italic">Aucune consultation enregistrée</div>
                <?php else: ?>
                    <?php foreach ($consultations as $c): ?>
                        <div class="p-6 hover:bg-gray-50 transition-colors flex justify-between items-start">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-bold text-gray-800"><?= date('d/m/Y', strtotime($c['date_consultation'])) ?></span>
                                    <span class="text-xs text-gray-400"><?= date('H:i', strtotime($c['date_consultation'])) ?></span>
                                </div>
                                <p class="text-sm font-medium text-blue-600 mb-2">Dr. <?= htmlspecialchars($c['medecin_prenom'] . ' ' . $c['medecin_nom']) ?></p>
                                <p class="text-gray-600 text-sm line-clamp-2"><?= htmlspecialchars($c['motif']) ?></p>
                            </div>
                            <a href="voir_consultation.php?id=<?= $c['id'] ?>" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ordonnances -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b flex justify-between items-center">
                <h3 class="font-bold text-gray-800">Dernières Ordonnances</h3>
                <a href="ordonnances.php?patient_id=<?= $p['id'] ?>" class="text-xs font-bold text-green-600 hover:underline">Voir tout</a>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php if (empty($ordonnances)): ?>
                    <div class="col-span-full text-center text-gray-400 py-4 italic">Aucune ordonnance délivrée</div>
                <?php else: ?>
                    <?php foreach (array_slice($ordonnances, 0, 4) as $o): ?>
                        <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-500"><?= date('d/m/Y', strtotime($o['date_creation'])) ?></p>
                                <p class="text-sm font-bold text-gray-800">Ordonnance #<?= $o['id'] ?></p>
                            </div>
                            <a href="voir_ordonnance.php?id=<?= $o['id'] ?>" class="text-green-600 p-2 hover:bg-green-100 rounded-lg">
                                <i class="fas fa-file-prescription"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once '../components/doctor_layout_bottom.php'; ?>