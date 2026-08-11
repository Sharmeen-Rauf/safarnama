<?php
// config/db.php
// Setup PHP Native Session securely
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Simple zero-dependency .env parser
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $name = trim($parts[0]);
            $value = trim($parts[1]);
            
            // Strip surrounding quotes
            $value = preg_replace('/^[\'"]|[\'"]$/', '', $value);
            
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Database Credentials
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASSWORD') ?: (getenv('DB_PASS') ?: '');
$db_name = getenv('DB_NAME') ?: 'digital_mandi';
$db_port = getenv('DB_PORT') ?: 3306;

// SSL CA detection
$ssl_ca = getenv('DB_SSL_CA') ?: null;
if ($ssl_ca) {
    // Resolve relative paths if needed
    if (!empty($ssl_ca) && !file_exists($ssl_ca)) {
        $resolved = __DIR__ . '/../' . $ssl_ca;
        if (file_exists($resolved)) {
            $ssl_ca = $resolved;
        }
    }
}
if (!$ssl_ca && file_exists(__DIR__ . '/ca.pem')) {
    $ssl_ca = __DIR__ . '/ca.pem';
}

$conn = mysqli_init();
$connected = false;

if ($conn) {
    mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 3); // Short timeout to fail fast if DB is offline
    
    // Configure SSL CA cert if present (required for Aiven Cloud MySQL)
    if ($ssl_ca) {
        mysqli_ssl_set($conn, NULL, NULL, $ssl_ca, NULL, NULL);
    }
    
    // Connect
    $connected = @mysqli_real_connect(
        $conn,
        $db_host,
        $db_user,
        $db_pass,
        $db_name,
        $db_port,
        NULL,
        $ssl_ca ? MYSQLI_CLIENT_SSL : 0
    );
}

define('DB_MOCKED', !$connected);
define('JSON_DB_PATH', __DIR__ . '/../db_fallback.json');

// Initialize local JSON Database if connection is mocked/down
if (DB_MOCKED) {
    if (!file_exists(JSON_DB_PATH)) {
        $initialData = [
            "users" => [
                [
                    "id" => 1,
                    "full_name" => "Muhammad Ali",
                    "email" => "farmer@digitalmandi.com",
                    // Hashed password for 'password123'
                    "password" => '$2y$10$twqRhgDUG6hrqtcVAaCAWunVjBxttTdN2nKYe1HD9V4TQ9wQ/0xy2',
                    "mobile_number" => "03001234567",
                    "address" => "House 12, Street 3",
                    "city" => "Okara",
                    "province" => "Punjab",
                    "role" => "Farmer",
                    "created_at" => date('Y-m-d H:i:s')
                ],
                [
                    "id" => 2,
                    "full_name" => "Ahmed Khan",
                    "email" => "buyer@digitalmandi.com",
                    "password" => '$2y$10$twqRhgDUG6hrqtcVAaCAWunVjBxttTdN2nKYe1HD9V4TQ9wQ/0xy2',
                    "mobile_number" => "03217654321",
                    "address" => "Plot 45, Phase 5, DHA",
                    "city" => "Lahore",
                    "province" => "Punjab",
                    "role" => "Buyer",
                    "created_at" => date('Y-m-d H:i:s')
                ]
            ],
            "farms" => [
                [
                    "id" => 1,
                    "user_id" => 1,
                    "farm_name" => "Ali Organic Farms",
                    "farm_location" => "Okara Bypass",
                    "crop_type" => "Wheat & Rice"
                ]
            ]
        ];
        file_put_contents(JSON_DB_PATH, json_encode($initialData, JSON_PRETTY_PRINT));
    }
} else {
    // If successfully connected to live MySQL (e.g. Aiven), check and auto-initialize tables
    $table_check = @mysqli_query($conn, "SHOW TABLES LIKE 'users'");
    if ($table_check && mysqli_num_rows($table_check) === 0) {
        $schema_file = __DIR__ . '/../schema.sql';
        if (file_exists($schema_file)) {
            $schema_sql = file_get_contents($schema_file);
            
            // Run multi-query
            if (mysqli_multi_query($conn, $schema_sql)) {
                do {
                    // Consume results
                    if ($result = mysqli_store_result($conn)) {
                        mysqli_free_result($result);
                    }
                } while (mysqli_next_result($conn));
            }
        }
    }
}

// Read JSON database helper
function get_json_db() {
    if (!file_exists(JSON_DB_PATH)) return ["users" => [], "farms" => []];
    return json_decode(file_get_contents(JSON_DB_PATH), true);
}

// Write JSON database helper
function save_json_db($data) {
    file_put_contents(JSON_DB_PATH, json_encode($data, JSON_PRETTY_PRINT));
}

/**
 * Abstraction layer to execute queries. Handles prepared statements for MySQL and emulates them for local JSON DB.
 *
 * @param string $sql SQL string with ? placeholders
 * @param array $params Array of parameters to bind
 * @param string $types Type string e.g. "ssi"
 * @return array|bool|array-associative Query results or operation status metadata
 */
function db_query($sql, $params = [], $types = '') {
    global $conn;
    
    if (!DB_MOCKED) {
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return false;
        }
        
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        
        $success = mysqli_stmt_execute($stmt);
        if (!$success) {
            mysqli_stmt_close($stmt);
            return false;
        }
        
        $result = mysqli_stmt_get_result($stmt);
        
        // INSERT, UPDATE, DELETE queries
        if ($result === false) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            $insert_id = mysqli_stmt_insert_id($stmt);
            mysqli_stmt_close($stmt);
            return [
                "affected_rows" => $affected_rows,
                "insert_id" => $insert_id
            ];
        }
        
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        
        mysqli_stmt_close($stmt);
        return $rows;
    } else {
        // Execute JSON Database operations
        $db = get_json_db();
        $cleanSql = preg_replace('/\s+/', ' ', trim($sql));
        
        // 1. SELECT by email
        if (preg_match('/SELECT .* FROM users WHERE email = \?/i', $cleanSql)) {
            $email = $params[0];
            $found = [];
            foreach ($db['users'] as $user) {
                if (strtolower($user['email']) === strtolower($email)) {
                    $found[] = $user;
                }
            }
            return $found;
        }
        
        // 2. SELECT user by id
        if (preg_match('/SELECT .* FROM users WHERE id = \?/i', $cleanSql)) {
            $id = (int)$params[0];
            $found = [];
            foreach ($db['users'] as $user) {
                if ((int)$user['id'] === $id) {
                    $found[] = $user;
                }
            }
            return $found;
        }
        
        // 3. SELECT farm by user_id
        if (preg_match('/SELECT .* FROM farms WHERE user_id = \?/i', $cleanSql)) {
            $userId = (int)$params[0];
            $found = [];
            foreach ($db['farms'] as $farm) {
                if ((int)$farm['user_id'] === $userId) {
                    $found[] = $farm;
                }
            }
            return $found;
        }
        
        // 4. INSERT INTO users
        if (preg_match('/INSERT INTO users/i', $cleanSql)) {
            // Params: full_name, email, password, mobile_number, address, city, province, role
            $newId = count($db['users']) > 0 ? max(array_column($db['users'], 'id')) + 1 : 1;
            $newUser = [
                "id" => $newId,
                "full_name" => $params[0],
                "email" => $params[1],
                "password" => $params[2],
                "mobile_number" => $params[3],
                "address" => $params[4],
                "city" => $params[5],
                "province" => $params[6],
                "role" => $params[7],
                "created_at" => date('Y-m-d H:i:s')
            ];
            $db['users'][] = $newUser;
            save_json_db($db);
            return [
                "affected_rows" => 1,
                "insert_id" => $newId
            ];
        }
        
        // 5. INSERT INTO farms
        if (preg_match('/INSERT INTO farms/i', $cleanSql)) {
            // Params: user_id, farm_name, farm_location, crop_type
            $newId = count($db['farms']) > 0 ? max(array_column($db['farms'], 'id')) + 1 : 1;
            $newFarm = [
                "id" => $newId,
                "user_id" => (int)$params[0],
                "farm_name" => $params[1],
                "farm_location" => $params[2],
                "crop_type" => $params[3]
            ];
            $db['farms'][] = $newFarm;
            save_json_db($db);
            return [
                "affected_rows" => 1,
                "insert_id" => $newId
            ];
        }
        
        // 6. UPDATE users
        if (preg_match('/UPDATE users SET/i', $cleanSql)) {
            // Params: full_name, mobile_number, address, city, province, id
            $userId = (int)$params[5];
            $updated = false;
            foreach ($db['users'] as &$user) {
                if ((int)$user['id'] === $userId) {
                    $user['full_name'] = $params[0];
                    $user['mobile_number'] = $params[1];
                    $user['address'] = $params[2];
                    $user['city'] = $params[3];
                    $user['province'] = $params[4];
                    $updated = true;
                    break;
                }
            }
            if ($updated) {
                save_json_db($db);
                return ["affected_rows" => 1, "insert_id" => 0];
            }
            return ["affected_rows" => 0, "insert_id" => 0];
        }
        
        // 7. UPDATE farms
        if (preg_match('/UPDATE farms SET/i', $cleanSql)) {
            // Params: farm_name, farm_location, crop_type, user_id
            $userId = (int)$params[3];
            $updated = false;
            foreach ($db['farms'] as &$farm) {
                if ((int)$farm['user_id'] === $userId) {
                    $farm['farm_name'] = $params[0];
                    $farm['farm_location'] = $params[1];
                    $farm['crop_type'] = $params[2];
                    $updated = true;
                    break;
                }
            }
            if ($updated) {
                save_json_db($db);
                return ["affected_rows" => 1, "insert_id" => 0];
            }
            return ["affected_rows" => 0, "insert_id" => 0];
        }
        
        return false;
    }
}
