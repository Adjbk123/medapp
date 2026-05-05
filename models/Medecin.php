<?php
require_once 'User.php';

class Medecin extends User {
    protected $idspecialite;
    protected $num;
    protected $diplome;
    public $verification_token;
    public $verification_token_expires;
    public $reset_token;
    public $reset_token_expires;
    public $remember_token;
    public $remember_token_expires;
    public $verification_status = 'pending';
    
    public function __construct($db) {
        parent::__construct($db);
        $this->table_name = "medecin";
        $this->role = "medecin";
    }
    
    // Getters et Setters
    public function getIdSpecialite() {
        return $this->idspecialite;
    }

    public function setIdSpecialite($idspecialite) {
        $this->idspecialite = $idspecialite;
    }

    public function getNum() {
        return $this->num;
    }

    public function setNum($num) {
        $this->num = $num;
    }
    
    public function getDiplome() {
        return $this->diplome;
    }

    public function setDiplome($diplome) {
        $this->diplome = $diplome;
    }

    // Méthode pour générer un token de vérification
    public function generateVerificationToken() {
        // Fonction de log locale
        $writeLog = function($message) {
            $log_file = __DIR__ . '/../logs/debug.log';
            $timestamp = date('Y-m-d H:i:s');
            file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
        };
        
        // Générer un token unique
        $token = bin2hex(random_bytes(32));
        $writeLog("Nouveau token généré : " . $token);
        
        // Stocker le token et sa date d'expiration
        $this->verification_token = $token;
        $this->verification_token_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        return $token;
    }
    
    // Implémentation de la méthode register pour les médecins
    public function register() {
        // Fonction de log locale
        $writeLog = function($message) {
            $log_file = __DIR__ . '/../logs/debug.log';
            $timestamp = date('Y-m-d H:i:s');
            file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
        };
        
        // Définir le statut de vérification comme "pending"
        $this->verification_status = 'pending';
        $writeLog("Inscription d'un nouveau médecin avec statut : " . $this->verification_status);

        $query = "INSERT INTO " . $this->table_name . " 
                  (nom, prenom, datenais, email, contact, password, role, num, diplome, idspecialite, 
                   verification_status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $writeLog("Requête SQL : " . $query);
        
        $stmt = $this->db->prepare($query);
        
        // Nettoyage et sécurisation des entrées
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->prenom = htmlspecialchars(strip_tags($this->prenom));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->contact = htmlspecialchars(strip_tags($this->contact));
        $this->diplome = htmlspecialchars(strip_tags($this->diplome));

        
        // Hashage du mot de passe
        $hashed_password = $this->hashPassword($this->password);
        
        // Binding des valeurs
        $stmt->bindParam(1, $this->nom);
        $stmt->bindParam(2, $this->prenom);
        $stmt->bindParam(3, $this->datenais);
        $stmt->bindParam(4, $this->email);
        $stmt->bindParam(5, $this->contact);
        $stmt->bindParam(6, $hashed_password);
        $stmt->bindParam(7, $this->role);
        $stmt->bindParam(8, $this->num);
        $stmt->bindParam(9, $this->diplome);
        $stmt->bindParam(10, $this->idspecialite);
        $stmt->bindParam(11, $this->verification_status);
        
        if($stmt->execute()) {
            $writeLog("Compte médecin enregistré avec succès, en attente de vérification par l'administrateur");
            return true; // Retourner true pour indiquer que l'inscription a réussi
        } else {
            $writeLog("Erreur lors de l'enregistrement : " . implode(", ", $stmt->errorInfo()));
        }
        
        return false;
    }
    
    // Méthode pour mettre à jour un médecin
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nom = ?, prenom = ?, datenais = ?, email = ?, contact = ? 
                  WHERE id = ?";
        
        $stmt = $this->db->prepare($query);
        
        // Nettoyage et sécurisation des entrées
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->prenom = htmlspecialchars(strip_tags($this->prenom));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->contact = htmlspecialchars(strip_tags($this->contact));
        
        // Binding des valeurs
        $stmt->bindParam(1, $this->nom);
        $stmt->bindParam(2, $this->prenom);
        $stmt->bindParam(3, $this->datenais);
        $stmt->bindParam(4, $this->email);
        $stmt->bindParam(5, $this->contact);
        $stmt->bindParam(6, $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Méthode pour mettre à jour le mot de passe
    public function updatePassword() {
        $query = "UPDATE " . $this->table_name . " SET password = ? WHERE id = ?";
        
        $stmt = $this->db->prepare($query);
        
        // Hashage du nouveau mot de passe
        $hashed_password = $this->hashPassword($this->password);
        
        // Binding des valeurs
        $stmt->bindParam(1, $hashed_password);
        $stmt->bindParam(2, $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Méthode pour confirmer un compte utilisateur
    public function confirmAccount($token) {
        // Fonction de log locale
        $writeLog = function($message) {
            $log_file = __DIR__ . '/../logs/debug.log';
            $timestamp = date('Y-m-d H:i:s');
            file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
        };
        
        $writeLog("Début de la confirmation du compte avec le token : " . $token);
        
        // Vérifier si le token est valide et n'a pas expiré
        $query = "SELECT id, verification_status, verification_token, verification_token_expires FROM " . $this->table_name . " 
                 WHERE verification_token = ? 
                 AND verification_token_expires > NOW() 
                 LIMIT 0,1";
        
        $writeLog("Requête SQL : " . $query);
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $token);
        $stmt->execute();
        
        // Log du nombre de résultats
        $writeLog("Nombre de résultats trouvés : " . $stmt->rowCount());
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $writeLog("Compte trouvé avec l'ID : " . $row['id']);
            $writeLog("Statut actuel : " . $row['verification_status']);
            $writeLog("Token stocké : " . $row['verification_token']);
            $writeLog("Expiration du token : " . $row['verification_token_expires']);
            
            $this->id = $row['id'];
            
            // Vérifier si le compte n'est pas déjà vérifié
            if ($row['verification_status'] === 'verified') {
                $writeLog("Le compte est déjà vérifié");
                return true;
            }
            
            $query = "UPDATE " . $this->table_name . " 
                     SET verification_status = 'verified', 
                         verification_token = NULL, 
                         verification_token_expires = NULL 
                     WHERE id = ?";
            
            $writeLog("Requête de mise à jour : " . $query);
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(1, $this->id);
            
            if($stmt->execute()) {
                $writeLog("Compte mis à jour avec succès");
                return true;
            } else {
                $writeLog("Erreur lors de la mise à jour du compte : " . implode(", ", $stmt->errorInfo()));
            }
        } else {
            // Vérifier si le token existe mais a expiré
            $query = "SELECT id, verification_token_expires FROM " . $this->table_name . " WHERE verification_token = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(1, $token);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $writeLog("Token trouvé mais expiré. Date d'expiration : " . $row['verification_token_expires']);
            } else {
                $writeLog("Aucun compte trouvé avec ce token");
            }
        }
        
        return false;
    }
    
    /**
     * Met à jour le statut de vérification d'un médecin
     * 
     * @param string $status Le nouveau statut ('verified', 'rejected', 'pending')
     * @return bool True si la mise à jour a réussi, false sinon
     */
    public function updateVerificationStatus($status) {
        // Fonction de log locale
        $writeLog = function($message) {
            $log_file = __DIR__ . '/../logs/debug.log';
            $timestamp = date('Y-m-d H:i:s');
            file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
        };
        
        $writeLog("Mise à jour du statut de vérification pour le médecin ID: " . $this->id . " vers " . $status);
        
        // Vérifier que le statut est valide
        $valid_statuses = ['verified', 'rejected', 'pending'];
        if (!in_array($status, $valid_statuses)) {
            $writeLog("Statut invalide: " . $status);
            return false;
        }
        
        $query = "UPDATE " . $this->table_name . " SET verification_status = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $status);
        $stmt->bindParam(2, $this->id);
        
        if ($stmt->execute()) {
            $writeLog("Statut de vérification mis à jour avec succès");
            return true;
        } else {
            $writeLog("Erreur lors de la mise à jour du statut: " . implode(", ", $stmt->errorInfo()));
            return false;
        }
    }
    
    /**
     * Envoie un email de confirmation de vérification au médecin
     * 
     * @return bool True si l'email a été envoyé avec succès, false sinon
     */
    public function sendVerificationConfirmationEmail() {
        // Fonction de log locale
        $writeLog = function($message) {
            $log_file = __DIR__ . '/../logs/debug.log';
            $timestamp = date('Y-m-d H:i:s');
            file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
        };
        
        // Récupérer les informations du médecin
        $query = "SELECT nom, prenom, email FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $nom_complet = $row['prenom'] . ' ' . $row['nom'];
            $email = $row['email'];
            
            $writeLog("Envoi d'un email de confirmation de vérification à " . $email);
            
            try {
                // Utiliser la classe Mailer existante
                require_once __DIR__ . '/../send_mail.php';
                $mailer = new Mailer();
                
                // Préparer le contenu de l'email
                $subject = "Votre compte MedConnect a été vérifié";
                $message = "<html>
                            <head>
                                <title>Compte vérifié</title>
                            </head>
                            <body>
                                <h2>Bonjour Dr. " . htmlspecialchars($nom_complet) . ",</h2>
                                <p>Nous avons le plaisir de vous informer que votre compte MedConnect a été vérifié avec succès.</p>
                                <p>Vous pouvez maintenant vous connecter à votre compte et commencer à utiliser nos services.</p>
                                <p><a href='" . env('APP_URL', '') . url('views/login.php') . "'>Se connecter à MedConnect</a></p>
                                <p>Cordialement,<br>L'équipe MedConnect</p>
                            </body>
                            </html>";
                
                // Créer un fichier temporaire pour l'email
                $temp_file = __DIR__ . '/../views/emails/temp_verification.php';
                file_put_contents($temp_file, $message);
                
                // Envoyer l'email en utilisant la classe Mailer
                // Note: En environnement de développement, nous simulons l'envoi d'email
                if (env('APP_ENV') === 'development') {
                    $writeLog("Simulation d'envoi d'email en environnement de développement");
                    $writeLog("Email qui aurait été envoyé à: " . $email);
                    $writeLog("Sujet: " . $subject);
                    $writeLog("Message: " . $message);
                    return true;
                } else {
                    // En production, envoyer réellement l'email
                    $result = $mailer->sendCustomEmail($email, $nom_complet, $subject, $message);
                    if ($result) {
                        $writeLog("Email de confirmation envoyé avec succès");
                        return true;
                    } else {
                        $writeLog("Erreur lors de l'envoi de l'email de confirmation");
                        return false;
                    }
                }
            } catch (Exception $e) {
                $writeLog("Exception lors de l'envoi de l'email: " . $e->getMessage());
                return false;
            }
        } else {
            $writeLog("Aucun médecin trouvé avec l'ID: " . $this->id);
            return false;
        }
    }
    
    /**
     * Envoie un email de rejet au médecin
     * 
     * @return bool True si l'email a été envoyé avec succès, false sinon
     */
    public function sendRejectionEmail() {
        // Fonction de log locale
        $writeLog = function($message) {
            $log_file = __DIR__ . '/../logs/debug.log';
            $timestamp = date('Y-m-d H:i:s');
            file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
        };
        
        // Récupérer les informations du médecin
        $query = "SELECT nom, prenom, email FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $nom_complet = $row['prenom'] . ' ' . $row['nom'];
            $email = $row['email'];
            
            $writeLog("Envoi d'un email de rejet à " . $email);
            
            try {
                // Utiliser la classe Mailer existante
                require_once __DIR__ . '/../send_mail.php';
                $mailer = new Mailer();
                
                // Préparer le contenu de l'email
                $subject = "Information concernant votre demande d'inscription MedConnect";
                $message = "<html>
                            <head>
                                <title>Information sur votre compte</title>
                            </head>
                            <body>
                                <h2>Bonjour Dr. " . htmlspecialchars($nom_complet) . ",</h2>
                                <p>Nous vous remercions pour votre demande d'inscription à MedConnect.</p>
                                <p>Après examen de votre dossier, nous ne sommes malheureusement pas en mesure de valider votre compte pour le moment.</p>
                                <p>Cela peut être dû à des informations incomplètes ou incorrectes. Nous vous invitons à nous contacter pour plus d'informations.</p>
                                <p>Vous pouvez nous joindre par email à support@medconnect.com ou par téléphone au 01 23 45 67 89.</p>
                                <p>Cordialement,<br>L'équipe MedConnect</p>
                            </body>
                            </html>";
                
                // Créer un fichier temporaire pour l'email
                $temp_file = __DIR__ . '/../views/emails/temp_rejection.php';
                file_put_contents($temp_file, $message);
                
                // Envoyer l'email en utilisant la classe Mailer
                // Note: En environnement de développement, nous simulons l'envoi d'email
                if (env('APP_ENV') === 'development') {
                    $writeLog("Simulation d'envoi d'email de rejet en environnement de développement");
                    $writeLog("Email qui aurait été envoyé à: " . $email);
                    $writeLog("Sujet: " . $subject);
                    $writeLog("Message: " . $message);
                    return true;
                } else {
                    // En production, envoyer réellement l'email
                    $result = $mailer->sendCustomEmail($email, $nom_complet, $subject, $message);
                    if ($result) {
                        $writeLog("Email de rejet envoyé avec succès");
                        return true;
                    } else {
                        $writeLog("Erreur lors de l'envoi de l'email de rejet");
                        return false;
                    }
                }
            } catch (Exception $e) {
                $writeLog("Exception lors de l'envoi de l'email de rejet: " . $e->getMessage());
                return false;
            }
        } else {
            $writeLog("Aucun médecin trouvé avec l'ID: " . $this->id);
            return false;
        }
    }
    
    /**
     * Supprime un médecin de la base de données
     * 
     * @return bool True si la suppression a réussi, false sinon
     */
    public function delete() {
        // Créer le répertoire de logs s'il n'existe pas
        $log_dir = __DIR__ . '/../logs';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0777, true);
        }
        
        // Fonction de log locale
        $writeLog = function($message) use ($log_dir) {
            $log_file = $log_dir . '/delete_medecin.log';
            $timestamp = date('Y-m-d H:i:s');
            file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
            // Également écrire dans error_log pour le débogage
            error_log("DELETE_MEDECIN: " . $message);
        };
        
        $writeLog("Début de la suppression du médecin avec l'ID: " . $this->id);
        
        // Vérifier si l'ID est valide
        if (empty($this->id) || !is_numeric($this->id)) {
            $writeLog("ERREUR: ID de médecin invalide: " . $this->id);
            return false;
        }
        
        // Vérifier si le médecin existe
        try {
            $check_query = "SELECT id FROM " . $this->table_name . " WHERE id = ?";
            $check_stmt = $this->db->prepare($check_query);
            $check_stmt->bindParam(1, $this->id);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() === 0) {
                $writeLog("ERREUR: Aucun médecin trouvé avec l'ID: " . $this->id);
                return false;
            }
            
            $writeLog("Médecin trouvé, début de la suppression des données associées");
        } catch (PDOException $e) {
            $writeLog("ERREUR lors de la vérification du médecin: " . $e->getMessage());
            return false;
        }
        
        try {
            // Commencer une transaction
            $this->db->beginTransaction();
            $writeLog("Transaction démarrée");
            
            // Vérifier si la table rendez_vous existe
            $tables_query = "SHOW TABLES LIKE 'rendez_vous'";
            $tables_stmt = $this->db->prepare($tables_query);
            $tables_stmt->execute();
            
            if ($tables_stmt->rowCount() > 0) {
                // Supprimer d'abord les rendez-vous associés au médecin
                $delete_rdv_query = "DELETE FROM rendez_vous WHERE id_medecin = ?";
                $delete_rdv_stmt = $this->db->prepare($delete_rdv_query);
                $delete_rdv_stmt->bindParam(1, $this->id);
                $delete_rdv_stmt->execute();
                $writeLog("Rendez-vous associés au médecin supprimés: " . $delete_rdv_stmt->rowCount() . " enregistrements");
            } else {
                $writeLog("La table rendez_vous n'existe pas, étape ignorée");
            }
            
            // Vérifier si la table horaires_medecin existe
            $tables_query = "SHOW TABLES LIKE 'horaires_medecin'";
            $tables_stmt = $this->db->prepare($tables_query);
            $tables_stmt->execute();
            
            if ($tables_stmt->rowCount() > 0) {
                // Supprimer les horaires du médecin
                $delete_horaires_query = "DELETE FROM horaires_medecin WHERE id_medecin = ?";
                $delete_horaires_stmt = $this->db->prepare($delete_horaires_query);
                $delete_horaires_stmt->bindParam(1, $this->id);
                $delete_horaires_stmt->execute();
                $writeLog("Horaires du médecin supprimés: " . $delete_horaires_stmt->rowCount() . " enregistrements");
            } else {
                $writeLog("La table horaires_medecin n'existe pas, étape ignorée");
            }
            
            // Supprimer les autres relations potentielles (ajouter selon le schéma de la base de données)
            // Par exemple, supprimer les avis sur le médecin, etc.
            
            // Finalement, supprimer le médecin
            $delete_query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
            $delete_stmt = $this->db->prepare($delete_query);
            $delete_stmt->bindParam(1, $this->id);
            $delete_stmt->execute();
            $rows_affected = $delete_stmt->rowCount();
            
            $writeLog("Suppression du médecin: " . $rows_affected . " enregistrement(s) affecté(s)");
            
            // Valider la transaction seulement si le médecin a été supprimé
            if ($rows_affected > 0) {
                $this->db->commit();
                $writeLog("Transaction validée, médecin supprimé avec succès");
                return true;
            } else {
                $this->db->rollBack();
                $writeLog("ERREUR: Aucun enregistrement supprimé pour le médecin, transaction annulée");
                return false;
            }
        } catch (PDOException $e) {
            // En cas d'erreur, annuler la transaction
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
                $writeLog("Transaction annulée suite à une erreur");
            }
            $writeLog("ERREUR PDO lors de la suppression du médecin: " . $e->getMessage());
            $writeLog("Code d'erreur SQL: " . $e->getCode());
            return false;
        } catch (Exception $e) {
            // En cas d'erreur générale, annuler la transaction
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
                $writeLog("Transaction annulée suite à une erreur générale");
            }
            $writeLog("ERREUR générale lors de la suppression du médecin: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vérifie un médecin (change son statut de vérification à 'verified')
     * 
     * @return bool True si la vérification a réussi, False sinon
     */
    public function verify() {
        try {
            // Fonction de log locale
            $writeLog = function($message) {
                $log_file = __DIR__ . '/../logs/debug.log';
                $timestamp = date('Y-m-d H:i:s');
                $log_message = "[$timestamp] $message\n";
                file_put_contents($log_file, $log_message, FILE_APPEND);
            };
            
            $writeLog("Début de la vérification du médecin ID: " . $this->id);
            
            // Mettre à jour le statut de vérification
            $query = "UPDATE " . $this->table_name . " 
                     SET verification_status = 'verified' 
                     WHERE id = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(1, $this->id);
            
            if ($stmt->execute()) {
                $writeLog("Médecin ID: " . $this->id . " vérifié avec succès");
                
                // Envoyer un email de confirmation au médecin
                $this->sendVerificationConfirmationEmail();
                
                return true;
            } else {
                $writeLog("ERREUR: Échec de la vérification du médecin ID: " . $this->id);
                return false;
            }
        } catch (Exception $e) {
            $writeLog("ERREUR lors de la vérification du médecin: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Rejette un médecin (change son statut de vérification à 'rejected')
     * 
     * @param string $reason Raison du rejet
     * @return bool True si le rejet a réussi, False sinon
     */
    public function reject($reason = '') {
        try {
            // Fonction de log locale
            $writeLog = function($message) {
                $log_file = __DIR__ . '/../logs/debug.log';
                $timestamp = date('Y-m-d H:i:s');
                $log_message = "[$timestamp] $message\n";
                file_put_contents($log_file, $log_message, FILE_APPEND);
            };
            
            $writeLog("Début du rejet du médecin ID: " . $this->id);
            
            // Mettre à jour le statut de vérification
            $query = "UPDATE " . $this->table_name . " 
                     SET verification_status = 'rejected' 
                     WHERE id = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(1, $this->id);
            
            if ($stmt->execute()) {
                $writeLog("Médecin ID: " . $this->id . " rejeté avec succès");
                
                // Envoyer un email de rejet au médecin
                $this->sendRejectionEmail();
                
                return true;
            } else {
                $writeLog("ERREUR: Échec du rejet du médecin ID: " . $this->id);
                return false;
            }
        } catch (Exception $e) {
            $writeLog("ERREUR lors du rejet du médecin: " . $e->getMessage());
            return false;
        }
    }
} 