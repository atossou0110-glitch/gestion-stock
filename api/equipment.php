<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

try {
    $pdo = db();
    $user = require_auth($pdo);
    ensure_equipment_table($pdo);
    
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        json_response(['ok' => true, 'items' => fetch_equipment($pdo)]);
    }

    if ($method === 'POST') {
        if ($user['role'] !== 'admin') {
            json_response(['ok' => false, 'error' => 'Acces refuse pour ce role.'], 403);
        }

        $data = read_json();
        $name = trim((string) ($data['name'] ?? ''));
        $serialNumber = trim((string) ($data['serialNumber'] ?? ''));
        $category = trim((string) ($data['category'] ?? 'Autre'));
        $status = trim((string) ($data['status'] ?? 'disponible'));
        $assignedTo = trim((string) ($data['assignedTo'] ?? ''));
        $assignedOffice = trim((string) ($data['assignedOffice'] ?? ''));
        $purchaseDate = trim((string) ($data['purchaseDate'] ?? ''));
        $notes = trim((string) ($data['notes'] ?? ''));

        if ($name === '') {
            json_response(['ok' => false, 'error' => 'Le nom de l\'équipement est obligatoire.'], 400);
        }

        if ($serialNumber === '') {
            json_response(['ok' => false, 'error' => 'Le numéro de série est obligatoire.'], 400);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO equipment 
             (name, serial_number, category, status, assigned_to, assigned_office, purchase_date, notes)
             VALUES (:name, :serial_number, :category, :status, :assigned_to, :assigned_office, :purchase_date, :notes)'
        );
        $stmt->execute([
            ':name' => $name,
            ':serial_number' => $serialNumber,
            ':category' => $category,
            ':status' => $status,
            ':assigned_to' => $assignedTo !== '' ? $assignedTo : null,
            ':assigned_office' => $assignedOffice !== '' ? $assignedOffice : null,
            ':purchase_date' => $purchaseDate !== '' ? $purchaseDate : null,
            ':notes' => $notes !== '' ? $notes : null,
        ]);

        $id = (int) $pdo->lastInsertId();
        $stmt = $pdo->prepare(
            'SELECT id, name, serial_number, category, status, assigned_to, assigned_office, purchase_date, notes, created_at
             FROM equipment
             WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        $item = $stmt->fetch();

        log_equipment_history($pdo, [
            'event_type' => 'creation',
            'equipment_id' => $id,
            'equipment_name' => $item['name'],
            'serial_number' => $item['serial_number'],
            'new_status' => $item['status'],
            'assigned_to' => $item['assigned_to'],
            'assigned_office' => $item['assigned_office'],
            'notes' => 'Création de l\'équipement.',
        ]);

        json_response(['ok' => true, 'item' => equipment_to_app($item), 'history' => fetch_equipment_history($pdo)], 201);
    }

    if ($method === 'PATCH' || $method === 'PUT') {
        if (!in_array($user['role'], ['admin', 'moderateur_stock'], true)) {
            json_response(['ok' => false, 'error' => 'Acces refuse pour ce role.'], 403);
        }

        $data = read_json();
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            json_response(['ok' => false, 'error' => 'Identifiant équipement manquant.'], 400);
        }

        $stmt = $pdo->prepare(
            'SELECT id, name, serial_number, category, status, assigned_to, assigned_office, purchase_date, notes
             FROM equipment
             WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        $oldItem = $stmt->fetch();

        if (!$oldItem) {
            json_response(['ok' => false, 'error' => 'Équipement introuvable.'], 404);
        }

        $fields = [
            'name' => ['column' => 'name', 'type' => 'string'],
            'serialNumber' => ['column' => 'serial_number', 'type' => 'string'],
            'category' => ['column' => 'category', 'type' => 'string'],
            'status' => ['column' => 'status', 'type' => 'string'],
            'assignedTo' => ['column' => 'assigned_to', 'type' => 'string'],
            'assignedOffice' => ['column' => 'assigned_office', 'type' => 'string'],
            'purchaseDate' => ['column' => 'purchase_date', 'type' => 'string'],
            'notes' => ['column' => 'notes', 'type' => 'string'],
        ];

        $updates = [];
        $params = [':id' => $id];
        $hasAssignmentChange = false;
        $hasStatusChange = false;
        $oldAssignment = $oldItem['assigned_to'];
        $oldStatus = $oldItem['status'];

        foreach ($fields as $key => $field) {
            if (!array_key_exists($key, $data)) {
                continue;
            }

            $param = ':' . $key;
            $updates[] = $field['column'] . ' = ' . $param;
            $value = trim((string) $data[$key]);
            $params[$param] = $value !== '' ? $value : null;

            if ($key === 'assignedTo' || $key === 'assignedOffice') {
                $hasAssignmentChange = true;
            }
            if ($key === 'status') {
                $hasStatusChange = true;
            }
        }

        if ($updates !== []) {
            $stmt = $pdo->prepare('UPDATE equipment SET ' . implode(', ', $updates) . ' WHERE id = :id');
            $stmt->execute($params);
        }

        $stmt = $pdo->prepare(
            'SELECT id, name, serial_number, category, status, assigned_to, assigned_office, purchase_date, notes, created_at
             FROM equipment
             WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        $item = $stmt->fetch();

        if (!$item) {
            json_response(['ok' => false, 'error' => 'Équipement introuvable.'], 404);
        }

        if ($hasStatusChange && $oldStatus !== $item['status']) {
            log_equipment_history($pdo, [
                'event_type' => 'changement_etat',
                'equipment_id' => (int) $item['id'],
                'equipment_name' => $item['name'],
                'serial_number' => $item['serial_number'],
                'old_status' => $oldStatus,
                'new_status' => $item['status'],
                'notes' => 'Changement d\'état de l\'équipement.',
            ]);
        }

        if ($hasAssignmentChange) {
            $newAssignedTo = $item['assigned_to'];
            $newAssignedOffice = $item['assigned_office'];
            
            if ($oldAssignment !== $newAssignedTo || $oldItem['assigned_office'] !== $newAssignedOffice) {
                log_equipment_history($pdo, [
                    'event_type' => $newAssignedTo === null ? 'retour' : 'affectation',
                    'equipment_id' => (int) $item['id'],
                    'equipment_name' => $item['name'],
                    'serial_number' => $item['serial_number'],
                    'old_assigned_to' => $oldAssignment,
                    'new_assigned_to' => $newAssignedTo,
                    'old_assigned_office' => $oldItem['assigned_office'],
                    'new_assigned_office' => $newAssignedOffice,
                    'notes' => $newAssignedTo === null ? 'Retour de l\'équipement.' : 'Affectation de l\'équipement.',
                ]);
            }
        }

        json_response(['ok' => true, 'item' => equipment_to_app($item), 'history' => fetch_equipment_history($pdo)]);
    }

    if ($method === 'DELETE') {
        if ($user['role'] !== 'admin') {
            json_response(['ok' => false, 'error' => 'Acces refuse pour ce role.'], 403);
        }

        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            $data = read_json();
            $id = (int) ($data['id'] ?? 0);
        }

        if ($id <= 0) {
            json_response(['ok' => false, 'error' => 'Identifiant équipement manquant.'], 400);
        }

        $stmt = $pdo->prepare(
            'SELECT id, name, serial_number, status, assigned_to
             FROM equipment
             WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        $item = $stmt->fetch();

        if (!$item) {
            json_response(['ok' => false, 'error' => 'Équipement introuvable.'], 404);
        }

        if ($item['assigned_to'] !== null) {
            json_response(['ok' => false, 'error' => 'Impossible de supprimer un équipement affecté. Veuillez d\'abord le retourner.'], 400);
        }

        log_equipment_history($pdo, [
            'event_type' => 'suppression',
            'equipment_id' => (int) $item['id'],
            'equipment_name' => $item['name'],
            'serial_number' => $item['serial_number'],
            'notes' => 'Suppression de l\'équipement.',
        ]);

        $stmt = $pdo->prepare('DELETE FROM equipment WHERE id = :id');
        $stmt->execute([':id' => $id]);

        json_response(['ok' => true]);
    }

    json_response(['ok' => false, 'error' => 'Methode non autorisee.'], 405);
} catch (Throwable $e) {
    json_response([
        'ok' => false,
        'error' => 'Erreur serveur equipment.',
        'detail' => $e->getMessage(),
    ], 500);
}
