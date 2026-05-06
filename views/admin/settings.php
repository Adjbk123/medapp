<?php
$page_title = "Paramètres Administrateur - MedAdmin";
$header_title = "Paramètres";
$header_icon = "fas fa-cog";
include_once '../components/admin_layout_top.php';

// Traitement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        $stmt = $db->prepare("UPDATE admin SET nom=?, prenom=?, email=?, contact=? WHERE id=?");
        $stmt->execute([$_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['contact'], $user_id]);
        $_SESSION['nom'] = $_POST['nom'];
        $_SESSION['prenom'] = $_POST['prenom'];
        $success = "Profil mis à jour.";
    } elseif ($_POST['action'] === 'change_password') {
        $admin = $db->query("SELECT password FROM admin WHERE id=$user_id")->fetch();
        if (password_verify($_POST['old_password'], $admin['password']) && $_POST['new_password'] === $_POST['confirm_password']) {
            $db->prepare("UPDATE admin SET password=? WHERE id=?")->execute([password_hash($_POST['new_password'], PASSWORD_DEFAULT), $user_id]);
            $success = "Mot de passe modifié.";
        } else {
            $error = "Vérifiez vos mots de passe.";
        }
    }
}

$admin = $db->query("SELECT * FROM admin WHERE id=$user_id")->fetch();
?>

<div class="fade-in space-y-8 max-w-6xl mx-auto">
    <?php if (isset($success)): ?>
        <div class="p-4 bg-emerald-50 text-emerald-700 rounded-2xl border border-emerald-100"><?= $success ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="p-4 bg-red-50 text-red-700 rounded-2xl border border-red-100"><?= $error ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Sidebar Info -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 text-center">
                <div class="w-24 h-24 rounded-full bg-gradient-to-br from-admin-400 to-admin-600 mx-auto mb-6 flex items-center justify-center text-white text-3xl shadow-lg shadow-admin-500/20">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-800"><?= htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']) ?></h3>
                <p class="text-sm text-slate-500 mb-6">Administrateur Système</p>
                <div class="pt-6 border-t border-slate-50 flex justify-center gap-4">
                    <div class="text-center">
                        <p class="text-xs text-slate-400 uppercase font-bold">Rôle</p>
                        <p class="text-sm font-bold text-admin-600">Super Admin</p>
                    </div>
                </div>
            </div>

            <div class="bg-slate-900 p-8 rounded-3xl shadow-xl text-white">
                <h4 class="text-sm font-bold uppercase tracking-widest text-slate-400 mb-6">État du Système</h4>
                <div class="space-y-4">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-400">Version</span>
                        <span class="font-bold">v2.4.0</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-400">Environnement</span>
                        <span class="px-2 py-0.5 bg-amber-500/20 text-amber-500 rounded font-bold text-[10px] uppercase">Production</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-400">Database</span>
                        <span class="text-emerald-400 font-bold"><i class="fas fa-circle text-[8px] mr-1"></i> Connectée</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Forms -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Profil -->
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
                <h3 class="text-lg font-bold text-slate-800 mb-6">Informations Personnelles</h3>
                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <input type="hidden" name="action" value="update_profile">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Prénom</label>
                        <input type="text" name="prenom" value="<?= htmlspecialchars($admin['prenom']) ?>" class="w-full px-4 py-3 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-admin-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Nom</label>
                        <input type="text" name="nom" value="<?= htmlspecialchars($admin['nom']) ?>" class="w-full px-4 py-3 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-admin-500 outline-none">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Email Professionnel</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" class="w-full px-4 py-3 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-admin-500 outline-none">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Contact</label>
                        <input type="text" name="contact" value="<?= htmlspecialchars($admin['contact']) ?>" class="w-full px-4 py-3 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-admin-500 outline-none">
                    </div>
                    <div class="md:col-span-2 flex justify-end">
                        <button type="submit" class="bg-admin-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-admin-700 transition-colors">Enregistrer</button>
                    </div>
                </form>
            </div>

            <!-- Password -->
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
                <h3 class="text-lg font-bold text-slate-800 mb-6">Sécurité du Compte</h3>
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="change_password">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Ancien mot de passe</label>
                        <input type="password" name="old_password" required class="w-full px-4 py-3 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-admin-500 outline-none">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Nouveau mot de passe</label>
                            <input type="password" name="new_password" required class="w-full px-4 py-3 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-admin-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Confirmation</label>
                            <input type="password" name="confirm_password" required class="w-full px-4 py-3 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-admin-500 outline-none">
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="bg-slate-800 text-white px-8 py-3 rounded-xl font-bold hover:bg-slate-900 transition-colors">Changer le mot de passe</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once '../components/admin_layout_bottom.php'; ?>
