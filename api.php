<?php
// api.php - users table + one-to-one chat + chat rooms
header('Content-Type: application/json');
set_exception_handler(function ($e) { echo json_encode(['ok' => false, 'error' => $e->getMessage()]); });

// ---- DB connection (XAMPP/WAMP default: root, no password) ----
$pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
// Auto-setup (remove these lines once everything works, for speed)
$pdo->exec("CREATE DATABASE IF NOT EXISTS chat_app");
$pdo->exec("USE chat_app");
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB");
$pdo->exec("CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB");
$pdo->exec("CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id   INT NOT NULL,
    receiver_id INT NULL,          -- filled for one-to-one chat
    room_id     INT NULL,          -- filled for room chat
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id)   REFERENCES users(id),
    FOREIGN KEY (receiver_id) REFERENCES users(id),
    FOREIGN KEY (room_id)     REFERENCES rooms(id)
) ENGINE=InnoDB");

// Find a row by name, or create it (used for users and rooms)
function findOrCreate($pdo, $table, $col, $val) {
    $st = $pdo->prepare("SELECT id FROM $table WHERE $col = ?");
    $st->execute([$val]);
    $id = $st->fetchColumn();
    if (!$id) {
        $pdo->prepare("INSERT INTO $table ($col) VALUES (?)")->execute([$val]);
        $id = $pdo->lastInsertId();
    }
    return (int)$id;
}

$action = $_REQUEST['action'] ?? '';

// ---- start: login by username and pick who/where to chat ----
if ($action === 'start') {
    $username = trim($_POST['username'] ?? '');
    $target   = trim($_POST['target'] ?? '');
    $mode     = ($_POST['mode'] ?? '') === 'room' ? 'room' : 'user';
    if ($username === '' || $target === '') { echo json_encode(['ok' => false, 'error' => 'fill all fields']); exit; }
    $meId = findOrCreate($pdo, 'users', 'username', $username);
    $st = $pdo->prepare("SELECT COALESCE(MAX(id),0) FROM messages WHERE receiver_id=? AND room_id IS NULL");
    $st->execute([$meId]);
    echo json_encode([
        'ok'        => true,
        'inbox_id'  => (int)$st->fetchColumn(),
        'me_id'     => $meId,
        'target_id' => $mode === 'room'
                        ? findOrCreate($pdo, 'rooms', 'name', $target)
                        : findOrCreate($pdo, 'users', 'username', $target),
    ]);
    exit;
}

// ---- common params for send / new / old ----
$me   = (int)($_REQUEST['me_id'] ?? 0);
$t    = (int)($_REQUEST['target_id'] ?? 0);
$mode = ($_REQUEST['mode'] ?? '') === 'room' ? 'room' : 'user';

if ($mode === 'room') {
    $where = "m.room_id = ?";
    $wp    = [$t];
} else {
    $where = "m.room_id IS NULL AND ((m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?))";
    $wp    = [$me, $t, $t, $me];
}
$select = "SELECT m.id, m.sender_id, u.username AS sender, m.message, m.created_at
           FROM messages m JOIN users u ON u.id = m.sender_id";

if ($action === 'send') {
    $text = trim($_POST['message'] ?? '');
    if ($text === '') { echo json_encode(['ok' => false]); exit; }
    $st = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, room_id, message) VALUES (?,?,?,?)");
    $st->execute($mode === 'room' ? [$me, null, $t, $text] : [$me, $t, null, $text]);
    echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);

} elseif ($action === 'inbox') {        // new private messages sent to me, from anyone
    $after = (int)($_GET['after'] ?? 0);
    $st = $pdo->prepare("SELECT m.id, m.sender_id, u.username AS sender
                         FROM messages m JOIN users u ON u.id = m.sender_id
                         WHERE m.receiver_id = ? AND m.room_id IS NULL AND m.id > ? ORDER BY m.id ASC");
    $st->execute([$me, $after]);
    echo json_encode($st->fetchAll());

} elseif ($action === 'new') {          // polling: only messages newer than 'after'
    $after = (int)($_GET['after'] ?? 0);
    $st = $pdo->prepare("$select WHERE $where AND m.id > ? ORDER BY m.id ASC");
    $st->execute([...$wp, $after]);
    echo json_encode($st->fetchAll());

} elseif ($action === 'old') {          // history: 10 messages older than 'before' (0 = latest 10)
    $before = (int)($_GET['before'] ?? 0);
    $st = $pdo->prepare("SELECT * FROM (
            $select WHERE $where AND (? = 0 OR m.id < ?) ORDER BY m.id DESC LIMIT 10
        ) t ORDER BY id ASC");
    $st->execute([...$wp, $before, $before]);
    echo json_encode($st->fetchAll());
}