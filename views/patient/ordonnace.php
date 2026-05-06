<?php
$page_title = "Mes Ordonnances - MedConnect";
$header_title = "Mes Ordonnances";
$header_icon = "fas fa-prescription";
include_once '../components/patient_layout_top.php';

// Requête pour récupérer les ordonnances du patient avec infos médecin
$sql = "SELECT o.*, m.nom AS nom_medecin, m.prenom AS prenom_medecin
        FROM ordonnance o
        JOIN medecin m ON o.idmedecin = m.id
        WHERE o.idpatient = :idpatient
        ORDER BY o.date_creation DESC";

$stmt = db()->prepare($sql);
$stmt->execute(['idpatient' => $id_patient]);
$ordonnances = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculer les statistiques des ordonnances
$ordonnances_actives = array_filter($ordonnances, function($ord) {
    return $ord['statut'] === 'active';
});

$ordonnances_a_renouveler = 0;
foreach ($ordonnances as $ord) {
    if ($ord['statut'] === 'à renouveler') {
        $ordonnances_a_renouveler++;
    }
}
?>

<style>
    .prescription-card {
        transition: all 0.3s ease;
    }
    .prescription-card:hover {
        transform: translateY(-5px);
    }
    .btn-primary {
        background-color: #3b82f6;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        transition: all 0.3s ease;
    }
    .btn-primary:hover {
        background-color: #2563eb;
    }
</style>

<!-- Statistiques -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 fade-in">
    <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-[#3b82f6]">Ordonnances actives</p>
                <h3 class="text-2xl font-bold text-[#1e40af]"><?php echo count($ordonnances_actives); ?></h3>
            </div>
            <div class="w-12 h-12 rounded-full bg-[#EFF6FF] flex items-center justify-center">
                <i class="fas fa-prescription text-xl text-[#3b82f6]"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-[#10b981]">Médicaments en cours</p>
                <h3 class="text-2xl font-bold text-[#1e40af]"><?php echo count($ordonnances); ?></h3>
            </div>
            <div class="w-12 h-12 rounded-full bg-[#ECFDF5] flex items-center justify-center">
                <i class="fas fa-pills text-xl text-[#10b981]"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-[#f59e0b]">À renouveler</p>
                <h3 class="text-2xl font-bold text-[#1e40af]"><?php echo $ordonnances_a_renouveler; ?></h3>
            </div>
            <div class="w-12 h-12 rounded-full bg-[#FFFBEB] flex items-center justify-center">
                <i class="fas fa-clock text-xl text-[#f59e0b]"></i>
            </div>
        </div>
    </div>
</div>

<!-- Liste des ordonnances -->
<div class="bg-white rounded-xl shadow-lg p-6 glass-effect fade-in">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold text-[#1e40af] flex items-center">
            <i class="fas fa-prescription mr-2"></i>
            Mes dernières ordonnances
        </h2>
    </div>

    <?php if (empty($ordonnances)): ?>
        <div class="text-center py-8">
            <p class="text-gray-500">Vous n'avez aucune ordonnance pour le moment.</p>
        </div>
    <?php else: ?>
        <?php foreach ($ordonnances as $ordonnance): ?>
            <div class="prescription-card bg-white rounded-lg shadow-md p-6 mb-6 border border-gray-100">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-[#1e40af]">
                            Ordonnance du <?php echo date('d/m/Y', strtotime($ordonnance['date_creation'])); ?>
                        </h3>
                        <p class="text-sm text-gray-600">
                            Dr. <?php echo htmlspecialchars($ordonnance['prenom_medecin'] . ' ' . $ordonnance['nom_medecin']); ?>
                        </p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="px-3 py-1 rounded-full text-sm <?php echo $ordonnance['statut'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                            <?php echo ucfirst($ordonnance['statut']); ?>
                        </span>
                        <a href="telecharger_ordonnance.php?id=<?php echo $ordonnance['id']; ?>" class="btn-primary text-sm">
                            <i class="fas fa-download mr-2"></i>Télécharger
                        </a>
                    </div>
                </div>

                <div class="mt-4">
                    <h4 class="text-md font-semibold text-[#1e40af] mb-2">Médicaments prescrits</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white rounded-lg overflow-hidden">
                            <thead class="bg-[#EFF6FF]">
                                <tr>
                                    <th class="px-4 py-2 text-left text-sm font-medium text-[#1e40af]">Médicament</th>
                                    <th class="px-4 py-2 text-left text-sm font-medium text-[#1e40af]">Posologie</th>
                                    <th class="px-4 py-2 text-left text-sm font-medium text-[#1e40af]">Quantité</th>
                                    <th class="px-4 py-2 text-left text-sm font-medium text-[#1e40af]">Durée</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $medicaments = explode("\n", $ordonnance['medicaments']);
                                $posologies = explode("\n", $ordonnance['posologie']);
                                $quantites = explode("\n", $ordonnance['quantite']);
                                $durees = explode("\n", $ordonnance['duree_medicament']);
                                $max = max(count($medicaments), count($posologies), count($quantites), count($durees));
                                for ($i = 0; $i < $max; $i++): ?>
                                    <tr class="border-b border-gray-100">
                                        <td class="px-4 py-2"><?php echo isset($medicaments[$i]) ? htmlspecialchars($medicaments[$i]) : ''; ?></td>
                                        <td class="px-4 py-2"><?php echo isset($posologies[$i]) ? htmlspecialchars($posologies[$i]) : ''; ?></td>
                                        <td class="px-4 py-2"><?php echo isset($quantites[$i]) ? htmlspecialchars($quantites[$i]) : ''; ?></td>
                                        <td class="px-4 py-2"><?php echo isset($durees[$i]) ? htmlspecialchars($durees[$i]) : ''; ?></td>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if (!empty($ordonnance['instructions'])): ?>
                    <div class="mt-4">
                        <h4 class="text-md font-semibold text-[#1e40af] mb-2">Instructions supplémentaires</h4>
                        <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($ordonnance['instructions'])); ?></p>
                    </div>
                <?php endif; ?>

                <div class="mt-4 text-sm text-gray-600">
                    <p>Date de validité : <?php echo date('d/m/Y', strtotime($ordonnance['date_validite'])); ?></p>
                    <?php if ($ordonnance['renouvellement']): ?>
                        <p>Renouvellement possible : <?php echo $ordonnance['nombre_renouvellements']; ?> fois</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include_once '../components/patient_layout_bottom.php'; ?>
