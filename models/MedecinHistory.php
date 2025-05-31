<?php
/**
 * Classe pour gérer l'historique des actions liées aux médecins
 */
class MedecinHistory {
    private $db;
    private $table_name = "medecin_history";
    
    public $id;
    public $medecin_id;
    public $action;
    public $details;
    public $admin_id;
    public $admin_name;
    public $created_at;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Ajoute une entrée dans l'historique
     * 
     * @param int $medecin_id ID du médecin concerné
     * @param string $action Type d'action (verification, rejection, update, etc.)
     * @param string $details Détails supplémentaires sur l'action
     * @return bool True si l'ajout a réussi, False sinon
     */
    public function addEntry($medecin_id, $action, $details = "") {
        // Vérifier si la table existe, sinon la créer
        $this->createTableIfNotExists();
        
        // Récupérer les informations de l'administrateur connecté
        $admin_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
        $admin_name = isset($_SESSION['nom']) && isset($_SESSION['prenom']) 
                    ? $_SESSION['prenom'] . ' ' . $_SESSION['nom'] 
                    : 'Système';
        
        // Préparer la requête
        $query = "INSERT INTO " . $this->table_name . " 
                 (medecin_id, action, details, admin_id, admin_name, created_at) 
                 VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($query);
        
        // Binding des paramètres
        $stmt->bindParam(1, $medecin_id);
        $stmt->bindParam(2, $action);
        $stmt->bindParam(3, $details);
        $stmt->bindParam(4, $admin_id);
        $stmt->bindParam(5, $admin_name);
        
        // Exécuter la requête
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Récupère l'historique d'un médecin
     * 
     * @param int $medecin_id ID du médecin
     * @param int $limit Nombre maximum d'entrées à récupérer
     * @return array Tableau des entrées d'historique
     */
    public function getHistoryByMedecinId($medecin_id, $limit = 10) {
        // Vérifier si la table existe, sinon la créer
        $this->createTableIfNotExists();
        
        // Préparer la requête
        $query = "SELECT * FROM " . $this->table_name . " 
                 WHERE medecin_id = ? 
                 ORDER BY created_at DESC 
                 LIMIT ?";
        
        $stmt = $this->db->prepare($query);
        
        // Binding des paramètres
        $stmt->bindParam(1, $medecin_id);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        
        // Exécuter la requête
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Crée la table d'historique si elle n'existe pas
     */
    private function createTableIfNotExists() {
        $query = "CREATE TABLE IF NOT EXISTS " . $this->table_name . " (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    medecin_id INT NOT NULL,
                    action VARCHAR(50) NOT NULL,
                    details TEXT,
                    admin_id INT,
                    admin_name VARCHAR(100),
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX (medecin_id),
                    INDEX (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->exec($query);
    }
}
?>
