-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 30 mai 2025 à 16:26
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `medappdb`
--

-- --------------------------------------------------------

--
-- Structure de la table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `datenais` date NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `password` varchar(500) NOT NULL,
  `role` varchar(100) NOT NULL,
  `verification_status` enum('pending','verified','rejected') DEFAULT 'verified'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `admin`
--

INSERT INTO `admin` (`id`, `nom`, `prenom`, `datenais`, `email`, `contact`, `password`, `role`, `verification_status`) VALUES
(3, 'Admin', 'System', '1990-01-01', 'admin@medapp.com', '+1234567890', '$2y$10$GRJ5HoZoxVSRW31lxqsto.sO4GTMifnXPaLheEzkpmRFPaCAJtaEy', 'admin', 'verified');

-- --------------------------------------------------------

--
-- Structure de la table `carnetsante`
--

CREATE TABLE `carnetsante` (
  `id` int(11) NOT NULL,
  `id_patient` int(11) NOT NULL,
  `groupesanguin` varchar(10) DEFAULT NULL,
  `taille` decimal(5,2) DEFAULT NULL,
  `poids` decimal(5,2) DEFAULT NULL,
  `allergie` text DEFAULT NULL,
  `electrophorese` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `carnetsante`
--

INSERT INTO `carnetsante` (`id`, `id_patient`, `groupesanguin`, `taille`, `poids`, `allergie`, `electrophorese`, `created_at`, `updated_at`) VALUES
(4, 4, '0+', 165.00, 80.00, 'tomate', 'A', '2025-05-23 14:31:25', '2025-05-25 12:55:28');

-- --------------------------------------------------------

--
-- Structure de la table `consultation`
--

CREATE TABLE `consultation` (
  `id` int(11) NOT NULL,
  `id_patient` int(11) NOT NULL,
  `id_medecin` int(11) NOT NULL,
  `date_consultation` datetime NOT NULL,
  `motif` text NOT NULL,
  `antecedents` text DEFAULT NULL,
  `examen_clinique` text NOT NULL,
  `diagnostic` text NOT NULL,
  `traitement` text DEFAULT NULL,
  `recommandations` text DEFAULT NULL,
  `prochain_rdv` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `consultation`
--

INSERT INTO `consultation` (`id`, `id_patient`, `id_medecin`, `date_consultation`, `motif`, `antecedents`, `examen_clinique`, `diagnostic`, `traitement`, `recommandations`, `prochain_rdv`, `created_at`, `updated_at`) VALUES
(1, 4, 12, '2025-05-23 15:30:51', 'maux de tete', 'néant', 'néant', 'palu', 'dolimex', 'repos', NULL, '2025-05-23 14:30:51', '2025-05-23 14:30:51'),
(2, 23, 12, '2025-05-27 01:23:21', 'SYERTUI', 'TRUUTY5F', 'JTRYUIYYTDU', 'FRTUYEFRuctyèyu', 'iuog', '', NULL, '2025-05-27 00:23:21', '2025-05-27 00:23:21');

-- --------------------------------------------------------

--
-- Structure de la table `dossiers_medicaux`
--

CREATE TABLE `dossiers_medicaux` (
  `id` int(11) NOT NULL,
  `id_patient` int(11) NOT NULL,
  `antecedents` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `traitements` text DEFAULT NULL,
  `derniere_maj` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `fichemed`
--

CREATE TABLE `fichemed` (
  `id` int(11) NOT NULL,
  `id_patient` int(11) NOT NULL,
  `id_profil` int(11) NOT NULL,
  `id_carnet` int(11) NOT NULL,
  `lieu_naissance` varchar(100) DEFAULT NULL,
  `situation_familiale` varchar(20) DEFAULT NULL,
  `enfants` int(11) DEFAULT NULL,
  `grossesses` int(11) DEFAULT NULL,
  `num_secu` varchar(20) DEFAULT NULL,
  `groupe_sanguin` varchar(10) DEFAULT NULL,
  `medecin_traitant` varchar(100) DEFAULT NULL,
  `Assurance` varchar(100) DEFAULT NULL,
  `antecedents_familiaux` text DEFAULT NULL,
  `maladies_infantiles` text DEFAULT NULL,
  `antecedents_medicaux` text DEFAULT NULL,
  `antecedents_chirurgicaux` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `intolerance_medicament` text DEFAULT NULL,
  `traitement_regulier` text DEFAULT NULL,
  `vaccins` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `google_tokens`
--

CREATE TABLE `google_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `access_token` text NOT NULL,
  `refresh_token` text DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `hopitaux`
--

CREATE TABLE `hopitaux` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `localisation` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `success` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `medecin`
--

CREATE TABLE `medecin` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `datenais` date NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'medecin',
  `num` varchar(20) NOT NULL,
  `diplome` varchar(255) NOT NULL,
  `idspecialite` int(11) DEFAULT NULL,
  `verification_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `verification_token` varchar(64) DEFAULT NULL,
  `verification_token_expires` datetime DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `remember_token` varchar(64) DEFAULT NULL,
  `remember_token_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `medecin`
--

INSERT INTO `medecin` (`id`, `nom`, `prenom`, `datenais`, `email`, `contact`, `password`, `role`, `num`, `diplome`, `idspecialite`, `verification_status`, `verification_token`, `verification_token_expires`, `reset_token`, `reset_token_expires`, `remember_token`, `remember_token_expires`, `created_at`) VALUES
(12, 'Melvine', 'AGOMADJE', '2010-05-22', 'melvineyemadje@gmail.com', '+229 01 57 86 69 59', '$2y$10$qQvyT5JGkvYa5cyXbaU9M.qdPNiAUaAkDswIlx6cMrJ/roqKHLWJ.', 'medecin', '948795038', '', 6, 'verified', NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-23 15:59:45'),
(21, 'BOKO', 'John', '2010-05-07', 'fridaroot35@gmail.com', '+2290157866959', '$2y$10$OdBySRZENVV5/kR777pqseTNX9La42nlhpfhH6JHIUwzxvis3l9bu', 'medecin', '46578976345678690', 'diplome_1748254272_68343e40acd2e.png', 5, 'verified', NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-26 10:11:12'),
(22, 'SOSSOU', 'Fabrice', '1980-03-12', 'fabrice.sossou@medapp.bj', '22961000000', '$2y$10$exampleHASHmotdepasse', 'medecin', 'MDC001', 'img1.jpg', 1, 'verified', NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-26 16:22:40'),
(23, 'KIKI', 'Romuald', '1975-07-20', 'romuald.kiki@medapp.bj', '22962000000', '$2y$10$exampleHASHmotdepasse', 'medecin', 'MDC002', 'diplome.jpg', 3, 'verified', NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-26 16:22:40'),
(24, 'AYABA', 'Ginette', '1985-09-05', 'ginette.ayaba@medapp.bj', '22963000000', '$2y$10$exampleHASHmotdepasse', 'medecin', 'MDC003', '', 2, 'rejected', NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-26 16:22:40');

-- --------------------------------------------------------

--
-- Structure de la table `medecin_history`
--

CREATE TABLE `medecin_history` (
  `id` int(11) NOT NULL,
  `medecin_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `admin_name` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `medecin_history`
--

INSERT INTO `medecin_history` (`id`, `medecin_id`, `action`, `details`, `admin_id`, `admin_name`, `created_at`) VALUES
(1, 21, 'verification', 'Compte vérifié et activé par l\'administrateur.', 3, 'System Admin', '2025-05-26 11:35:56'),
(2, 24, 'rejection', 'Compte rejeté par l\'administrateur. Raison : Non spécifiée', 3, 'System Admin', '2025-05-26 17:23:47');

-- --------------------------------------------------------

--
-- Structure de la table `medicament`
--

CREATE TABLE `medicament` (
  `id` int(11) NOT NULL,
  `id_ordonnance` int(11) NOT NULL,
  `nom_medicament` varchar(255) NOT NULL,
  `dosage` varchar(100) NOT NULL,
  `frequence` varchar(100) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `contenu` text NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `date_envoi` datetime NOT NULL DEFAULT current_timestamp(),
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `sender_type` enum('patient','medecin') NOT NULL,
  `lu` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `messages`
--

INSERT INTO `messages` (`id`, `contenu`, `image_url`, `date_envoi`, `sender_id`, `receiver_id`, `sender_type`, `lu`) VALUES
(1, 'salut', NULL, '2025-05-23 15:27:30', 4, 12, 'patient', 1),
(2, 'salut', NULL, '2025-05-23 15:27:35', 4, 12, 'patient', 1),
(3, 'comment', NULL, '2025-05-23 15:27:53', 12, 4, 'medecin', 1),
(4, 'bien', NULL, '2025-05-23 15:28:01', 12, 4, 'medecin', 1),
(5, 'cc', NULL, '2025-05-25 14:27:22', 5, 12, 'patient', 0),
(6, 'oui\r\n', NULL, '2025-05-28 17:04:53', 12, 23, 'medecin', 0),
(7, 'oui\r\n', NULL, '2025-05-28 17:23:11', 12, 23, 'medecin', 0),
(8, 'cc', NULL, '2025-05-28 21:38:54', 4, 21, 'patient', 0),
(9, '', '/uploads/messages/msg_683786e7a317a_1748469479.jpg', '2025-05-28 22:57:59', 12, 4, 'medecin', 1),
(10, 'C4E\r\n', NULL, '2025-05-28 22:58:39', 12, 4, 'medecin', 1),
(11, 'CC', NULL, '2025-05-28 22:58:54', 4, 12, 'patient', 1),
(12, 'C4E\r\n', NULL, '2025-05-28 23:05:35', 12, 4, 'medecin', 1),
(13, 'cc', NULL, '2025-05-28 23:05:52', 4, 12, 'patient', 1),
(14, 'doi,n', NULL, '2025-05-28 23:06:05', 4, 12, 'patient', 1),
(15, 'OUI', NULL, '2025-05-28 23:18:57', 4, 21, 'patient', 0),
(16, '', '/uploads/messages/msg_68378be69ca19_1748470758.png', '2025-05-28 23:19:18', 4, 21, 'patient', 0),
(17, 'v', NULL, '2025-05-28 23:22:58', 4, 21, 'patient', 0),
(18, 'h', NULL, '2025-05-28 23:23:06', 4, 21, 'patient', 0),
(19, 'h', NULL, '2025-05-29 00:08:50', 4, 21, 'patient', 0),
(20, 'ok', NULL, '2025-05-29 00:08:58', 4, 21, 'patient', 0);

-- --------------------------------------------------------

--
-- Structure de la table `ordonnance`
--

CREATE TABLE `ordonnance` (
  `id` int(11) NOT NULL,
  `idmedecin` int(11) NOT NULL,
  `idpatient` int(11) NOT NULL,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
  `date_validite` date NOT NULL,
  `medicaments` text NOT NULL,
  `posologie` text NOT NULL,
  `quantite` text NOT NULL,
  `duree_medicament` text NOT NULL,
  `duree_traitement` varchar(50) NOT NULL,
  `instructions` text DEFAULT NULL,
  `signature` varchar(255) DEFAULT NULL,
  `renouvellement` tinyint(1) DEFAULT 0,
  `nombre_renouvellements` int(11) DEFAULT 0,
  `statut` enum('active','expiree','annulee') NOT NULL DEFAULT 'active',
  `signature_medecin` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `ordonnance`
--

INSERT INTO `ordonnance` (`id`, `idmedecin`, `idpatient`, `date_creation`, `date_validite`, `medicaments`, `posologie`, `quantite`, `duree_medicament`, `duree_traitement`, `instructions`, `signature`, `renouvellement`, `nombre_renouvellements`, `statut`, `signature_medecin`, `created_at`, `updated_at`) VALUES
(1, 12, 4, '2025-05-25 14:10:56', '2025-05-31', 'Para', '12', '30', '10', '2semaine', '', 'uploads/signatures/signature_1_1748178692.png', 0, 0, 'active', NULL, '2025-05-25 13:10:56', '2025-05-25 13:11:32'),
(2, 12, 23, '2025-05-27 01:05:14', '2025-05-29', 'dolimex', '1', '21', '12', '5', 'NEANT', 'uploads/signatures/signature_2_1748304376.png', 0, 0, 'active', NULL, '2025-05-27 00:05:14', '2025-05-27 00:06:16'),
(3, 12, 4, '2025-05-28 17:55:59', '2025-05-31', 'ghkj', 'uygliuk', '445', '6', '34JOURS', '', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAABI0AAADGCAYAAABfGHCIAAAAAXNSR0IArs4c6QAAIABJREFUeF7t3QWUlsXbx/EfzdIgLSVKhyiCNCid0qIoIJ1KlyAqKd2NNCLdSJc0KNKIhIB0s7A07zuDu39AYJfdZ5994jvncBZ273vumc/c6znP5TXXhLtz994j0RBAAAEEEEAAAQQQQAABBBBAAAEEEHhCIBxBI94HBBBAA', 0, 0, 'active', NULL, '2025-05-28 16:55:59', '2025-05-28 16:55:59');

-- --------------------------------------------------------

--
-- Structure de la table `password_reset`
--

CREATE TABLE `password_reset` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expire_date` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `password_reset`
--

INSERT INTO `password_reset` (`id`, `email`, `token`, `expire_date`, `used`) VALUES
(1, 'elfridayemadje5@gmail.com', 'b5121697d021167cae77f695958cb5d74b25f15e7bbef5e3fa69b31eaa3a0cb4', '2025-05-23 18:12:59', 0),
(2, 'elfridayemadje5@gmail.com', '7ac00dc3ccedc9fdad693685db3cc48d542401fad1dacdba5923ccaf1e1508d7', '2025-05-23 18:13:01', 0),
(3, 'elfridayemadje5@gmail.com', 'dcbd3b1675f7ae181f9a26eedd7e1161dbb2463829041e12524a3e986e53fc5f', '2025-05-25 18:26:18', 0),
(4, 'elfridayemadje5@gmail.com', '727057fd9549dd6b34ab0850d1c9b93847d47f6047f71e71fe5c12928d0f4947', '2025-05-26 11:58:45', 0),
(5, 'elfridayemadje5@gmail.com', '1a78b6c11f970194c0d089549602251fe0eb6165e4c28116adcba9e75ff941d1', '2025-05-26 12:55:09', 0),
(6, 'elfridayemadje5@gmail.com', '0b1cf951b50716cb711005007bddf335b653f5244f3efe54fd54d9791783a669', '2025-05-26 12:57:41', 0);

-- --------------------------------------------------------

--
-- Structure de la table `patient`
--

CREATE TABLE `patient` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `datenais` date NOT NULL,
  `sexe` enum('M','F','A') NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'patient',
  `id_medecin` int(11) DEFAULT NULL,
  `verification_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `verification_token` varchar(64) DEFAULT NULL,
  `verification_token_expires` datetime DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `remember_token` varchar(64) DEFAULT NULL,
  `remember_token_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `patient`
--

INSERT INTO `patient` (`id`, `nom`, `prenom`, `datenais`, `sexe`, `email`, `contact`, `password`, `role`, `id_medecin`, `verification_status`, `verification_token`, `verification_token_expires`, `reset_token`, `reset_token_expires`, `remember_token`, `remember_token_expires`, `created_at`) VALUES
(4, 'YEMADJE ', 'Fleur', '2005-02-24', 'F', 'elfridayemadje5@gmail.com', '+229 01 57 86 69 59', '$2y$10$7lHGPP0wN69E4gJcu6FWQ.AtaC4/WjFtr7hXNC5ByaW8uyvJ1Q8ue', 'patient', 12, 'verified', NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-23 15:59:45'),
(21, 'AGBODJAN', 'Mireille', '1995-08-17', 'F', 'mireille.agbodjan@example.com', '22961001122', '$2y$10$V7EtOnrBvZ/PxE3JdUs5ge5GlkixK2yzzFrEjO.Kv1Uov7vLjVz0e', 'patient', 22, 'verified', NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-26 16:29:27'),
(22, 'TOGBE', 'Ulrich', '1990-12-04', 'M', 'ulrich.togbe@example.com', '22997004433', '$2y$10$V7EtOnrBvZ/PxE3JdUs5ge5GlkixK2yzzFrEjO.Kv1Uov7vLjVz0e', 'patient', 23, 'pending', 'a1b2c3d4e5f6', '2025-06-01 12:00:00', NULL, NULL, NULL, NULL, '2025-05-26 16:29:27'),
(23, 'HOUNTONDJI', 'Clarisse', '1998-05-23', 'F', 'clarisse.hountondji@example.com', '22995112233', '$2y$10$V7EtOnrBvZ/PxE3JdUs5ge5GlkixK2yzzFrEjO.Kv1Uov7vLjVz0e', 'patient', 12, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-26 16:29:27'),
(25, 'ZINSOU', 'Aristide', '1992-09-10', 'M', 'aristide.zinsou@example.com', '22994447788', '$2y$10$V7EtOnrBvZ/PxE3JdUs5ge5GlkixK2yzzFrEjO.Kv1Uov7vLjVz0e', 'patient', 21, 'rejected', NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-26 16:29:27');

-- --------------------------------------------------------

--
-- Structure de la table `pharmacie`
--

CREATE TABLE `pharmacie` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `localisation` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `pharmacie`
--

INSERT INTO `pharmacie` (`id`, `nom`, `localisation`) VALUES
(1, 'Pharmacie Saint Michel', 'Cotonou, Carrefour Zogbo, ├á c├┤t├® de l\'├®glise Saint Michel'),
(2, 'Pharmacie de la Paix', 'Abomey-Calavi, Tankp├¿, en face du supermarch├® Leader Price'),
(3, 'Pharmacie des Lagunes', 'Porto-Novo, Rue du march├® central, quartier Dj├¿gan-Kp├¿vi'),
(4, 'Pharmacie Universitaire', 'Cotonou, Campus UAC, Facult├® des Sciences de la Sant├®'),
(5, 'Pharmacie Etoile du Sud', 'Parakou, Quartier Zongo, ├á 200m du rond-point Bio Gu├¿ra'),
(6, 'Pharmacie Soleil', 'Bohicon, Route de Dassa, ├á proximit├® de la station Total'),
(7, 'Pharmacie le Bon Samaritain', 'Djougou, Rue du Lyc├®e, face ├á la mairie'),
(8, 'Pharmacie Centrale de Natitingou', 'Natitingou, Rue principale, ├á c├┤t├® du commissariat'),
(9, 'Pharmacie M├®dicale', 'Ouidah, Quartier Pahou, pr├¿s de l\'h├┤pital Saint Camille'),
(10, 'Pharmacie Renaissance', 'Lokossa, Place de l\'Ind├®pendance, face ├á l\'ancienne poste'),
(11, 'Pharmacie Camp Guézo', 'Cotonou, Quartier Camp Guézo'),
(12, 'Pharmacie Saint Michel', 'Cotonou, Boulevard Saint Michel'),
(13, 'Pharmacie des Cocotiers', 'Cotonou, Fidjrossè'),
(14, 'Pharmacie Akpakpa', 'Cotonou, Akpakpa PK3'),
(15, 'Pharmacie Le Littoral', 'Cotonou, Haie Vive'),
(16, 'Pharmacie Médicale Porto-Novo', 'Porto-Novo, Place Bayol'),
(17, 'Pharmacie Parana', 'Abomey-Calavi, Zogbadjè'),
(18, 'Pharmacie du Centre', 'Parakou, Quartier Zongo'),
(19, 'Pharmacie des Collines', 'Dassa-Zoumè, Centre-ville'),
(20, 'Pharmacie Lagune', 'Cotonou, Sainte Rita'),
(21, 'Pharmacie Jéricho', 'Cotonou, Quartier Jéricho'),
(22, 'Pharmacie Zoungoudo', 'Abomey-Calavi, Zoungoudo'),
(23, 'Pharmacie de l’Aéroport', 'Cotonou, Route de l’Aéroport'),
(24, 'Pharmacie Les Archanges', 'Cotonou, Agla'),
(25, 'Pharmacie Sainte Famille', 'Cotonou, Vedoko'),
(26, 'Pharmacie Adogléta', 'Cotonou, Adogléta'),
(27, 'Pharmacie Albarika', 'Parakou, Quartier Albarika'),
(28, 'Pharmacie Divine Providence', 'Bohicon, Centre-ville'),
(29, 'Pharmacie de Pobè', 'Pobè, Rue principale'),
(30, 'Pharmacie Gbégamey', 'Cotonou, Gbégamey');

-- --------------------------------------------------------

--
-- Structure de la table `prixconsultation`
--

CREATE TABLE `prixconsultation` (
  `id` int(11) NOT NULL,
  `prix` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `profilmedecin`
--

CREATE TABLE `profilmedecin` (
  `id` int(11) NOT NULL,
  `adresse` text DEFAULT NULL,
  `profession` varchar(100) DEFAULT NULL,
  `imgdiplome` text DEFAULT NULL,
  `disponibilite` text DEFAULT NULL,
  `idmedecin` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `profilmedecin`
--

INSERT INTO `profilmedecin` (`id`, `adresse`, `profession`, `imgdiplome`, `disponibilite`, `idmedecin`) VALUES
(1, 'pk', NULL, NULL, '', 12);

-- --------------------------------------------------------

--
-- Structure de la table `profilpatient`
--

CREATE TABLE `profilpatient` (
  `id` int(11) NOT NULL,
  `adresse` text DEFAULT NULL,
  `profession` varchar(100) DEFAULT NULL,
  `idpatient` int(11) NOT NULL,
  `idcarnetsante` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `profilpatient`
--

INSERT INTO `profilpatient` (`id`, `adresse`, `profession`, `idpatient`, `idcarnetsante`) VALUES
(1, 'cotonou', 'Etudiante', 4, 4);

-- --------------------------------------------------------

--
-- Structure de la table `rendezvous`
--

CREATE TABLE `rendezvous` (
  `id` int(11) NOT NULL,
  `dateheure` datetime NOT NULL,
  `statut` enum('en attente','accepté','refusé') DEFAULT 'en attente',
  `idmedecin` int(11) NOT NULL,
  `idpatient` int(11) NOT NULL,
  `idspecialite` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `rendezvous`
--

INSERT INTO `rendezvous` (`id`, `dateheure`, `statut`, `idmedecin`, `idpatient`, `idspecialite`) VALUES
(2, '2025-05-23 15:30:00', 'accepté', 12, 4, 6),
(4, '2025-05-31 15:00:00', 'en attente', 21, 4, 5);

-- --------------------------------------------------------

--
-- Structure de la table `sessions`
--

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `specialite`
--

CREATE TABLE `specialite` (
  `id` int(11) NOT NULL,
  `nomspecialite` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `specialite`
--

INSERT INTO `specialite` (`id`, `nomspecialite`) VALUES
(1, 'Cardiologie'),
(2, 'Gynécologie'),
(3, 'Neurologie'),
(4, 'Ophtalmologie'),
(5, 'ORL'),
(6, 'Pédiatrie'),
(7, 'Psychiatrie'),
(8, 'Radiologie'),
(9, 'Urologie');

-- --------------------------------------------------------

--
-- Structure de la table `typing_status`
--

CREATE TABLE `typing_status` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `sender_type` enum('patient','medecin') NOT NULL,
  `is_typing` tinyint(1) NOT NULL DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `typing_status`
--

INSERT INTO `typing_status` (`id`, `user_id`, `receiver_id`, `sender_type`, `is_typing`, `last_updated`) VALUES
(50, 4, 12, 'patient', 1, '2025-05-28 22:05:47');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `google_id` varchar(100) DEFAULT NULL,
  `role` enum('patient','medecin','admin') NOT NULL,
  `auth_method` enum('standard','google') DEFAULT 'standard',
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `vaccins`
--

CREATE TABLE `vaccins` (
  `id` int(11) NOT NULL,
  `id_patient` int(11) NOT NULL,
  `nom_vaccin` varchar(255) NOT NULL,
  `date_vaccination` date DEFAULT NULL,
  `date_rappel` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `carnetsante`
--
ALTER TABLE `carnetsante`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_patient` (`id_patient`);

--
-- Index pour la table `consultation`
--
ALTER TABLE `consultation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_patient` (`id_patient`),
  ADD KEY `id_medecin` (`id_medecin`);

--
-- Index pour la table `dossiers_medicaux`
--
ALTER TABLE `dossiers_medicaux`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_patient` (`id_patient`);

--
-- Index pour la table `fichemed`
--
ALTER TABLE `fichemed`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_patient` (`id_patient`),
  ADD KEY `id_profil` (`id_profil`),
  ADD KEY `id_carnet` (`id_carnet`);

--
-- Index pour la table `google_tokens`
--
ALTER TABLE `google_tokens`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `hopitaux`
--
ALTER TABLE `hopitaux`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `medecin`
--
ALTER TABLE `medecin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idspecialite` (`idspecialite`);

--
-- Index pour la table `medecin_history`
--
ALTER TABLE `medecin_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medecin_id` (`medecin_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Index pour la table `medicament`
--
ALTER TABLE `medicament`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_ordonnance` (`id_ordonnance`);

--
-- Index pour la table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sender` (`sender_id`,`sender_type`),
  ADD KEY `idx_receiver` (`receiver_id`),
  ADD KEY `idx_date` (`date_envoi`);

--
-- Index pour la table `ordonnance`
--
ALTER TABLE `ordonnance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idmedecin` (`idmedecin`),
  ADD KEY `idpatient` (`idpatient`);

--
-- Index pour la table `password_reset`
--
ALTER TABLE `password_reset`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `patient`
--
ALTER TABLE `patient`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `id_medecin` (`id_medecin`);

--
-- Index pour la table `pharmacie`
--
ALTER TABLE `pharmacie`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `prixconsultation`
--
ALTER TABLE `prixconsultation`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `profilmedecin`
--
ALTER TABLE `profilmedecin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idmedecin` (`idmedecin`);

--
-- Index pour la table `profilpatient`
--
ALTER TABLE `profilpatient`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idpatient` (`idpatient`),
  ADD KEY `idcarnetsante` (`idcarnetsante`);

--
-- Index pour la table `rendezvous`
--
ALTER TABLE `rendezvous`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idmedecin` (`idmedecin`),
  ADD KEY `idpatient` (`idpatient`),
  ADD KEY `idspecialite` (`idspecialite`);

--
-- Index pour la table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `specialite`
--
ALTER TABLE `specialite`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nomspecialite` (`nomspecialite`);

--
-- Index pour la table `typing_status`
--
ALTER TABLE `typing_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_typing_status` (`user_id`,`receiver_id`,`sender_type`),
  ADD KEY `idx_last_updated` (`last_updated`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `vaccins`
--
ALTER TABLE `vaccins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_patient` (`id_patient`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `carnetsante`
--
ALTER TABLE `carnetsante`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `consultation`
--
ALTER TABLE `consultation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `dossiers_medicaux`
--
ALTER TABLE `dossiers_medicaux`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `fichemed`
--
ALTER TABLE `fichemed`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `google_tokens`
--
ALTER TABLE `google_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `hopitaux`
--
ALTER TABLE `hopitaux`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `medecin`
--
ALTER TABLE `medecin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT pour la table `medecin_history`
--
ALTER TABLE `medecin_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `medicament`
--
ALTER TABLE `medicament`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT pour la table `ordonnance`
--
ALTER TABLE `ordonnance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `password_reset`
--
ALTER TABLE `password_reset`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `patient`
--
ALTER TABLE `patient`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT pour la table `pharmacie`
--
ALTER TABLE `pharmacie`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT pour la table `prixconsultation`
--
ALTER TABLE `prixconsultation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `profilmedecin`
--
ALTER TABLE `profilmedecin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `profilpatient`
--
ALTER TABLE `profilpatient`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `rendezvous`
--
ALTER TABLE `rendezvous`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `specialite`
--
ALTER TABLE `specialite`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT pour la table `typing_status`
--
ALTER TABLE `typing_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `vaccins`
--
ALTER TABLE `vaccins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `carnetsante`
--
ALTER TABLE `carnetsante`
  ADD CONSTRAINT `carnetsante_ibfk_1` FOREIGN KEY (`id_patient`) REFERENCES `patient` (`id`);

--
-- Contraintes pour la table `consultation`
--
ALTER TABLE `consultation`
  ADD CONSTRAINT `consultation_ibfk_1` FOREIGN KEY (`id_patient`) REFERENCES `patient` (`id`),
  ADD CONSTRAINT `consultation_ibfk_2` FOREIGN KEY (`id_medecin`) REFERENCES `medecin` (`id`);

--
-- Contraintes pour la table `dossiers_medicaux`
--
ALTER TABLE `dossiers_medicaux`
  ADD CONSTRAINT `dossiers_medicaux_ibfk_1` FOREIGN KEY (`id_patient`) REFERENCES `patient` (`id`);

--
-- Contraintes pour la table `fichemed`
--
ALTER TABLE `fichemed`
  ADD CONSTRAINT `fichemed_ibfk_1` FOREIGN KEY (`id_patient`) REFERENCES `patient` (`id`),
  ADD CONSTRAINT `fichemed_ibfk_2` FOREIGN KEY (`id_profil`) REFERENCES `profilpatient` (`id`),
  ADD CONSTRAINT `fichemed_ibfk_3` FOREIGN KEY (`id_carnet`) REFERENCES `carnetsante` (`id`);

--
-- Contraintes pour la table `medecin`
--
ALTER TABLE `medecin`
  ADD CONSTRAINT `medecin_ibfk_1` FOREIGN KEY (`idspecialite`) REFERENCES `specialite` (`id`);

--
-- Contraintes pour la table `medicament`
--
ALTER TABLE `medicament`
  ADD CONSTRAINT `medicament_ibfk_1` FOREIGN KEY (`id_ordonnance`) REFERENCES `ordonnance` (`id`);

--
-- Contraintes pour la table `ordonnance`
--
ALTER TABLE `ordonnance`
  ADD CONSTRAINT `ordonnance_ibfk_1` FOREIGN KEY (`idmedecin`) REFERENCES `medecin` (`id`),
  ADD CONSTRAINT `ordonnance_ibfk_2` FOREIGN KEY (`idpatient`) REFERENCES `patient` (`id`);

--
-- Contraintes pour la table `patient`
--
ALTER TABLE `patient`
  ADD CONSTRAINT `patient_ibfk_1` FOREIGN KEY (`id_medecin`) REFERENCES `medecin` (`id`);

--
-- Contraintes pour la table `profilmedecin`
--
ALTER TABLE `profilmedecin`
  ADD CONSTRAINT `profilmedecin_ibfk_1` FOREIGN KEY (`idmedecin`) REFERENCES `medecin` (`id`);

--
-- Contraintes pour la table `profilpatient`
--
ALTER TABLE `profilpatient`
  ADD CONSTRAINT `profilpatient_ibfk_1` FOREIGN KEY (`idpatient`) REFERENCES `patient` (`id`),
  ADD CONSTRAINT `profilpatient_ibfk_2` FOREIGN KEY (`idcarnetsante`) REFERENCES `carnetsante` (`id`);

--
-- Contraintes pour la table `rendezvous`
--
ALTER TABLE `rendezvous`
  ADD CONSTRAINT `rendezvous_ibfk_1` FOREIGN KEY (`idmedecin`) REFERENCES `medecin` (`id`),
  ADD CONSTRAINT `rendezvous_ibfk_2` FOREIGN KEY (`idpatient`) REFERENCES `patient` (`id`),
  ADD CONSTRAINT `rendezvous_ibfk_3` FOREIGN KEY (`idspecialite`) REFERENCES `specialite` (`id`);

--
-- Contraintes pour la table `vaccins`
--
ALTER TABLE `vaccins`
  ADD CONSTRAINT `vaccins_ibfk_1` FOREIGN KEY (`id_patient`) REFERENCES `patient` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
