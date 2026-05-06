<?php
$page_title = "Réparation Automatique - MedAdmin";
$header_title = "Correction Diplômes";
$header_icon = "fas fa-magic";
include_once '../components/admin_layout_top.php';

$diplomes_dir = __DIR__ . '/../../uploads/diplomes/';
$available_files = is_dir($diplomes_dir) ? array_diff(scandir($diplomes_dir), ['.', '..']) : [];

$fixed_count = 0;
$errors = [];

if (!empty($available_files)) {
    $default_file = reset($available_files);
    $doctors = $db->query("SELECT id, nom, prenom, diplome FROM medecin WHERE diplome IS NOT NULL AND diplome != ''")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($doctors as $doctor) {
        if (!in_array($doctor['diplome'], $available_files)) {
            $stmt = $db->prepare("UPDATE medecin SET diplome = ? WHERE id = ?");
            if ($stmt->execute([$default_file, $doctor['id']])) {
                $fixed_count++;
            } else {
                $errors[] = "Échec pour " . $doctor['nom'];
            }
        }
    }
}
?>

<div class="fade-in max-w-2xl mx-auto">
    <div class="bg-white p-12 rounded-3xl shadow-sm border border-slate-100 text-center">
        <?php if (empty($available_files)): ?>
            <div class="w-20 h-20 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-800 mb-2">Erreur Critique</h3>
            <p class="text-slate-500">Aucun fichier trouvé dans le répertoire des diplômes.</p>
        <?php else: ?>
            <div class="w-20 h-20 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-800 mb-2">Processus Terminé</h3>
            <p class="text-slate-500 mb-8"><?= $fixed_count ?> associations ont été réparées avec le fichier par défaut.</p>
            
            <div class="bg-slate-50 p-4 rounded-xl text-sm font-medium text-slate-600 mb-8">
                Fichier utilisé : <span class="text-admin-600"><?= htmlspecialchars($default_file) ?></span>
            </div>
        <?php endif; ?>

        <div class="flex flex-col gap-3">
            <a href="update_diploma.php" class="bg-admin-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-admin-700 transition-all shadow-lg shadow-admin-500/20">Retour à la gestion</a>
            <a href="dashboard.php" class="text-slate-400 hover:text-slate-600 text-sm font-bold">Aller au tableau de bord</a>
        </div>
    </div>
</div>

<?php include_once '../components/admin_layout_bottom.php'; ?>
