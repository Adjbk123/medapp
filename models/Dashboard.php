<?php
class Dashboard {
    private $db;
    private $medecin_id;

    public function __construct($db, $medecin_id) {
        $this->db = $db;
        $this->medecin_id = $medecin_id;
    }

    // Obtenir le nombre de rendez-vous du jour
    public function getRendezVousAujourdhui() {
        $query = "SELECT COUNT(*) as total FROM rendezvous 
                 WHERE idmedecin = ? AND DATE(dateheure) = CURDATE()";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$this->medecin_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    // Obtenir le nombre de patients actifs
    public function getPatientsActifs() {
        $query = "SELECT COUNT(DISTINCT idpatient) as total 
                 FROM rendezvous 
                 WHERE idmedecin = ? 
                 AND dateheure >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$this->medecin_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    // Obtenir le nombre de consultations du jour
    public function getConsultationsDuJour() {
        $today = date('Y-m-d');
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total
            FROM consultation
            WHERE id_medecin = ? 
            AND DATE(date_consultation) = ?
        ");
        $stmt->execute([$this->medecin_id, $today]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // Obtenir le nombre de messages non lus
    public function getMessagesNonLus() {
        $query = "SELECT COUNT(*) as total FROM messages m
                 INNER JOIN patient p ON m.sender_id = p.id
                 WHERE m.receiver_id = :user_id 
                 AND m.lu = 0";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":user_id", $this->medecin_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Obtenir les derniers patients
    public function getDerniersPatients($limit = 5) {
        $query = "SELECT p.id, p.nom, p.prenom, p.datenais, p.sexe, p.email, p.contact, MAX(r.dateheure) as derniere_visite 
                 FROM patient p 
                 INNER JOIN rendezvous r ON p.id = r.idpatient 
                 WHERE r.idmedecin = ? 
                 GROUP BY p.id 
                 ORDER BY derniere_visite DESC 
                 LIMIT ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$this->medecin_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtenir les rendez-vous du jour
    public function getRendezVousDuJour() {
        $query = "SELECT r.*, p.nom as patient_nom, p.prenom as patient_prenom
                 FROM rendezvous r
                 INNER JOIN patient p ON r.idpatient = p.id
                 WHERE r.idmedecin = ?
                 AND DATE(r.dateheure) = CURDATE()
                 ORDER BY r.dateheure ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$this->medecin_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtenir les rappels importants
    public function getRappelsImportants() {
        $rappels = [];

        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM rendezvous WHERE idmedecin = ? AND statut = 'en attente' AND dateheure >= CURDATE()");
            $stmt->execute([$this->medecin_id]);
            $total = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            if ($total > 0) {
                $rappels[] = [
                    'titre' => "$total rendez-vous en attente de confirmation",
                    'description' => 'Veuillez confirmer ou annuler ces rendez-vous.'
                ];
            }
        } catch (Exception $e) {}

        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM rendezvous WHERE idmedecin = ? AND DATE(dateheure) = CURDATE()");
            $stmt->execute([$this->medecin_id]);
            $total = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            if ($total > 0) {
                $rappels[] = [
                    'titre' => "$total rendez-vous aujourd'hui",
                    'description' => "Consultez votre agenda pour voir les détails."
                ];
            }
        } catch (Exception $e) {}

        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM messages WHERE receiver_id = ? AND lu = 0");
            $stmt->execute([$this->medecin_id]);
            $total = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            if ($total > 0) {
                $rappels[] = [
                    'titre' => "$total message(s) non lu(s)",
                    'description' => 'Vous avez des messages en attente de lecture.'
                ];
            }
        } catch (Exception $e) {}

        return $rappels;
    }
}
?> 