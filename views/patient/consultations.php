<?php
$page_title = "Mes Consultations - MedConnect";
$header_title = "Mes Consultations";
$header_icon = "fas fa-stethoscope";
include_once '../components/patient_layout_top.php';

// Récupérer les consultations du patient
$query = "SELECT c.*, m.nom as medecin_nom, m.prenom as medecin_prenom 
          FROM consultation c 
          JOIN medecin m ON c.id_medecin = m.id 
          WHERE c.id_patient = ? 
          ORDER BY c.date_consultation DESC";
$stmt = db()->prepare($query);
$stmt->execute([$id_patient]);
$consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .consultation-card {
        transition: all 0.3s ease;
    }
    .consultation-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
</style>

<div class="bg-white rounded-xl shadow-lg p-6 glass-effect fade-in">
    <h1 class="text-2xl font-bold text-[#1e40af] mb-8 flex items-center">
        <i class="fas fa-history mr-3"></i>Historique des consultations
    </h1>
    
    <?php if (empty($consultations)): ?>
        <div class="flex flex-col items-center justify-center py-12 bg-[#EFF6FF] rounded-xl border border-blue-100">
            <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mb-4 shadow-sm">
                <i class="fas fa-stethoscope text-4xl text-[#3b82f6]"></i>
            </div>
            <p class="text-[#1e40af] font-medium text-lg">Aucune consultation enregistrée</p>
            <p class="text-gray-500 mt-2">Vos rapports de consultation apparaîtront ici.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <?php foreach ($consultations as $consultation): ?>
                <div class="consultation-card bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
                    <div class="bg-[#F8FAFC] px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-[#3b82f6] flex items-center justify-center text-white mr-3">
                                <i class="fas fa-user-md"></i>
                            </div>
                            <h3 class="font-bold text-[#1e40af]">Dr. <?php echo htmlspecialchars($consultation['medecin_prenom'] . ' ' . $consultation['medecin_nom']); ?></h3>
                        </div>
                        <span class="px-4 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-bold">
                            <?php echo date('d/m/Y', strtotime($consultation['date_consultation'])); ?>
                        </span>
                    </div>
                    
                    <div class="p-6 space-y-6">
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-[#3b82f6] mb-2">Motif</h4>
                            <p class="text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($consultation['motif'])); ?></p>
                        </div>
                        
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-[#3b82f6] mb-2">Diagnostic</h4>
                            <div class="p-4 bg-red-50 rounded-lg border-l-4 border-red-400">
                                <p class="text-gray-800 font-medium"><?php echo nl2br(htmlspecialchars($consultation['diagnostic'])); ?></p>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-[#3b82f6] mb-2">Traitement</h4>
                            <p class="text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($consultation['traitement'])); ?></p>
                        </div>
                        
                        <?php if ($consultation['recommandations']): ?>
                            <div class="p-4 bg-blue-50 rounded-lg">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-[#3b82f6] mb-2">Recommandations</h4>
                                <p class="text-gray-700 italic"><?php echo nl2br(htmlspecialchars($consultation['recommandations'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($consultation['prochain_rdv']): ?>
                            <div class="pt-4 border-t border-gray-100 flex items-center text-sm">
                                <i class="fas fa-calendar-alt text-[#3b82f6] mr-2"></i>
                                <span class="text-gray-600">Prochain RDV : </span>
                                <span class="ml-2 font-bold text-[#1e40af]"><?php echo date('d/m/Y H:i', strtotime($consultation['prochain_rdv'])); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../components/patient_layout_bottom.php'; ?>