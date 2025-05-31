-- Script pour créer la table d'historique des médecins
CREATE TABLE IF NOT EXISTS medecin_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medecin_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    details TEXT,
    admin_id INT,
    admin_name VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (medecin_id),
    INDEX (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
