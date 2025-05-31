<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure les fichiers nécessaires
require_once '../../includes/session.php';
require_once '../../includes/security.php';
require_once '../../config/database.php';
require_once '../../models/Medecin.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
requireLogin();
requireRole('admin');

// Vérifier si un ID a été fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<div class="admin-alert admin-alert-danger">ID de médecin non spécifié.</div>';
    exit;
}

$medecin_id = intval($_GET['id']);

// Connexion à la base de données
try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    echo '<div class="admin-alert admin-alert-danger">Erreur de connexion à la base de données: ' . $e->getMessage() . '</div>';
    exit;
}

// Récupérer les détails complets du médecin
$query = "SELECT m.*, s.nomspecialite 
          FROM medecin m 
          LEFT JOIN specialite s ON m.idspecialite = s.id 
          WHERE m.id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $medecin_id);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    echo '<div class="admin-alert admin-alert-danger">Médecin non trouvé.</div>';
    exit;
}

$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

// Formater la date de naissance
$date_naissance = !empty($doctor['datenais']) ? date('d/m/Y', strtotime($doctor['datenais'])) : 'Non spécifiée';

// Vérifier si le médecin a un diplôme
$diplome_path = !empty($doctor['diplome']) ? '../../uploads/diplomes/' . $doctor['diplome'] : '';
$has_diplome = !empty($diplome_path) && file_exists($diplome_path);

// Récupérer d'autres informations liées au médecin si nécessaire
// Par exemple, les horaires, les avis, etc.
?>

<div class="admin-grid admin-grid-cols-1 admin-md:grid-cols-2 admin-gap-6">
    <!-- Informations personnelles -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h4 class="admin-card-title">Informations personnelles</h4>
        </div>
        <div class="admin-card-body">
            <div class="admin-flex admin-items-center admin-gap-4 admin-mb-4">
                <div class="admin-stat-icon warning" style="width: 4rem; height: 4rem;">
                    <i class="fas fa-user-md" style="font-size: 2rem;"></i>
                </div>
                <div>
                    <h3 class="admin-text-xl admin-font-bold"><?php echo htmlspecialchars($doctor['prenom'] . ' ' . $doctor['nom']); ?></h3>
                    <p class="admin-text-muted"><?php echo htmlspecialchars($doctor['nomspecialite'] ?? 'Spécialité non spécifiée'); ?></p>
                </div>
            </div>
            
            <div class="admin-grid admin-grid-cols-2 admin-gap-4">
                <div class="admin-form-group">
                    <label class="admin-form-label">Nom</label>
                    <p class="admin-form-control-static"><?php echo htmlspecialchars($doctor['nom']); ?></p>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Prénom</label>
                    <p class="admin-form-control-static"><?php echo htmlspecialchars($doctor['prenom']); ?></p>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Date de naissance</label>
                    <p class="admin-form-control-static"><?php echo $date_naissance; ?></p>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Email</label>
                    <p class="admin-form-control-static"><?php echo htmlspecialchars($doctor['email']); ?></p>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Téléphone</label>
                    <p class="admin-form-control-static"><?php echo htmlspecialchars($doctor['contact'] ?? 'Non spécifié'); ?></p>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Date d'inscription</label>
                    <p class="admin-form-control-static"><?php echo !empty($doctor['created_at']) ? date('d/m/Y H:i', strtotime($doctor['created_at'])) : 'Non spécifiée'; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Informations professionnelles -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h4 class="admin-card-title">Informations professionnelles</h4>
        </div>
        <div class="admin-card-body">
            <div class="admin-grid admin-grid-cols-2 admin-gap-4">
                <div class="admin-form-group">
                    <label class="admin-form-label">Spécialité</label>
                    <p class="admin-form-control-static"><?php echo htmlspecialchars($doctor['nomspecialite'] ?? 'Non spécifiée'); ?></p>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Numéro RPPS</label>
                    <p class="admin-form-control-static">
                        <span class="admin-badge admin-badge-primary">
                            <?php echo htmlspecialchars($doctor['num'] ?? 'Non spécifié'); ?>
                        </span>
                    </p>
                </div>
                <?php if (isset($doctor['experience'])): ?>
                <div class="admin-form-group">
                    <label class="admin-form-label">Années d'expérience</label>
                    <p class="admin-form-control-static"><?php echo htmlspecialchars($doctor['experience']); ?> ans</p>
                </div>
                <?php endif; ?>
                <?php if (isset($doctor['hopital'])): ?>
                <div class="admin-form-group">
                    <label class="admin-form-label">Hôpital/Clinique</label>
                    <p class="admin-form-control-static"><?php echo htmlspecialchars($doctor['hopital']); ?></p>
                </div>
                <?php endif; ?>
                <div class="admin-form-group admin-col-span-2">
                    <label class="admin-form-label">Diplôme</label>
                    <?php if ($has_diplome): ?>
                    <div class="admin-flex admin-items-center admin-gap-2">
                        <a href="<?php echo $diplome_path; ?>" target="_blank" class="admin-btn admin-btn-sm admin-btn-primary">
                            <i class="fas fa-file-pdf"></i>
                            <span>Voir le diplôme</span>
                        </a>
                        <span class="admin-text-sm admin-text-muted"><?php echo htmlspecialchars($doctor['diplome']); ?></span>
                    </div>
                    <?php else: ?>
                    <p class="admin-form-control-static">
                        <span class="admin-badge admin-badge-danger">Aucun diplôme fourni</span>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statut de vérification -->
<div class="admin-card admin-mt-6">
    <div class="admin-card-header">
        <h4 class="admin-card-title">Statut de vérification</h4>
    </div>
    <div class="admin-card-body">
        <div class="admin-flex admin-items-center admin-gap-4">
            <div class="admin-stat-icon warning">
                <i class="fas fa-clock"></i>
            </div>
            <div>
                <h4 class="admin-font-bold">En attente de vérification</h4>
                <p class="admin-text-sm admin-text-muted">Ce médecin attend votre validation pour pouvoir accéder à la plateforme.</p>
            </div>
        </div>
        
        <div class="admin-mt-4">
            <label class="admin-form-label">Commentaire (optionnel)</label>
            <textarea id="commentaire" name="commentaire" class="admin-form-control" rows="3" placeholder="Ajoutez un commentaire concernant la vérification..."></textarea>
            <p class="admin-form-text">Ce commentaire sera enregistré dans l'historique de vérification.</p>
        </div>
    </div>
</div>

<script>
    // Copier le commentaire du modal vers le formulaire principal lors de la soumission
    document.getElementById('actionFormInModal').addEventListener('submit', function() {
        const commentaire = document.getElementById('commentaire').value;
        const commentaireInput = document.createElement('input');
        commentaireInput.type = 'hidden';
        commentaireInput.name = 'commentaire';
        commentaireInput.value = commentaire;
        this.appendChild(commentaireInput);
    });
</script>
