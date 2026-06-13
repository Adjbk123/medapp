<?php
$page_title = "Mes Patients - MedConnect";
$header_title = "Mes Patients";
$header_icon = "fas fa-users";
include_once '../components/doctor_layout_top.php';

try {
    $stmt = $db->prepare("SELECT DISTINCT p.* FROM patient p JOIN rendezvous r ON p.id = r.idpatient WHERE r.idmedecin = ? ORDER BY p.nom, p.prenom");
    $stmt->execute([$user_id]);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = "Une erreur est survenue.";
}
?>

<div class="flex justify-between items-center mb-8 fade-in">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Gestion des patients</h2>
        <p class="text-gray-500">Retrouvez la liste de tous vos patients suivis.</p>
    </div>
    <div class="flex gap-4">
        <div class="relative">
            <input type="text" placeholder="Rechercher..." class="pl-10 pr-4 py-2 border rounded-xl focus:ring-2 focus:ring-green-500 outline-none">
            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
        </div>
        <a href="register_patient.php" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-xl flex items-center gap-2 shadow-lg transition-transform hover:scale-105">
            <i class="fas fa-plus"></i>Nouveau Patient
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 fade-in">
    <?php if (empty($patients)): ?>
        <div class="col-span-full bg-white p-12 rounded-2xl shadow-sm border border-dashed border-gray-300 text-center">
            <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-user-plus text-3xl text-gray-300"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-700">Aucun patient</h3>
            <p class="text-gray-500 mt-2">Vous n'avez pas encore de patients affectés.</p>
        </div>
    <?php else: ?>
        <?php foreach ($patients as $patient): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-16 h-16 rounded-2xl bg-green-50 flex items-center justify-center text-green-600 font-bold text-xl">
                            <?= strtoupper(substr($patient['prenom'], 0, 1) . substr($patient['nom'], 0, 1)) ?>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']) ?></h3>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($patient['email']) ?></p>
                        </div>
                    </div>
                    
                    <div class="space-y-3 mb-6">
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-phone w-5 text-green-500"></i>
                            <span><?= htmlspecialchars($patient['contact']) ?></span>
                        </div>
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-birthday-cake w-5 text-green-500"></i>
                            <span><?= date('d/m/Y', strtotime($patient['datenais'])) ?></span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3 pt-4 border-t border-gray-50">
                        <a href="patient_details.php?id=<?= $patient['id'] ?>" class="py-2 text-center text-sm font-bold text-green-600 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                            Voir Dossier
                        </a>
                        <a href="nouvelle_consultation.php?patient_id=<?= $patient['id'] ?>" class="py-2 text-center text-sm font-bold text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                            Consulter
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include_once '../components/doctor_layout_bottom.php'; ?>