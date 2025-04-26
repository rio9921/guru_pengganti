<?php
/**
 * GuruSinergi - Koneksi Database
 * 
 * File untuk menangani koneksi dan interaksi dengan database
 */

// Jika config.php belum diinclude, include dulu
if (!function_exists('config')) {
    if (file_exists(__DIR__ . '/config.php')) {
        require_once __DIR__ . '/config.php';
    } else {
        die('Config file tidak ditemukan');
    }
}

// Coba buat koneksi mysqli
try {
    $db = new mysqli(
        config('db_host'),
        config('db_user'),
        config('db_pass'),
        config('db_name')
    );

    if ($db->connect_error) {
        throw new Exception("Koneksi database gagal: " . $db->connect_error);
    }

    // Set karakter encoding
    $db->set_charset("utf8mb4");

    // Debug untuk memeriksa koneksi
    if (config('debug_mode')) {
        error_log("Koneksi database berhasil dibuat");
    }

} catch (Exception $e) {
    // Log error dan tampilkan pesan yang user-friendly
    error_log("Database connection error: " . $e->getMessage());
    
    if (config('debug_mode')) {
        die("Koneksi database gagal: " . $e->getMessage());
    } else {
        die("Koneksi database gagal. Silahkan hubungi administrator.");
    }
}

/**
 * Fungsi untuk melakukan query dan menangani error
 * 
 * @param string $sql Query SQL yang akan dijalankan
 * @param array $params Parameter yang akan diikat ke query (jika ada)
 * @param string $types Tipe data untuk parameter (i=integer, d=double, s=string, b=blob)
 * @return mysqli_stmt|mysqli_result|bool Statement hasil prepare atau result set
 */
function db_query($sql, $params = [], $types = '') {
    global $db;

    try {
        if (empty($params)) {
            // Query langsung tanpa parameter
            $result = $db->query($sql);
            if ($result === false) {
                throw new Exception("Query error: " . $db->error);
            }
            return $result;
        } else {
            // Query dengan prepared statement
            $stmt = $db->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Prepare statement error: " . $db->error);
            }

            // Jika tipe tidak diisi, generate otomatis
            if (empty($types)) {
                $types = '';
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i';
                    } elseif (is_float($param)) {
                        $types .= 'd';
                    } elseif (is_string($param)) {
                        $types .= 's';
                    } else {
                        $types .= 'b';
                    }
                }
            }

            // Metode alternatif untuk binding parameter
            if (!empty($params)) {
                // Gunakan call_user_func_array dengan cara yang lebih sederhana
                if (count($params) === 1) {
                    $stmt->bind_param($types, $params[0]);
                } elseif (count($params) === 2) {
                    $stmt->bind_param($types, $params[0], $params[1]);
                } elseif (count($params) === 3) {
                    $stmt->bind_param($types, $params[0], $params[1], $params[2]);
                } elseif (count($params) === 4) {
                    $stmt->bind_param($types, $params[0], $params[1], $params[2], $params[3]);
                } elseif (count($params) === 5) {
                    $stmt->bind_param($types, $params[0], $params[1], $params[2], $params[3], $params[4]);
                } else {
                    // Untuk parameter yang lebih banyak, gunakan reflect
                    $refFunction = new ReflectionMethod($stmt, 'bind_param');
                    $refParams = array($types);
                    foreach ($params as $key => $value) {
                        $refParams[] = $value;
                    }
                    $refFunction->invokeArgs($stmt, $refParams);
                }
            }

            // Eksekusi statement
            if (!$stmt->execute()) {
                throw new Exception("Execute statement error: " . $stmt->error);
            }

            return $stmt;
        }
    } catch (Exception $e) {
        // Log error
        error_log("Database query error: " . $e->getMessage());
        
        if (config('debug_mode')) {
            die("Database query error: " . $e->getMessage());
        } else {
            die("Terjadi kesalahan database. Silahkan hubungi administrator.");
        }
    }
}

/**
 * Fungsi untuk mendapatkan hasil query sebagai array asosiatif
 * 
 * @param string $sql Query SQL yang akan dijalankan
 * @param array $params Parameter yang akan diikat ke query (jika ada)
 * @param string $types Tipe data untuk parameter
 * @return array Array hasil query
 */
function db_fetch_all($sql, $params = [], $types = '') {
    $result = db_query($sql, $params, $types);
    
    if ($result instanceof mysqli_stmt) {
        $stmt_result = $result->get_result();
        $data = $stmt_result->fetch_all(MYSQLI_ASSOC);
        $result->close();
        return $data;
    } elseif ($result instanceof mysqli_result) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    return [];
}

/**
 * Fungsi untuk mendapatkan satu baris hasil query
 * 
 * @param string $sql Query SQL yang akan dijalankan
 * @param array $params Parameter yang akan diikat ke query (jika ada)
 * @param string $types Tipe data untuk parameter
 * @return array|null Array hasil query atau null jika tidak ada data
 */
function db_fetch_one($sql, $params = [], $types = '') {
    $result = db_query($sql, $params, $types);
    
    if ($result instanceof mysqli_stmt) {
        $stmt_result = $result->get_result();
        $data = $stmt_result->fetch_assoc();
        $result->close();
        return $data;
    } elseif ($result instanceof mysqli_result) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Fungsi untuk insert data ke database
 * 
 * @param string $table Nama tabel
 * @param array $data Data yang akan diinsert
 * @return int|bool ID dari data yang diinsert atau false jika gagal
 */
function db_insert($table, $data) {
    global $db;
    
    $columns = array_keys($data);
    $values = array_values($data);
    
    $placeholders = array_fill(0, count($columns), '?');
    
    $sql = "INSERT INTO " . $table . " (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
    
    $result = db_query($sql, $values);
    
    if ($result) {
        return $db->insert_id;
    }
    
    return false;
}