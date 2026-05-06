<?php
$page_title = "Tableau de bord Patient - MedConnect";
$header_title = "Mon Tableau de bord";
$header_icon = "fas fa-home";
include_once '../components/patient_layout_top.php';

// Récupérer le prochain rendez-vous
$stmt = db()->prepare("
    SELECT r.dateheure, m.nom as medecin_nom, m.prenom as medecin_prenom, r.statut
    FROM rendezvous r
    JOIN medecin m ON r.idmedecin = m.id
    WHERE r.idpatient = ? AND DATE(r.dateheure) >= CURDATE()
    ORDER BY r.dateheure ASC
    LIMIT 1
");
$stmt->execute([$id_patient]);
$prochain_rdv = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer le médecin traitant
$stmt = db()->prepare("
    SELECT m.nom, m.prenom
    FROM medecin m
    JOIN patient p ON p.id_medecin = m.id
    WHERE p.id = ?
");
$stmt->execute([$id_patient]);
$medecin = $stmt->fetch(PDO::FETCH_ASSOC);

// Compter les ordonnances
$stmt = db()->prepare("
    SELECT COUNT(*) as total
    FROM ordonnance
    WHERE idpatient = ?
");
$stmt->execute([$id_patient]);
$ordonnances = $stmt->fetch(PDO::FETCH_ASSOC);

// Compter les messages non lus
$stmt = db()->prepare("
    SELECT COUNT(*) as total
    FROM messages
    WHERE receiver_id = ? AND lu = 0
");
$stmt->execute([$id_patient]);
$messages = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer les rendez-vous récents
$stmt = db()->prepare("
    SELECT r.dateheure, m.nom as medecin_nom, m.prenom as medecin_prenom, r.statut, r.id
    FROM rendezvous r
    JOIN medecin m ON r.idmedecin = m.id
    WHERE r.idpatient = ? AND DATE(r.dateheure) >= CURDATE()
    ORDER BY r.dateheure ASC
    LIMIT 3
");
$stmt->execute([$id_patient]);
$rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les rappels de médicaments
$stmt = db()->prepare("
    SELECT o.medicaments, o.posologie, o.date_validite
    FROM ordonnance o
    WHERE o.idpatient = ? AND o.date_validite >= CURDATE()
    ORDER BY o.date_validite ASC
    LIMIT 3
");
$stmt->execute([$id_patient]);
$medicaments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les documents médicaux
$stmt = db()->prepare("
    SELECT o.id, o.date_creation, o.medicaments, m.nom as medecin_nom, m.prenom as medecin_prenom
    FROM ordonnance o
    JOIN medecin m ON o.idmedecin = m.id
    WHERE o.idpatient = ?
    ORDER BY o.date_creation DESC
    LIMIT 2
");
$stmt->execute([$id_patient]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les rappels importants
$stmt = db()->prepare("
    SELECT o.id, o.date_validite, o.renouvellement, o.nombre_renouvellements
    FROM ordonnance o
    WHERE o.idpatient = ? 
    AND o.date_validite >= CURDATE()
    AND (o.renouvellement = 1 OR o.date_validite <= DATE_ADD(CURDATE(), INTERVAL 7 DAY))
    ORDER BY o.date_validite ASC
    LIMIT 3
");
$stmt->execute([$id_patient]);
$rappels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les derniers messages
$stmt = db()->prepare("
    SELECT m.id, m.contenu, m.date_envoi, med.nom as medecin_nom, med.prenom as medecin_prenom
    FROM messages m
    JOIN medecin med ON m.sender_id = med.id
    WHERE m.receiver_id = ? AND m.sender_type = 'medecin'
    ORDER BY m.date_envoi DESC
    LIMIT 1
");
$stmt->execute([$id_patient]);
$dernier_message = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<style>
    .stat-card {
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    .rdv-card {
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }
    .rdv-card:hover {
        transform: translateX(5px);
        border-left-color: #3b82f6;
    }
    .reminder-card {
        transition: all 0.3s ease;
        border-bottom: 2px solid transparent;
    }
    .reminder-card:hover {
        transform: scale(1.02);
        border-bottom-color: #3b82f6;
    }
    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    .status-confirmed {
        background-color: #DCFCE7;
        color: #10b981;
    }
    .status-pending {
        background-color: #FEF3C7;
        color: #f59e0b;
    }
    .status-cancelled {
        background-color: #FEE2E2;
        color: #ef4444;
    }
    .pulse {
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
        100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
    }
</style>

<!-- Bienvenue -->
<div class="mb-8 fade-in">
    <h2 class="text-2xl font-bold text-[#1e40af] mb-2">Bonjour, <?php echo htmlspecialchars($prenom); ?> 👋</h2>
    <p class="text-gray-600">Voici un aperçu de votre santé et de vos prochains rendez-vous</p>
</div>

<!-- Statistiques rapides -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 fade-in">
    <div class="stat-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-[#3b82f6] glass-effect">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-[#3b82f6] font-medium">Prochain RDV</p>
                <?php if ($prochain_rdv): ?>
                    <h3 class="text-xl font-bold text-[#1e40af] mt-1">
                        <?php echo date('d M Y', strtotime($prochain_rdv['dateheure'])); ?>
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">
                        <?php echo date('H:i', strtotime($prochain_rdv['dateheure'])); ?> - 
                        Dr. <?php echo htmlspecialchars($prochain_rdv['medecin_prenom'] . ' ' . $prochain_rdv['medecin_nom']); ?>
                    </p>
                <?php else: ?>
                    <h3 class="text-xl font-bold text-[#1e40af] mt-1">Aucun RDV</h3>
                    <p class="text-xs text-gray-500 mt-1">Prenez rendez-vous</p>
                <?php endif; ?>
            </div>
            <div class="w-14 h-14 rounded-full bg-[#EFF6FF] flex items-center justify-center">
                <i class="fas fa-calendar-check text-xl text-[#3b82f6]"></i>
            </div>
        </div>
        <div class="mt-4 text-right">
            <a href="rdv.php" class="text-xs text-[#3b82f6] hover:underline">Voir tous les RDV →</a>
        </div>
    </div>
    
    <div class="stat-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-[#10b981] glass-effect">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-[#10b981] font-medium">Médecin traitant</p>
                <?php if ($medecin): ?>
                    <h3 class="text-xl font-bold text-[#1e40af] mt-1">
                        Dr. <?php echo htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']); ?>
                    </h3>
                <?php else: ?>
                    <h3 class="text-xl font-bold text-[#1e40af] mt-1">Non assigné</h3>
                    <p class="text-xs text-gray-500 mt-1">Contactez votre assurance</p>
                <?php endif; ?>
            </div>
            <div class="w-14 h-14 rounded-full bg-[#ECFDF5] flex items-center justify-center">
                <i class="fas fa-user-md text-xl text-[#10b981]"></i>
            </div>
        </div>
        <div class="mt-4 text-right">
            <a href="profile_patient.php" class="text-xs text-[#10b981] hover:underline">Voir mon profil →</a>
        </div>
    </div>
    
    <div class="stat-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-[#f59e0b] glass-effect">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-[#f59e0b] font-medium">Ordonnances</p>
                <h3 class="text-xl font-bold text-[#1e40af] mt-1"><?php echo $ordonnances['total']; ?></h3>
                <p class="text-xs text-gray-500 mt-1">
                    <?php echo $ordonnances['total'] > 0 ? "Ordonnances actives" : "Aucune ordonnance"; ?>
                </p>
            </div>
            <div class="w-14 h-14 rounded-full bg-[#FFFBEB] flex items-center justify-center">
                <i class="fas fa-prescription text-xl text-[#f59e0b]"></i>
            </div>
        </div>
        <div class="mt-4 text-right">
            <a href="ordonnace.php" class="text-xs text-[#f59e0b] hover:underline">Voir mes ordonnances →</a>
        </div>
    </div>
    
    <div class="stat-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-[#8b5cf6] glass-effect">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-[#8b5cf6] font-medium">Messages</p>
                <h3 class="text-xl font-bold text-[#1e40af] mt-1"><?php echo $messages['total']; ?></h3>
                <p class="text-xs text-gray-500 mt-1">
                    <?php echo $messages['total'] > 0 ? $messages['total'] . " message(s) non lu(s)" : "Aucun message non lu"; ?>
                </p>
            </div>
            <div class="w-14 h-14 rounded-full bg-[#F5F3FF] flex items-center justify-center">
                <i class="fas fa-envelope text-xl text-[#8b5cf6]"></i>
            </div>
        </div>
        <div class="mt-4 text-right">
            <a href="messages.php" class="text-xs text-[#8b5cf6] hover:underline">Voir mes messages →</a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 fade-in">
    <!-- Section Rendez-vous -->
    <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-[#1e40af]">
                <i class="fas fa-calendar-alt mr-2"></i>Mes Rendez-vous
            </h2>
            <a href="rdv.php" class="bg-[#3b82f6] hover:bg-[#2563eb] text-white px-4 py-2 rounded-lg transition-colors duration-300 flex items-center">
                <i class="fas fa-plus-circle mr-2"></i>Nouveau RDV
            </a>
        </div>
        
        <div class="space-y-4">
            <?php if (empty($rdvs)): ?>
                <div class="flex flex-col items-center justify-center py-8 bg-[#F9FAFB] rounded-lg">
                    <div class="w-16 h-16 rounded-full bg-[#EFF6FF] flex items-center justify-center mb-3">
                        <i class="fas fa-calendar-alt text-2xl text-[#3b82f6]"></i>
                    </div>
                    <p class="text-gray-500 font-medium">Aucun rendez-vous à venir</p>
                </div>
            <?php else: ?>
                <?php foreach ($rdvs as $rdv): ?>
                    <div class="rdv-card p-4 bg-[#F9FAFB] rounded-lg hover:bg-[#EFF6FF] transition-all duration-300">
                        <div class="flex items-start">
                            <div class="mr-4 bg-[#EFF6FF] rounded-lg p-2 text-center min-w-[60px]">
                                <p class="text-lg font-bold text-[#3b82f6]"><?php echo date('d', strtotime($rdv['dateheure'])); ?></p>
                                <p class="text-xs text-[#3b82f6]"><?php echo date('M', strtotime($rdv['dateheure'])); ?></p>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-medium text-[#1e40af]">Dr. <?php echo htmlspecialchars($rdv['medecin_prenom'] . ' ' . $rdv['medecin_nom']); ?></p>
                                        <p class="text-sm text-[#3b82f6] flex items-center mt-1">
                                            <i class="fas fa-clock mr-2 text-xs"></i>
                                            <?php echo date('H:i', strtotime($rdv['dateheure'])); ?>
                                        </p>
                                    </div>
                                    <span class="status-badge <?php 
                                        echo $rdv['statut'] === 'confirmé' ? 'status-confirmed' : 
                                            ($rdv['statut'] === 'en attente' ? 'status-pending' : 
                                            'status-cancelled'); 
                                    ?>">
                                        <?php echo ucfirst($rdv['statut']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Section Médicaments -->
    <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-[#1e40af]">
                <i class="fas fa-pills mr-2"></i>Mes Médicaments
            </h2>
        </div>
        <div class="space-y-4">
            <?php if (empty($medicaments)): ?>
                <div class="flex flex-col items-center justify-center py-8 bg-[#F9FAFB] rounded-lg">
                    <div class="w-16 h-16 rounded-full bg-[#ECFDF5] flex items-center justify-center mb-3">
                        <i class="fas fa-pills text-2xl text-[#10b981]"></i>
                    </div>
                    <p class="text-gray-500 font-medium">Aucun médicament actif</p>
                </div>
            <?php else: ?>
                <?php foreach ($medicaments as $med): ?>
                    <div class="reminder-card p-4 bg-[#F9FAFB] rounded-lg">
                        <p class="font-medium text-[#1e40af]"><?php echo htmlspecialchars($med['medicaments']); ?></p>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($med['posologie']); ?></p>
                        <p class="text-xs text-gray-500 mt-2">Expire le <?php echo date('d/m/Y', strtotime($med['date_validite'])); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Graphique -->
<div class="mt-8 bg-white rounded-xl shadow-lg p-6 glass-effect fade-in">
    <h2 class="text-xl font-semibold text-[#1e40af] mb-6">
        <i class="fas fa-chart-line mr-2"></i>Suivi de santé
    </h2>
    <div class="w-full h-64">
        <canvas id="healthChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('healthChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin'],
                datasets: [{
                    label: 'Consultations',
                    data: [2, 1, 3, 1, 2, 1],
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
});
</script>

<?php include_once '../components/patient_layout_bottom.php'; ?>
