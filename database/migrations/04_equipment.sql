-- Table pour les équipements durables (Matériels)
CREATE TABLE IF NOT EXISTS equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    serial_number VARCHAR(50) UNIQUE NOT NULL,
    category VARCHAR(50) NOT NULL, -- Ex: Viseuse, Ordinateur, Imprimante
    status ENUM('disponible', 'en_utilisation', 'maintenance', 'hors_service') DEFAULT 'disponible',
    assigned_to VARCHAR(100) DEFAULT NULL, -- Nom de la personne
    assigned_office VARCHAR(100) DEFAULT NULL, -- Nom du bureau
    purchase_date DATE DEFAULT NULL,
    warranty_end DATE DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_serial (serial_number),
    INDEX idx_status (status),
    INDEX idx_assigned (assigned_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table pour l'historique des mouvements et changements d'état
CREATE TABLE IF NOT EXISTS equipment_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    event_type ENUM('creation', 'affectation', 'retour', 'etat', 'maintenance', 'suppression') NOT NULL,
    user_name VARCHAR(100) DEFAULT NULL,
    old_value TEXT DEFAULT NULL, -- JSON ou texte décrivant l'état avant
    new_value TEXT DEFAULT NULL, -- JSON ou texte décrivant l'état après
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    INDEX idx_equipment (equipment_id),
    INDEX idx_event (event_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Données de démo (optionnel)
INSERT INTO equipment (name, serial_number, category, status, assigned_to, assigned_office, purchase_date) VALUES
('Viseuse Bosch', 'VS-2023-001', 'Outillage', 'disponible', NULL, NULL, '2023-01-15'),
('Ordinateur Dell', 'PC-2022-045', 'Informatique', 'en_utilisation', 'Jean Dupont', 'Bureau 101', '2022-06-10'),
('Imprimante HP', 'PR-2021-089', 'Informatique', 'maintenance', NULL, 'Secrétariat', '2021-11-20');
