<?php
declare(strict_types=1);

/**
 * Script d'insertion d'équipements d'exemple
 * À exécuter une seule fois pour peupler la base de données
 */

require __DIR__ . '/bootstrap.php';

try {
    $pdo = db();
    ensure_equipment_table($pdo);
    
    $equipments = [
        [
            'name' => 'Ordinateur de bureau',
            'serial_number' => 'PC-2024-001',
            'category' => 'Informatique',
            'status' => 'disponible',
            'assigned_to' => 'Jean Dupont',
            'assigned_office' => 'Bureau principal',
            'purchase_date' => '2023-06-15',
            'notes' => 'Dell XPS 15, processeur i7, 16GB RAM'
        ],
        [
            'name' => 'Imprimante multifonction',
            'serial_number' => 'PRINTER-HP-001',
            'category' => 'Équipement',
            'status' => 'disponible',
            'assigned_to' => '',
            'assigned_office' => 'Atelier',
            'purchase_date' => '2022-09-20',
            'notes' => 'HP LaserJet Pro M428fdw - couleur recto-verso'
        ],
        [
            'name' => 'Visseuse électrique',
            'serial_number' => 'TOOL-MAKITA-001',
            'category' => 'Outils',
            'status' => 'en_utilisation',
            'assigned_to' => 'Pierre Martin',
            'assigned_office' => 'Atelier',
            'purchase_date' => '2024-01-10',
            'notes' => 'Makita 18V, batterie 3.0 Ah'
        ],
        [
            'name' => 'Perceuse visseuse sans fil',
            'serial_number' => 'DRILL-BOSCH-042',
            'category' => 'Outils',
            'status' => 'maintenance',
            'assigned_to' => '',
            'assigned_office' => 'Atelier',
            'purchase_date' => '2022-03-05',
            'notes' => 'Bosch GSR 180-LI - batterie faible, en réparation'
        ],
        [
            'name' => 'Scie sauteuse',
            'serial_number' => 'SAW-FESTOOL-003',
            'category' => 'Outils',
            'status' => 'disponible',
            'assigned_to' => '',
            'assigned_office' => 'Atelier',
            'purchase_date' => '2023-11-30',
            'notes' => 'Festool PS 420 EBQ - très bon état'
        ],
        [
            'name' => 'Tableau blanc interactif',
            'serial_number' => 'SMARTBOARD-001',
            'category' => 'Équipement',
            'status' => 'hors_service',
            'assigned_to' => '',
            'assigned_office' => 'Salle de réunion',
            'purchase_date' => '2021-04-12',
            'notes' => 'Écran tactile défaillant - remplaçant attendu'
        ],
        [
            'name' => 'Projecteur de présentation',
            'serial_number' => 'PROJ-EPSON-001',
            'category' => 'Équipement',
            'status' => 'disponible',
            'assigned_to' => '',
            'assigned_office' => 'Salle de réunion',
            'purchase_date' => '2023-08-22',
            'notes' => 'Epson EB-L735U - 7000 lumens'
        ],
        [
            'name' => 'Ordinateur portable',
            'serial_number' => 'LAPTOP-HP-2024',
            'category' => 'Informatique',
            'status' => 'en_utilisation',
            'assigned_to' => 'Marie Lefebvre',
            'assigned_office' => 'Bureau directeur',
            'purchase_date' => '2024-02-01',
            'notes' => 'HP ProBook 450 G9 - 16GB, i5'
        ],
        [
            'name' => 'Climatiseur mobile',
            'serial_number' => 'AC-DELONGHI-001',
            'category' => 'Climatisation',
            'status' => 'disponible',
            'assigned_to' => '',
            'assigned_office' => 'Atelier',
            'purchase_date' => '2023-05-14',
            'notes' => 'DeLonghi PACZ110 - 2500W'
        ],
        [
            'name' => 'Appareil photo professionnel',
            'serial_number' => 'CAMERA-CANON-001',
            'category' => 'Photographie',
            'status' => 'disponible',
            'assigned_to' => 'Sophie Bernard',
            'assigned_office' => 'Studio',
            'purchase_date' => '2022-12-10',
            'notes' => 'Canon EOS R5 - 45MP'
        ]
    ];
    
    $inserted = 0;
    
    foreach ($equipments as $eq) {
        $stmt = $pdo->prepare(
            'INSERT INTO equipment 
             (name, serial_number, category, status, assigned_to, assigned_office, purchase_date, notes)
             VALUES (:name, :serial_number, :category, :status, :assigned_to, :assigned_office, :purchase_date, :notes)'
        );
        
        $stmt->execute([
            ':name' => $eq['name'],
            ':serial_number' => $eq['serial_number'],
            ':category' => $eq['category'],
            ':status' => $eq['status'],
            ':assigned_to' => $eq['assigned_to'] !== '' ? $eq['assigned_to'] : null,
            ':assigned_office' => $eq['assigned_office'] !== '' ? $eq['assigned_office'] : null,
            ':purchase_date' => $eq['purchase_date'] !== '' ? $eq['purchase_date'] : null,
            ':notes' => $eq['notes'] !== '' ? $eq['notes'] : null,
        ]);
        
        $id = (int) $pdo->lastInsertId();
        
        // Enregistrer l'événement d'historique
        log_equipment_history($pdo, [
            'event_type' => 'creation',
            'equipment_id' => $id,
            'equipment_name' => $eq['name'],
            'serial_number' => $eq['serial_number'],
            'new_status' => $eq['status'],
            'assigned_to' => $eq['assigned_to'] ?: null,
            'assigned_office' => $eq['assigned_office'] ?: null,
            'notes' => 'Équipement inséré par seed-equipment.php',
        ]);
        
        $inserted++;
        echo "✓ {$eq['name']} ({$eq['serial_number']}) - ID: $id\n";
    }
    
    echo "\n✅ {$inserted} équipements insérés avec succès!\n";
    
} catch (Throwable $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
