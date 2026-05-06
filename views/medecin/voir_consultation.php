<?php
$page_title = "Détails Consultation - MedConnect";
$header_title = "Détails de la Consultation";
$header_icon = "fas fa-file-medical";
include_once '../components/doctor_layout_top.php';

$consultation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$consultation_id) { echo "<script>window.location.href='consultations.php';</script>"; exit; }

$stmt = $db->prepare("SELECT c.*, p.nom as patient_nom, p.prenom as patient_prenom FROM consultation c JOIN patient p ON c.id_patient = p.id WHERE c.id = ? AND c.id_medecin = ?");
$stmt->execute([$consultation_id, $user_id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$c) { echo "<script>window.location.href='consultations.php';</script>"; exit; }
?>

<div class="max-w-4xl mx-auto fade-in">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Consultation du <?= date('d/m/Y', strtotime($c['date_consultation'])) ?></h2>
            <p class="text-gray-500">Patient: <?= htmlspecialchars($c['patient_prenom'] . ' ' . $c['patient_nom']) ?></p>
        </div>
        <div class="flex gap-3">
            <a href="imprimer_consultation.php?id=<?= $c['id'] ?>" class="p-2 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-100 transition-colors" title="Imprimer">
                <i class="fas fa-print"></i>
            </a>
            <a href="modifier_consultation.php?id=<?= $c['id'] ?>" class="p-2 bg-amber-50 text-amber-600 rounded-xl hover:bg-amber-100 transition-colors" title="Modifier">
                <i class="fas fa-edit"></i>
            </a>
            <a href="consultations.php" class="bg-gray-100 text-gray-600 px-6 py-2 rounded-xl hover:bg-gray-200 transition-colors">
                Retour
            </a>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-8 space-y-8">
            <!-- Motif -->
            <section>
                <h3 class="text-sm font-bold text-green-600 uppercase tracking-wider mb-3">Motif de consultation</h3>
                <div class="p-4 bg-gray-50 rounded-xl text-gray-800 leading-relaxed">
                    <?= nl2br(htmlspecialchars($c['motif'])) ?>
                </div>
            </section>

            <!-- Antécédents -->
            <?php if ($c['antecedents']): ?>
            <section>
                <h3 class="text-sm font-bold text-blue-600 uppercase tracking-wider mb-3">Antécédents</h3>
                <div class="p-4 bg-gray-50 rounded-xl text-gray-800 leading-relaxed">
                    <?= nl2br(htmlspecialchars($c['antecedents'])) ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Examen Clinique -->
            <section>
                <h3 class="text-sm font-bold text-purple-600 uppercase tracking-wider mb-3">Examen Clinique</h3>
                <div class="p-4 bg-gray-50 rounded-xl text-gray-800 leading-relaxed">
                    <?= nl2br(htmlspecialchars($c['examen_clinique'])) ?>
                </div>
            </section>

            <!-- Diagnostic -->
            <section>
                <h3 class="text-sm font-bold text-red-600 uppercase tracking-wider mb-3">Diagnostic</h3>
                <div class="p-4 bg-red-50 rounded-xl text-red-900 font-bold border border-red-100">
                    <?= htmlspecialchars($c['diagnostic']) ?>
                </div>
            </section>

            <!-- Traitement & Recommandations -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <?php if ($c['traitement']): ?>
                <section>
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3">Traitement préconisé</h3>
                    <div class="p-4 bg-gray-50 rounded-xl text-gray-800 text-sm">
                        <?= nl2br(htmlspecialchars($c['traitement'])) ?>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ($c['recommandations']): ?>
                <section>
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3">Recommandations</h3>
                    <div class="p-4 bg-gray-50 rounded-xl text-gray-800 text-sm">
                        <?= nl2br(htmlspecialchars($c['recommandations'])) ?>
                    </div>
                </section>
                <?php endif; ?>
            </div>

            <!-- Prochain RDV -->
            <?php if ($c['prochain_rdv']): ?>
            <div class="pt-6 border-t flex items-center justify-between">
                <span class="text-gray-500 font-medium">Prochain rendez-vous suggéré :</span>
                <span class="font-bold text-green-700"><?= date('d/m/Y à H:i', strtotime($c['prochain_rdv'])) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once '../components/doctor_layout_bottom.php'; ?>