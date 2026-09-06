<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$expectedToken = getenv('SYNC_TOKEN');
if (!$expectedToken) {
    http_response_code(500);
    echo json_encode(['error' => 'Server not configured']);
    exit;
}

$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!hash_equals('Bearer ' . $expectedToken, $auth)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['items']) || !is_array($data['items'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

$mysqli = new mysqli('localhost', 'DB_USERNAME', 'DB_PASSWORD', 'DB_NAME');
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}
$mysqli->set_charset('utf8mb4');

$synced = [];
$failed = [];

foreach ($data['items'] as $item) {
    $id = $item['id'] ?? null;
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $item['table_name'] ?? '');
    $action = $item['action'] ?? '';
    $payload = $item['payload'] ?? [];

    if (!$table || !in_array($action, ['insert', 'update', 'delete'], true) || !is_array($payload)) {
        $failed[] = ['id' => $id, 'reason' => 'Invalid item'];
        continue;
    }

    try {
        if ($action === 'insert') {
            $fields = [];
            $placeholders = [];
            $values = [];

            foreach ($payload as $key => $value) {
                $fields[] = '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $key) . '`';
                $placeholders[] = '?';
                $values[] = $value;
            }

            if (!$fields) {
                throw new Exception('No insert fields');
            }

            $sql = "INSERT INTO `$table` (" . implode(',', $fields) . ") VALUES (" . implode(',', $placeholders) . ")";
            $stmt = $mysqli->prepare($sql);
            if (!$stmt) {
                throw new Exception($mysqli->error);
            }

            $types = str_repeat('s', count($values));
            $stmt->bind_param($types, ...$values);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'update') {
            if (!isset($payload['id'])) {
                throw new Exception('Update requires id');
            }

            $set = [];
            $values = [];

            foreach ($payload as $key => $value) {
                if ($key === 'id') continue;
                $set[] = '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $key) . '` = ?';
                $values[] = $value;
            }

            $values[] = $payload['id'];

            $sql = "UPDATE `$table` SET " . implode(', ', $set) . " WHERE id = ?";
            $stmt = $mysqli->prepare($sql);
            if (!$stmt) {
                throw new Exception($mysqli->error);
            }

            $types = str_repeat('s', count($values));
            $stmt->bind_param($types, ...$values);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'delete') {
            $rowId = $payload['id'] ?? null;
            if (!$rowId) {
                throw new Exception('Delete requires id');
            }

            $stmt = $mysqli->prepare("DELETE FROM `$table` WHERE id = ?");
            $stmt->bind_param('i', $rowId);
            $stmt->execute();
            $stmt->close();
        }

        if ($id !== null) {
            $synced[] = (int) $id;
        }
    } catch (Exception $e) {
        $failed[] = ['id' => $id, 'reason' => $e->getMessage()];
    }
}

echo json_encode([
    'success' => true,
    'synced' => $synced,
    'failed' => $failed
]);