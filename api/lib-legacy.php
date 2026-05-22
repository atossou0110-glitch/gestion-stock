<?php
declare(strict_types=1);

/**
 * Bibliothèque de fonctions legacy de db.php
 * Chargée seulement par bootstrap.php pour éviter les conflits
 */

// ─── Récupération des données ───

function fetch_materials(PDO $pdo): array
{
    ensure_material_image_column($pdo);
    $stmt = $pdo->query(
        "SELECT id, cat, name, description, quantity, unit_price, unit, notes, created_at, image_data
         FROM materials
         ORDER BY cat, name ASC"
    );

    return array_map('material_to_app', $stmt->fetchAll());
}

function fetch_orders(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT id, supplier_id, reference, status, date_created, date_ordered, date_received, total_price, notes
         FROM orders
         ORDER BY date_created DESC"
    );

    return array_map('order_to_app', $stmt->fetchAll());
}

function fetch_movements(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT id, material_id, mat_name, direction, quantity, unit, date_movement, date_created, notes, created_by
         FROM stock_movements
         ORDER BY date_created DESC"
    );

    return array_map('movement_to_app', $stmt->fetchAll());
}

function fetch_stock_history(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT id, material_id, mat_name, old_qty, new_qty, unit, direction, reason, actor, date_movement, created_at
         FROM stock_history
         ORDER BY created_at DESC
         LIMIT 200"
    );

    return $stmt->fetchAll();
}

function fetch_suppliers(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT id, name, contact_name, email, phone, notes
         FROM suppliers
         ORDER BY name ASC"
    );

    return array_map('supplier_to_app', $stmt->fetchAll());
}

function fetch_equipment(PDO $pdo): array
{
    ensure_equipment_table($pdo);

    $stmt = $pdo->query(
        "SELECT id, name, serial_number, category, status, assigned_to, assigned_office, purchase_date, notes, created_at, updated_at
         FROM equipment
         ORDER BY name ASC"
    );

    return array_map('equipment_to_app', $stmt->fetchAll());
}

function fetch_equipment_history(PDO $pdo, ?int $equipmentId = null): array
{
    ensure_equipment_history_table($pdo);

    if ($equipmentId !== null && $equipmentId > 0) {
        $stmt = $pdo->prepare(
            "SELECT id, equipment_id, event_type, equipment_name, serial_number, old_status, new_status,
                    old_assigned_to, new_assigned_to, old_assigned_office, new_assigned_office,
                    notes, actor_id, actor_name, actor_role,
                    DATE_FORMAT(event_date, '%d/%m/%Y') AS event_date_fr,
                    DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') AS created_at_fr
             FROM equipment_history
             WHERE equipment_id = :equipment_id
             ORDER BY event_date DESC, id DESC"
        );
        $stmt->execute([':equipment_id' => $equipmentId]);
    } else {
        $stmt = $pdo->query(
            "SELECT id, equipment_id, event_type, equipment_name, serial_number, old_status, new_status,
                    old_assigned_to, new_assigned_to, old_assigned_office, new_assigned_office,
                    notes, actor_id, actor_name, actor_role,
                    DATE_FORMAT(event_date, '%d/%m/%Y') AS event_date_fr,
                    DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') AS created_at_fr
             FROM equipment_history
             ORDER BY event_date DESC, id DESC
             LIMIT 300"
        );
    }

    return $stmt->fetchAll();
}

function fetch_settings(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT setting_name, setting_value FROM settings ORDER BY setting_name ASC");
    $rows = $stmt->fetchAll();
    $result = [];
    foreach ($rows as $row) {
        $result[$row['setting_name']] = $row['setting_value'];
    }
    return $result;
}

// ─── Transformations ───

if (!function_exists('material_to_app')) {
    function material_to_app(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'cat' => $row['cat'] ?? 'Accessoires',
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'qty' => (float) ($row['quantity'] ?? 0),
            'unitPrice' => (float) ($row['unit_price'] ?? 0),
            'unit' => $row['unit'] ?? 'pièce',
            'notes' => $row['notes'] ?? '',
            'createdAt' => $row['created_at'] ?? '',
            'imageData' => $row['image_data'] ?? null,
        ];
    }
}

if (!function_exists('order_to_app')) {
    function order_to_app(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'supplierId' => (int) ($row['supplier_id'] ?? 0),
            'ref' => $row['reference'] ?? '',
            'status' => $row['status'] ?? 'pending',
            'dateCreated' => $row['date_created'] ?? '',
            'dateOrdered' => $row['date_ordered'] ?? '',
            'dateReceived' => $row['date_received'] ?? '',
            'totalPrice' => (float) ($row['total_price'] ?? 0),
            'notes' => $row['notes'] ?? '',
        ];
    }
}

if (!function_exists('movement_to_app')) {
    function movement_to_app(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'materialId' => (int) $row['material_id'],
            'matName' => $row['mat_name'],
            'direction' => $row['direction'] ?? 'out',
            'qty' => (float) $row['quantity'],
            'unit' => $row['unit'],
            'dateMovement' => $row['date_movement'] ?? '',
            'dateCreated' => $row['date_created'] ?? '',
            'notes' => $row['notes'] ?? '',
            'createdBy' => $row['created_by'] ?? 'unknown',
        ];
    }
}

if (!function_exists('supplier_to_app')) {
    function supplier_to_app(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'contact' => $row['contact_name'] ?? '',
            'email' => $row['email'] ?? '',
            'phone' => $row['phone'] ?? '',
            'notes' => $row['notes'] ?? '',
        ];
    }
}

if (!function_exists('equipment_to_app')) {
    function equipment_to_app(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'serialNumber' => $row['serial_number'] ?? '',
            'category' => $row['category'] ?? 'Autre',
            'status' => $row['status'] ?? 'disponible',
            'assignedTo' => $row['assigned_to'] ?? '',
            'assignedOffice' => $row['assigned_office'] ?? '',
            'purchaseDate' => $row['purchase_date'] ?? '',
            'notes' => $row['notes'] ?? '',
            'createdAt' => $row['created_at'] ?? '',
            'updatedAt' => $row['updated_at'] ?? '',
        ];
    }
}

// ─── Ensure tables ───

if (!function_exists('ensure_equipment_table')) {
    function ensure_equipment_table(PDO $pdo): void
    {
        static $done = false;
        if ($done) return;

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS equipment (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT,
              name VARCHAR(255) NOT NULL,
              serial_number VARCHAR(100) NOT NULL,
              category VARCHAR(100) DEFAULT 'Autre',
              status ENUM('disponible', 'en_utilisation', 'maintenance', 'hors_service') DEFAULT 'disponible',
              assigned_to VARCHAR(100),
              assigned_office VARCHAR(100),
              purchase_date DATE,
              notes TEXT,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (id),
              UNIQUE KEY uq_serial (serial_number),
              INDEX idx_status (status),
              INDEX idx_office (assigned_office)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $done = true;
    }
}

if (!function_exists('ensure_equipment_history_table')) {
    function ensure_equipment_history_table(PDO $pdo): void
    {
        static $done = false;
        if ($done) return;

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS equipment_history (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT,
              equipment_id INT UNSIGNED NOT NULL,
              event_type VARCHAR(50),
              equipment_name VARCHAR(255),
              serial_number VARCHAR(100),
              old_status VARCHAR(50),
              new_status VARCHAR(50),
              old_assigned_to VARCHAR(100),
              new_assigned_to VARCHAR(100),
              old_assigned_office VARCHAR(100),
              new_assigned_office VARCHAR(100),
              notes TEXT,
              actor_id INT UNSIGNED,
              actor_name VARCHAR(100),
              actor_role VARCHAR(40),
              event_date DATE,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (id),
              FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
              INDEX idx_equipment (equipment_id),
              INDEX idx_date (event_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $done = true;
    }
}
