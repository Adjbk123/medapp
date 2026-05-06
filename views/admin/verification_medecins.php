<?php
$page_title = "Vérification Approfondie - MedAdmin";
$header_title = "Vérifications Profils";
$header_icon = "fas fa-user-check";
include_once '../components/admin_layout_top.php';

// Récupérer les médecins en attente avec leurs profils
$query = "SELECT m.*, p.specialite as p_spec, p.hopital_actuel, p.annees_experience 
          FROM medecin m 
          LEFT JOIN profilmedecin p ON m.id = p.id_medecin 
          WHERE m.verification_status = 'pending' 
          ORDER BY m.created_at DESC";
$stmt = $db->query($query);
$medecins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="fade-in space-y-8">
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-8 border-b border-slate-50 bg-slate-50/30 flex justify-between items-center">
            <div>
                <h3 class="text-lg font-bold text-slate-800">Files d'attente de validation</h3>
                <p class="text-sm text-slate-500">Examen détaillé des compétences et affiliations</p>
            </div>
            <a href="verify_doctors.php" class="text-sm font-bold text-admin-600 hover:underline">Vue simplifiée</a>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                        <th class="px-8 py-4 border-b">Praticien</th>
                        <th class="px-8 py-4 border-b">Expérience</th>
                        <th class="px-8 py-4 border-b">Hôpital</th>
                        <th class="px-8 py-4 border-b">Document</th>
                        <th class="px-8 py-4 border-b text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if (empty($medecins)): ?>
                        <tr>
                            <td colspan="5" class="px-8 py-12 text-center text-slate-400 italic">Aucun profil en attente</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($medecins as $m): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-admin-50 text-admin-600 flex items-center justify-center font-bold">
                                        <?= strtoupper(substr($m['nom'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-800"><?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></p>
                                        <p class="text-xs text-slate-400"><?= htmlspecialchars($m['p_spec'] ?? 'Non renseigné') ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-5 text-sm text-slate-600 font-bold"><?= htmlspecialchars($m['annees_experience'] ?? '0') ?> ans</td>
                            <td class="px-8 py-5 text-sm text-slate-500"><?= htmlspecialchars($m['hopital_actuel'] ?? 'Indépendant') ?></td>
                            <td class="px-8 py-5">
                                <?php if ($m['diplome']): ?>
                                    <a href="../uploads/diplomes/<?= urlencode($m['diplome']) ?>" target="_blank" class="text-admin-600 hover:text-admin-800 font-bold text-xs flex items-center gap-2">
                                        <i class="fas fa-file-pdf"></i> DIPLÔME
                                    </a>
                                <?php else: ?>
                                    <span class="text-slate-300 text-xs italic">Aucun</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-8 py-5 text-right">
                                <a href="doctor_details.php?id=<?= $m['id'] ?>" class="inline-block px-4 py-2 bg-admin-600 text-white rounded-xl text-xs font-bold hover:bg-admin-700 transition-shadow shadow-lg shadow-admin-500/20">
                                    Examiner
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once '../components/admin_layout_bottom.php'; ?>