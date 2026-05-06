<?php
$page_title = "Mon Agenda - MedConnect";
$header_title = "Agenda des Rendez-vous";
$header_icon = "fas fa-calendar-alt";
include_once '../components/doctor_layout_top.php';

// Gestion de la mise à jour du statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    try {
        $rdv_id = filter_input(INPUT_POST, 'rdv_id', FILTER_VALIDATE_INT);
        $statut = htmlspecialchars($_POST['statut'] ?? '', ENT_QUOTES, 'UTF-8');
        
        $db->beginTransaction();
        $updateQuery = "UPDATE rendezvous SET statut = :statut WHERE id = :id AND idmedecin = :medecin_id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute(['statut' => $statut, 'id' => $rdv_id, 'medecin_id' => $user_id]);
        
        if ($statut === 'confirmé') {
            $stmt = $db->prepare("SELECT idpatient FROM rendezvous WHERE id = ?");
            $stmt->execute([$rdv_id]);
            $rdv_data = $stmt->fetch();
            $stmt = $db->prepare("UPDATE patient SET id_medecin = ? WHERE id = ?");
            $stmt->execute([$user_id, $rdv_data['idpatient']]);
        }
        $db->commit();
        $success = "Statut mis à jour.";
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        $error = "Erreur : " . $e->getMessage();
    }
}

// Récupérer les rendez-vous
$stmt = $db->prepare("SELECT r.*, p.nom as patient_nom, p.prenom as patient_prenom FROM rendezvous r JOIN patient p ON r.idpatient = p.id WHERE r.idmedecin = ? ORDER BY r.dateheure ASC");
$stmt->execute([$user_id]);
$rendezvous = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden fade-in">
    <div class="p-6 border-b flex justify-between items-center">
        <h3 class="text-xl font-bold text-gray-800">Liste des rendez-vous</h3>
        <div class="flex gap-2">
            <span class="flex items-center text-xs text-gray-500"><i class="fas fa-circle text-yellow-400 mr-1"></i>En attente</span>
            <span class="flex items-center text-xs text-gray-500"><i class="fas fa-circle text-green-500 mr-1"></i>Confirmé</span>
            <span class="flex items-center text-xs text-gray-500"><i class="fas fa-circle text-red-500 mr-1"></i>Annulé</span>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-4 font-bold">Date & Heure</th>
                    <th class="px-6 py-4 font-bold">Patient</th>
                    <th class="px-6 py-4 font-bold">Motif</th>
                    <th class="px-6 py-4 font-bold">Statut</th>
                    <th class="px-6 py-4 font-bold text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($rendezvous as $rdv): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-800"><?= date('d/m/Y', strtotime($rdv['dateheure'])) ?></div>
                            <div class="text-xs text-gray-500"><?= date('H:i', strtotime($rdv['dateheure'])) ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-800"><?= htmlspecialchars($rdv['patient_prenom'] . ' ' . $rdv['patient_nom']) ?></div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($rdv['motif']) ?></td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase <?= $rdv['statut'] === 'confirmé' ? 'bg-green-100 text-green-700' : ($rdv['statut'] === 'annulé' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') ?>">
                                <?= $rdv['statut'] ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <form method="POST" class="flex justify-center gap-2">
                                <input type="hidden" name="rdv_id" value="<?= $rdv['id'] ?>">
                                <?php if ($rdv['statut'] === 'en attente'): ?>
                                    <button type="submit" name="update_status" value="1" onclick="this.form.statut.value='confirmé'" class="p-2 bg-green-50 text-green-600 rounded-lg hover:bg-green-100 transition-colors" title="Confirmer">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="submit" name="update_status" value="1" onclick="this.form.statut.value='annulé'" class="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors" title="Annuler">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <input type="hidden" name="statut" value="">
                                <?php endif; ?>
                                <a href="patient_details.php?id=<?= $rdv['idpatient'] ?>" class="p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors" title="Voir dossier">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once '../components/doctor_layout_bottom.php'; ?>
