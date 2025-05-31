-- Ajout de la colonne image_url à la table messages
ALTER TABLE messages ADD COLUMN image_url VARCHAR(255) DEFAULT NULL AFTER contenu;
