<?php
$page_title = "Tableau de bord Administrateur - MedConnect";
$header_title = "Tableau de bord";
$header_icon = "fas fa-chart-line";
include_once '../components/admin_layout_top.php';

// Statistiques
$total_users = $db->query("SELECT (SELECT COUNT(*) FROM patient) + (SELECT COUNT(*) FROM medecin) + (SELECT COUNT(*) FROM admin)")->fetchColumn();
$total_doctors = $db->query("SELECT COUNT(*) FROM medecin WHERE verification_status = 'verified'")->fetchColumn();
$total_patients = $db->query("SELECT COUNT(*) FROM patient")->fetchColumn();
$total_appointments = $db->query("SELECT COUNT(*) FROM rendezvous")->fetchColumn();

// Activités récentes
$recent_activities = $db->query("
    (SELECT 'medecin' as type, CONCAT(prenom, ' ', nom) as name, 'Nouveau médecin inscrit' as action, created_at as date FROM medecin WHERE verification_status = 'pending')
    UNION ALL
    (SELECT 'patient' as type, CONCAT(prenom, ' ', nom) as name, 'Nouveau patient inscrit' as action, created_at as date FROM patient)
    ORDER BY date DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

function timeAgo($date) {
    $diff = (new DateTime())->diff(new DateTime($date));
    if ($diff->d > 0) return "Il y a " . $diff->d . "j";
    if ($diff->h > 0) return "Il y a " . $diff->h . "h";
    return "Il y a " . $diff->i . "m";
}
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 fade-in">
    <!-- Stat Card -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4 hover:shadow-md transition-shadow">
        <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl">
            <i class="fas fa-users"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-slate-500">Utilisateurs</p>
            <p class="text-2xl font-bold text-slate-900"><?= number_format($total_users) ?></p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4 hover:shadow-md transition-shadow">
        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
            <i class="fas fa-user-md"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-slate-500">Médecins</p>
            <p class="text-2xl font-bold text-slate-900"><?= number_format($total_doctors) ?></p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4 hover:shadow-md transition-shadow">
        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
            <i class="fas fa-procedures"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-slate-500">Patients</p>
            <p class="text-2xl font-bold text-slate-900"><?= number_format($total_patients) ?></p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4 hover:shadow-md transition-shadow">
        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
            <i class="fas fa-calendar-check"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-slate-500">Rendez-vous</p>
            <p class="text-2xl font-bold text-slate-900"><?= number_format($total_appointments) ?></p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 fade-in">
    <!-- Chart Section -->
    <div class="lg:col-span-2 bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-slate-800">Croissance Utilisateurs</h3>
            <select class="bg-slate-50 border-none text-xs font-bold text-slate-500 rounded-lg px-3 py-2 outline-none ring-0">
                <option>7 derniers jours</option>
                <option>30 derniers jours</option>
            </select>
        </div>
        <div class="h-[300px] flex items-center justify-center bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200">
            <p class="text-slate-400 font-medium italic">Graphique de tendance (Chart.js)</p>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
        <h3 class="text-lg font-bold text-slate-800 mb-6">Activités Récentes</h3>
        <div class="space-y-6">
            <?php foreach ($recent_activities as $activity): ?>
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center <?= $activity['type'] == 'medecin' ? 'bg-blue-100 text-blue-600' : 'bg-emerald-100 text-emerald-600' ?>">
                        <i class="fas <?= $activity['type'] == 'medecin' ? 'fa-user-md' : 'fa-user' ?> text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-slate-800 truncate"><?= htmlspecialchars($activity['name']) ?></p>
                        <p class="text-xs text-slate-500"><?= $activity['action'] ?></p>
                        <p class="text-[10px] text-slate-400 mt-1"><?= timeAgo($activity['date']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <a href="#" class="block text-center mt-8 text-sm font-bold text-admin-600 hover:text-admin-800 transition-colors">Voir tout le journal</a>
    </div>
</div>

<div class="mt-8 bg-white p-8 rounded-3xl shadow-sm border border-slate-100 fade-in">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-bold text-slate-800">Alertes de Vérification</h3>
        <span class="px-3 py-1 bg-red-100 text-red-600 rounded-full text-xs font-bold">Action requise</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="text-xs text-slate-400 font-bold uppercase tracking-wider border-b border-slate-50">
                <tr>
                    <th class="pb-3">Médecin</th>
                    <th class="pb-3">Spécialité</th>
                    <th class="pb-3">Date d'inscription</th>
                    <th class="pb-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php
                $pending = $db->query("SELECT m.*, s.nomspecialite FROM medecin m LEFT JOIN specialite s ON m.idspecialite = s.id WHERE verification_status = 'pending' LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($pending as $doc):
                ?>
                <tr>
                    <td class="py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center font-bold text-slate-500 text-xs">
                                <?= strtoupper(substr($doc['nom'], 0, 1)) ?>
                            </div>
                            <span class="font-bold text-slate-800">Dr. <?= htmlspecialchars($doc['prenom'] . ' ' . $doc['nom']) ?></span>
                        </div>
                    </td>
                    <td class="py-4 text-sm text-slate-600"><?= htmlspecialchars($doc['nomspecialite'] ?? 'Généraliste') ?></td>
                    <td class="py-4 text-sm text-slate-500"><?= date('d/m/Y', strtotime($doc['created_at'])) ?></td>
                    <td class="py-4 text-right">
                        <a href="verify_doctors.php?id=<?= $doc['id'] ?>" class="text-xs font-bold bg-admin-600 text-white px-4 py-2 rounded-lg hover:bg-admin-700 transition-colors shadow-lg shadow-admin-500/20">Vérifier</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once '../components/admin_layout_bottom.php'; ?>