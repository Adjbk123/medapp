<?php
require_once 'config/database.php';
require_once 'models/Medecin.php';

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Créer un fichier de log
$log_file = __DIR__ . '/delete_debug.log';
file_put_contents($log_file, "=== Début du débogage de la suppression ===\n");

function writeLog($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();
writeLog("Connexion à la base de données établie");

// Créer une instance de Medecin
$medecin = new Medecin($db);

// ID du médecin à supprimer (à remplacer par l'ID réel)
$medecin_id = isset($_GET['id']) ? $_GET['id'] : 1;
$medecin->id = $medecin_id;
writeLog("Tentative de suppression du médecin avec ID: " . $medecin_id);

// Afficher la structure de la table
try {
    $tables_query = "SHOW TABLES";
    $tables_stmt = $db->prepare($tables_query);
    $tables_stmt->execute();
    
    writeLog("Tables dans la base de données:");
    while ($table = $tables_stmt->fetch(PDO::FETCH_COLUMN)) {
        writeLog("- " . $table);
        
        // Afficher la structure de chaque table
        $columns_query = "SHOW COLUMNS FROM " . $table;
        $columns_stmt = $db->prepare($columns_query);
        $columns_stmt->execute();
        
        while ($column = $columns_stmt->fetch(PDO::FETCH_ASSOC)) {
            writeLog("  - " . $column['Field'] . " (" . $column['Type'] . ")");
        }
    }
} catch (Exception $e) {
    writeLog("Erreur lors de l'affichage des tables: " . $e->getMessage());
}

// Vérifier si le médecin existe
try {
    $check_query = "SELECT id FROM medecins WHERE id = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(1, $medecin_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        writeLog("Le médecin avec ID " . $medecin_id . " existe dans la base de données");
    } else {
        writeLog("ERREUR: Aucun médecin trouvé avec ID " . $medecin_id);
    }
} catch (Exception $e) {
    writeLog("Erreur lors de la vérification du médecin: " . $e->getMessage());
}

// Vérifier les rendez-vous associés
try {
    $rdv_query = "SELECT COUNT(*) as count FROM rendez_vous WHERE id_medecin = ?";
    $rdv_stmt = $db->prepare($rdv_query);
    $rdv_stmt->bindParam(1, $medecin_id);
    $rdv_stmt->execute();
    $rdv_count = $rdv_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    writeLog("Nombre de rendez-vous associés: " . $rdv_count);
} catch (Exception $e) {
    writeLog("Erreur lors de la vérification des rendez-vous: " . $e->getMessage());
}

// Vérifier les horaires associés
try {
    $horaires_query = "SELECT COUNT(*) as count FROM horaires_medecin WHERE id_medecin = ?";
    $horaires_stmt = $db->prepare($horaires_query);
    $horaires_stmt->bindParam(1, $medecin_id);
    $horaires_stmt->execute();
    $horaires_count = $horaires_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    writeLog("Nombre d'horaires associés: " . $horaires_count);
} catch (Exception $e) {
    writeLog("Erreur lors de la vérification des horaires: " . $e->getMessage());
}

// Tenter de supprimer le médecin
try {
    $result = $medecin->delete();
    if ($result) {
        writeLog("SUCCÈS: Le médecin a été supprimé avec succès");
    } else {
        writeLog("ÉCHEC: La suppression du médecin a échoué");
    }
} catch (Exception $e) {
    writeLog("EXCEPTION lors de la suppression: " . $e->getMessage());
}

writeLog("=== Fin du débogage de la suppression ===");

// Afficher le contenu du log
echo "<h1>Débogage de la suppression</h1>";
echo "<pre>" . file_get_contents($log_file) . "</pre>";
?>
