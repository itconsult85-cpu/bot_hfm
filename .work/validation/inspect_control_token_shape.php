<?php
$projectRoot = dirname(__DIR__, 2);
$config = parse_ini_file($projectRoot . DIRECTORY_SEPARATOR . '.env', false, INI_SCANNER_RAW);
if (!is_array($config)) {
    fwrite(STDERR, "Could not parse the project environment file.\n");
    exit(1);
}
$host = $config['database.default.hostname'] ?? $config['DB_HOST'] ?? '127.0.0.1';
$database = $config['database.default.database'] ?? $config['DB_NAME'] ?? '';
$user = $config['database.default.username'] ?? $config['DB_USER'] ?? '';
$password = $config['database.default.password'] ?? $config['DB_PASSWORD'] ?? '';
$port = (int) ($config['database.default.port'] ?? $config['DB_PORT'] ?? 3306);
$db = @new mysqli($host, $user, $password, $database, $port);
if ($db->connect_error) {
    fwrite(STDERR, "Local CI4 database connection failed.\n");
    exit(2);
}
$stmt = $db->prepare("SELECT id, key_name, key_value FROM bot_globals WHERE UPPER(TRIM(key_name)) = ? ORDER BY id ASC");
$key = 'BOT_CONTROL_TOKEN';
$stmt->bind_param('s', $key);
$stmt->execute();
$result = $stmt->get_result();
$count = 0;
$values = [];
$hasWhitespace = false;
$hasNonCanonicalKey = false;
while ($row = $result->fetch_assoc()) {
    $count++;
    $values[] = trim((string) $row['key_value']);
    $hasWhitespace = $hasWhitespace || ((string) $row['key_value'] !== trim((string) $row['key_value']));
    $hasNonCanonicalKey = $hasNonCanonicalKey || ((string) $row['key_name'] !== 'BOT_CONTROL_TOKEN');
}
$distinctValues = count(array_unique($values));
echo json_encode([
    'database_reachable' => true,
    'token_rows' => $count,
    'distinct_trimmed_values' => $distinctValues,
    'has_outer_whitespace' => $hasWhitespace,
    'has_noncanonical_key_name' => $hasNonCanonicalKey,
], JSON_PRETTY_PRINT) . PHP_EOL;
