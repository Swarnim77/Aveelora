<?php
$dbhost = 'localhost';
$dbuser = 'root';
$dbpass = '';
$dbname = 'raksi_db';

$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if ($conn->connect_error) {
    die('DB connect error: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

if (!function_exists('ensureOrdersColumn')) {
    /**
     * Ensure the `orders` table contains the requested column.
     *
     * @param mysqli $conn       Active database connection.
     * @param string $column     Column name to ensure.
     * @param string $definition Column definition fragment (e.g. "VARCHAR(30) NULL").
     *
     * @return bool True if the column exists or was created successfully, false otherwise.
     */
    function ensureOrdersColumn(mysqli $conn, string $column, string $definition): bool
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            return false;
        }

        $escapedColumn = $conn->real_escape_string($column);
        $check = $conn->query("SHOW COLUMNS FROM orders LIKE '{$escapedColumn}'");
        if ($check instanceof mysqli_result) {
            if ($check->num_rows > 0) {
                $check->free();
                return true;
            }
            $check->free();
        } elseif ($check === false) {
            return false;
        }

        return $conn->query("ALTER TABLE orders ADD COLUMN {$column} {$definition}") === true;
    }
}
?>

