<?php
$page_title = "Mes Ordonnances - MedConnect";
$header_title = "Gestion des Ordonnances";
$header_icon = "fas fa-prescription";
include_once '../components/doctor_layout_top.php';

try {
    $stmt = $db->prepare("SELECT o.*, p.nom as patient_nom, p.prenom as patient_prenom FROM ordonnance o JOIN patient p ON o.idpatient = p.id WHERE o.idmedecin = ? ORDER BY o.date_creation DESC");
    $stmt->execute([$user_id]);
    $ordonnances = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $ordonnances = [];
}
?>

<div class="flex justify-between items-center mb-8 fade-in">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Historique des Ordonnances</h2>
        <p class="text-gray-500">Retrouvez toutes les prescriptions délivrées à vos patients.</p>
    </div>
    <a href="nouvelle_ordonnance.php" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-xl flex items-center gap-2 shadow-lg transition-transform hover:scale-105">
        <i class="fas fa-plus"></i>Nouvelle Ordonnance
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 fade-in">
    <?php if (empty($ordonnances)): ?>
        <div class="lg:col-span-2 bg-white p-12 rounded-2xl shadow-sm border border-dashed border-gray-300 text-center">
            <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300">
                <i class="fas fa-file-prescription text-3xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-700">Aucune ordonnance</h3>
            <p class="text-gray-500 mt-2">Vous n'avez pas encore créé d'ordonnance.</p>
        </div>
    <?php else: ?>
        <?php foreach ($ordonnances as $o): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start mb-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center text-green-600">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800"><?= htmlspecialchars($o['patient_prenom'] . ' ' . $o['patient_nom']) ?></h3>
                            <p class="text-xs text-gray-500">Créée le <?= date('d/m/Y', strtotime($o['date_creation'])) ?></p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="voir_ordonnance.php?id=<?= $o['id'] ?>" class="p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors" title="Voir">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="modifier_ordonnance.php?id=<?= $o['id'] ?>" class="p-2 bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-100 transition-colors" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="telecharger_ordonnance.php?id=<?= $o['id'] ?>" class="p-2 bg-green-50 text-green-600 rounded-lg hover:bg-green-100 transition-colors" title="Télécharger">
                            <i class="fas fa-download"></i>
                        </a>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div class="p-4 bg-gray-50 rounded-xl">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 flex items-center gap-2">
                            <i class="fas fa-pills text-green-500"></i>Médicaments
                        </h4>
                        <p class="text-sm text-gray-700 line-clamp-2">
                            <?= htmlspecialchars($o['medicaments'] ?? 'Aucun médicament') ?>
                        </p>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 flex items-center gap-2">
                            <i class="fas fa-info-circle text-blue-500"></i>Instructions
                        </h4>
                        <p class="text-sm text-gray-700 line-clamp-2">
                            <?= htmlspecialchars($o['instructions'] ?? 'Aucune instruction') ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include_once '../components/doctor_layout_bottom.php'; ?>