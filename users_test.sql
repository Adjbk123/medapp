-- ============================================================
-- UTILISATEURS DE TEST - MedConnect
-- Généré le 2026-05-05
-- Mot de passe à utiliser : voir users_test.txt
-- ============================================================

USE medappdb;

-- 1. ADMIN
INSERT INTO admin (nom, prenom, datenais, email, contact, password, role) VALUES
('Admin', 'Super', '1990-01-01', 'admin@medconnect.com', '0600000001', '$2y$10$XZIVKp6Cg3OxUFUTR1PJouZk53/AuX1jlX2ffkF/hisBfD2wjuOqG', 'admin');

-- 2. MEDECIN (spécialité id=1 = Cardiologie, à adapter si besoin)
INSERT INTO medecin (nom, prenom, datenais, email, contact, num, password, role, idspecialite, verification_status) VALUES
('Dupont', 'Jean', '1980-05-15', 'medecin@medconnect.com', '0600000002', 'MED-001', '$2y$10$fEHwb4lJ/0SLOPZzRtgteu4JHUDjq2E4fuLRHKmNlERS77kfVqvJi', 'medecin', 1, 'verified');

-- 3. PATIENT
INSERT INTO patient (nom, prenom, datenais, sexe, email, contact, password, role, verification_status) VALUES
('Martin', 'Marie', '1995-08-20', 'F', 'patient@medconnect.com', '0600000003', '$2y$10$a/w6Fi5dliC/V6AX6jaN.eFXJeEj5PAoxm8ywPtUSPctZju9.1.Qq', 'patient', 'verified');
