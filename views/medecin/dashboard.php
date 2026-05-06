<?php
$page_title = "Tableau de bord - MedConnect";
$header_title = "Tableau de bord";
$header_icon = "fas fa-home";
include_once '../components/doctor_layout_top.php';
require_once '../../models/Dashboard.php';

$dashboard = new Dashboard($db, $user_id);

// Données du dashboard
$rdv_aujourdhui = $dashboard->getRendezVousAujourdhui();
$patients_actifs = $dashboard->getPatientsActifs();
$consultations_jour = $dashboard->getConsultationsDuJour();
$messages_non_lus = $dashboard->getMessagesNonLus();
$rdv_du_jour = $dashboard->getRendezVousDuJour();
$rappels = $dashboard->getRappelsImportants();

// Statistiques pour le graphique
$days_in_month = date('t');
$current_month = date('m');
$current_year = date('Y');
$stmt = $db->prepare("
    SELECT DATE(date_consultation) as jour, COUNT(*) as total
    FROM consultation
    WHERE id_medecin = ? AND MONTH(date_consultation) = ? AND YEAR(date_consultation) = ?
    GROUP BY jour
    ORDER BY jour
");
$stmt->execute([$user_id, $current_month, $current_year]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$consultations_par_jour = array_fill(0, $days_in_month, 0);
$labels = [];
for ($i = 1; $i <= $days_in_month; $i++) {
    $labels[] = $i;
}
foreach ($rows as $row) {
    $day = (int)date('d', strtotime($row['jour']));
    $consultations_par_jour[$day-1] = (int)$row['total'];
}
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 fade-in">
    <!-- Stat Cards -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 transition-all hover:shadow-md">
        <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
            <i class="fas fa-calendar-check text-xl"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium">RDV Aujourd'hui</p>
            <h3 class="text-2xl font-bold text-gray-800"><?= $rdv_aujourdhui ?></h3>
        </div>
    </div>
    
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 transition-all hover:shadow-md">
        <div class="w-12 h-12 rounded-xl bg-green-50 flex items-center justify-center text-green-600">
            <i class="fas fa-user-friends text-xl"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium">Patients Actifs</p>
            <h3 class="text-2xl font-bold text-gray-800"><?= $patients_actifs ?></h3>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 transition-all hover:shadow-md">
        <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center text-purple-600">
            <i class="fas fa-stethoscope text-xl"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium">Consultations (Mois)</p>
            <h3 class="text-2xl font-bold text-gray-800"><?= count($rows) ?></h3>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 transition-all hover:shadow-md">
        <div class="w-12 h-12 rounded-xl bg-yellow-50 flex items-center justify-center text-yellow-600">
            <i class="fas fa-envelope text-xl"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium">Messages</p>
            <h3 class="text-2xl font-bold text-gray-800"><?= $messages_non_lus ?></h3>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 fade-in">
    <!-- Agenda du jour -->
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b flex justify-between items-center">
            <h3 class="font-bold text-gray-800 flex items-center">
                <i class="fas fa-clock mr-2 text-green-600"></i>Agenda du jour
            </h3>
            <a href="rdv.php" class="text-sm text-green-600 hover:underline font-medium">Voir tout</a>
        </div>
        <div class="p-6">
            <?php if (empty($rdv_du_jour)): ?>
                <div class="text-center py-12">
                    <img src="../../assets/img/empty-schedule.svg" class="w-32 mx-auto mb-4 opacity-20">
                    <p class="text-gray-400">Aucun rendez-vous prévu aujourd'hui</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($rdv_du_jour as $rdv): ?>
                        <div class="flex items-center p-4 rounded-xl border border-gray-50 hover:bg-gray-50 transition-colors">
                            <div class="text-sm font-bold text-gray-500 w-16"><?= date('H:i', strtotime($rdv['dateheure'])) ?></div>
                            <div class="flex-1">
                                <p class="font-bold text-gray-800"><?= htmlspecialchars($rdv['patient_prenom'] . ' ' . $rdv['patient_nom']) ?></p>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($rdv['motif']) ?></p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?= $rdv['statut'] === 'confirmé' ? 'bg-green-100 text-green-600' : 'bg-yellow-100 text-yellow-600' ?>">
                                <?= $rdv['statut'] ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Rappels et Alertes -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-bold text-gray-800 mb-6 flex items-center">
            <i class="fas fa-bell mr-2 text-yellow-500"></i>Rappels importants
        </h3>
        <div class="space-y-4">
            <?php if (empty($rappels)): ?>
                <p class="text-sm text-gray-400 text-center py-8">Aucun rappel pour le moment</p>
            <?php else: ?>
                <?php foreach ($rappels as $rappel): ?>
                    <div class="p-4 rounded-xl bg-yellow-50 border-l-4 border-yellow-400">
                        <p class="text-sm font-bold text-yellow-800"><?= htmlspecialchars($rappel['titre']) ?></p>
                        <p class="text-xs text-yellow-600 mt-1"><?= htmlspecialchars($rappel['description']) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="mt-8 pt-8 border-t">
            <h4 class="font-bold text-gray-800 mb-4 text-sm uppercase tracking-wider">Actions rapides</h4>
            <div class="grid grid-cols-2 gap-3">
                <a href="nouvelle_consultation.php" class="p-3 rounded-xl bg-green-50 text-green-600 text-center hover:bg-green-100 transition-colors">
                    <i class="fas fa-plus mb-2 block"></i>
                    <span class="text-xs font-bold">Consultation</span>
                </a>
                <a href="nouvelle_ordonnance.php" class="p-3 rounded-xl bg-blue-50 text-blue-600 text-center hover:bg-blue-100 transition-colors">
                    <i class="fas fa-file-prescription mb-2 block"></i>
                    <span class="text-xs font-bold">Ordonnance</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Si besoin d'un graphique à l'avenir, les données sont prêtes
</script>

<?php include_once '../components/doctor_layout_bottom.php'; ?>