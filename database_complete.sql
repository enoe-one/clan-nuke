
CREATE DATABASE IF NOT EXISTS cfwt_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cfwt_db;

-- Table des utilisateurs admin
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'chef', 'etat_major', 'recruteur', 'moderateur') NOT NULL,
    must_change_password BOOLEAN DEFAULT TRUE,
    access_recruitment_player BOOLEAN DEFAULT FALSE,
    access_recruitment_faction BOOLEAN DEFAULT FALSE,
    access_edit_members BOOLEAN DEFAULT FALSE,
    access_moderation BOOLEAN DEFAULT FALSE,
    access_edit_site BOOLEAN DEFAULT FALSE,
    access_full BOOLEAN DEFAULT FALSE,
    access_create_accounts BOOLEAN DEFAULT FALSE,
    access_manage_legions BOOLEAN DEFAULT FALSE,
    access_reset_passwords BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des légions
CREATE TABLE legions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    chef_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chef_id) REFERENCES users(id)
);

-- Table des diplômes
CREATE TABLE diplomes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL,
    nom VARCHAR(255) NOT NULL,
    description TEXT,
    categorie ENUM('aerien', 'terrestre', 'aeronaval', 'formateur', 'elite') NOT NULL,
    niveau INT NOT NULL,
    prerequis VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des membres (joueurs)
CREATE TABLE members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    discord_pseudo VARCHAR(100) NOT NULL,
    roblox_pseudo VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    kdr DECIMAL(5,2) DEFAULT 0,
    grade VARCHAR(50) DEFAULT 'Soldat',
    rang VARCHAR(50) DEFAULT 'Recrue',
    legion_id INT,
    must_change_password BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (legion_id) REFERENCES legions(id)
);

-- Table de liaison membres-diplômes
CREATE TABLE member_diplomes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    diplome_id INT NOT NULL,
    obtained_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (diplome_id) REFERENCES diplomes(id) ON DELETE CASCADE
);

-- Table des candidatures membres
CREATE TABLE member_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    age INT NOT NULL,
    discord_pseudo VARCHAR(100) NOT NULL,
    roblox_pseudo VARCHAR(100) NOT NULL,
    rebirths INT NOT NULL,
    niveau INT NOT NULL,
    kdr DECIMAL(5,2) NOT NULL,
    recruteur VARCHAR(50) NOT NULL,
    recruteur_pseudo VARCHAR(100),
    vehicule_confiant VARCHAR(50) NOT NULL,
    vehicule_progresser VARCHAR(50) NOT NULL,
    motivation VARCHAR(50) NOT NULL,
    motivation_autre TEXT,
    message TEXT,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    reviewed_by INT,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    FOREIGN KEY (reviewed_by) REFERENCES users(id)
);

-- Table des candidatures factions
CREATE TABLE faction_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_faction VARCHAR(100) NOT NULL,
    chef_faction VARCHAR(100) NOT NULL,
    nombre_membres INT NOT NULL,
    evaluation INT NOT NULL,
    raison TEXT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    reviewed_by INT,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    FOREIGN KEY (reviewed_by) REFERENCES users(id)
);

-- Table des membres de faction
CREATE TABLE faction_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faction_id INT NOT NULL,
    pseudo VARCHAR(100) NOT NULL,
    niveau INT NOT NULL,
    FOREIGN KEY (faction_id) REFERENCES faction_applications(id) ON DELETE CASCADE
);

-- Table des logs admin
CREATE TABLE admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Table des signalements
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_name VARCHAR(100),
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending', 'in_progress', 'resolved', 'closed') DEFAULT 'pending',
    resolved_by INT,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    FOREIGN KEY (resolved_by) REFERENCES users(id)
);

-- ============================================
-- INSERTION DES DONNÉES PAR DÉFAUT
-- ============================================

-- Légions par défaut
INSERT INTO legions (nom, description) VALUES 
('Clan Nuke', 'Légion principale de la CFWT'),
('AFT', 'Alliance des Forces Terrestres');

-- ============================================
-- DIPLÔMES AÉRIENS
-- ============================================
INSERT INTO diplomes (code, nom, description, categorie, niveau, prerequis) VALUES
('PAMA', 'P.A.M.A — Pilote Aviation Mobile et Armée', 'Formation de base pour les pilotes militaires opérant dans des conditions variées, notamment sur hélicoptères de combat et avions légers.', 'aerien', 1, NULL),
('FAPT', 'F.A.P.T — Force Aéronautique Protection Terrestre', 'Unité spécialisée dans l''appui aérien rapproché, visant à protéger les troupes au sol lors d''opérations militaires.', 'aerien', 2, 'PAMA'),
('UPSR', 'U.P.S.R — Unité de Patrouille Stratégique ou Rapide', 'Division chargée de surveiller de vastes zones aériennes à haute altitude ou grande vitesse.', 'aerien', 2, 'PAMA'),
('FTMA', 'F.T.M.A — Force de Transport de Matériel et Armée', 'Unité de transport aérien de troupes, de blindés légers et de matériel stratégique.', 'aerien', 2, 'PAMA'),
('UEI', 'U.E.I — Unité d''Espionnage Intelligente', 'Unité des drones et avions furtifs pour collecter des renseignements en territoire ennemi.', 'aerien', 3, 'UPSR'),
('FBS', 'F.B.S — Force de Bombardement Stratégique', 'Division aérienne lourde équipée d''avions bombardiers pour cibles stratégiques.', 'aerien', 3, 'FTMA'),
('FEIR', 'F.E.I.R — Force d''Élite d''Intervention Rapide', 'Assure la sécurité aérienne de hauts gradés ou d''émissaires diplomatiques.', 'aerien', 3, 'FAPT'),
('FAPT_ELITE', 'F.A.P.T — Force Aéronautique Protection Terrestre (Élite)', 'Unité d''élite capable d''intervenir en quelques minutes avec précision maximale.', 'aerien', 4, 'FEIR');

-- ============================================
-- DIPLÔMES TERRESTRES
-- ============================================
INSERT INTO diplomes (code, nom, description, categorie, niveau, prerequis) VALUES
('UIT', 'U.I.T — Unité Intervention Terrestre', 'Forces de première ligne mobilisées pour sécuriser des zones hostiles.', 'terrestre', 1, NULL),
('FIR', 'F.I.R — Force d''Intervention Rapide', 'Déployée pour répondre aux menaces urgentes. Unité légère, mobile et entraînée.', 'terrestre', 2, 'UIT'),
('UPAA', 'U.P.A.A — Unité de Protection Anti-Aérienne', 'Neutralisation des menaces aériennes grâce à des batteries anti-aériennes.', 'terrestre', 2, 'UIT'),
('UTM', 'U.T.M — Unité Terrestre Motorisée', 'Unité mobile armée déployée rapidement grâce à des véhicules blindés légers.', 'terrestre', 2, 'UIT'),
('UPS', 'U.P.S — Unité de Parachutistes Stratégiques', 'Effectue des opérations par largage en profondeur sur le territoire ennemi.', 'terrestre', 2, 'FIR'),
('FIB', 'F.I.B — Force d''Intervention Blindée', 'Opère avec des chars et véhicules blindés lourds pour les assauts terrestres.', 'terrestre', 3, 'UTM'),
('UBT', 'U.B.T — Unité de Bombardement Terrestre', 'Unité d''artillerie lourde et de véhicules lance-missiles.', 'terrestre', 3, 'UPAA'),
('FEAT', 'F.E.A.T — Force d''Élite Anti-Terrestre', 'Regroupe les meilleurs éléments pour la destruction rapide de groupes ennemis.', 'terrestre', 4, 'FIB');

-- ============================================
-- DIPLÔMES AÉRONAVAL
-- ============================================
INSERT INTO diplomes (code, nom, description, categorie, niveau, prerequis) VALUES
('UIA', 'U.I.A — Unité d''Intervention Aéronaval', 'Unité d''intervention d''hélicoptères armés pouvant intervenir sur toute la carte.', 'aeronaval', 1, NULL),
('UM', 'U.M — Unité de Marins', 'Forces navales formées pour la navigation, la défense de navires et la sécurisation des ports.', 'aeronaval', 2, 'UIA'),
('UIN', 'U.I.N — Unité d''Intervention Navale', 'Intervient pour aider un navire attaqué. Peut intervenir en quelques minutes.', 'aeronaval', 2, 'UIA'),
('FTTA', 'F.T.T.A — Force de Transport de Troupe Aéronavale', 'Transport de soldats via hélicoptères pour les débarquements maritimes.', 'aeronaval', 2, 'UIA'),
('UBN', 'U.B.N — Unité de Bombardement Naval', 'Manie des batteries lourdes embarquées pour bombarder des cibles côtières.', 'aeronaval', 3, 'UM'),
('USA', 'U.S.A — Unité de Soutien Aérien', 'Fournit un appui armé aux unités navales, terrestres ou aériennes.', 'aeronaval', 3, 'FTTA'),
('FDUA', 'F.D.U.A — Force de Destruction d''Unités Aéronavales', 'Équipe d''élite responsable de la traque et neutralisation de navires et avions ennemis.', 'aeronaval', 4, 'UBN');

-- ============================================
-- DIPLÔMES FORMATEURS
-- ============================================
INSERT INTO diplomes (code, nom, description, categorie, niveau, prerequis) VALUES
('FORM_BLINDES', 'Formateur de pilote de véhicule terrestre blindage', 'Formateur qualifié pour enseigner le pilotage de véhicules blindés lourds et légers.', 'formateur', 3, 'FIB'),
('FORM_HELI', 'Formateur de pilote d''hélicoptère', 'Formateur qualifié pour enseigner le pilotage d''hélicoptères de combat.', 'formateur', 3, 'UIA'),
('FORM_AVION', 'Formateur de pilote d''avions', 'Formateur qualifié pour enseigner le pilotage d''avions militaires.', 'formateur', 3, 'PAMA'),
('FORM_AERO', 'Formateur de pilote aéroglisseur', 'Formateur qualifié pour enseigner le pilotage d''aéroglisseurs.', 'formateur', 3, 'FTTA'),
('FORM_NAVIRE', 'Formateur de marin et commandant de bord pour navire', 'Formateur qualifié pour enseigner la navigation et le commandement de navires.', 'formateur', 3, 'UM'),
('FORM_SOUSMARIN', 'Formateur de sous-marin', 'Formateur qualifié pour enseigner le pilotage de sous-marins.', 'formateur', 3, 'UIN');

-- ============================================
-- DIPLÔMES FORCES D'ÉLITE
-- ============================================
INSERT INTO diplomes (code, nom, description, categorie, niveau, prerequis) VALUES
('FEIAT', 'F.E.I.A.T — Force d''Élite d''Intervention Anti-Terrestre', 'Opérateurs spéciaux entraînés pour détruire les forces terrestres ennemies.', 'elite', 4, 'FEAT'),
('FEIAN', 'F.E.I.A.N — Force d''Élite d''Intervention Anti-Navale', 'Neutralise les installations maritimes par des missions commandos amphibies.', 'elite', 4, 'FDUA'),
('FEIAA', 'F.E.I.A.A — Force d''Élite d''Intervention Anti-Aérienne', 'Force spéciale entraînée pour détruire les unités aériennes ennemies.', 'elite', 4, 'FAPT_ELITE'),
('UPEM', 'U.P.E.M — Unité de Protection de l''État-Major', 'Défend les officiers supérieurs et les centres de commandement.', 'elite', 5, 'FEIAT'),
('FSCN', 'F.S.C.N — Force Spéciale du Clan Nuke', 'Unité la plus secrète et puissante pour missions à très haut risque.', 'elite', 5, 'UPEM');

-- ============================================
-- FIN DU SCRIPT SQL
-- Total: 2 légions + 35 diplômes
-- ============================================
