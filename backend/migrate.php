<?php
require_once __DIR__ . '/db.php';

// Run idempotent migrations to ensure new columns/constraints exist

function hasColumn($table, $column) {
    $q = pdo()->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $q->execute([$table, $column]);
    return (bool)$q->fetchColumn();
}

function hasFK($table, $column, $refTable) {
    $sql = "SELECT 1 FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME = ?";
    $q = pdo()->prepare($sql);
    $q->execute([$table, $column, $refTable]);
    return (bool)$q->fetchColumn();
}

$changes = [];

try {
    if (!hasColumn('properties', 'owner_id')) {
        pdo()->exec("ALTER TABLE properties ADD COLUMN owner_id INT NULL AFTER type");
        $changes[] = 'Added properties.owner_id';
    }
    if (!hasColumn('properties', 'status')) {
        pdo()->exec("ALTER TABLE properties ADD COLUMN status ENUM('available','rented') NOT NULL DEFAULT 'available' AFTER owner_id");
        $changes[] = "Added properties.status ('available'|'rented')";
        // Initialize existing rows
        pdo()->exec("UPDATE properties SET status = 'available' WHERE status IS NULL");
    }
    if (!hasFK('properties', 'owner_id', 'users')) {
        // Ensure index exists for owner_id to satisfy FK
        try { pdo()->exec("ALTER TABLE properties ADD INDEX idx_properties_owner_id (owner_id)"); } catch (Throwable $e) { /* ignore */ }
        pdo()->exec("ALTER TABLE properties ADD CONSTRAINT fk_properties_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL");
        $changes[] = 'Added FK properties.owner_id -> users.id';
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Migration error: ' . htmlspecialchars($e->getMessage());
    exit;
}

header('Content-Type: text/plain');
if (!$changes) {
    echo "No changes needed. Database is up to date.";
} else {
    echo "Applied changes:\n- " . implode("\n- ", $changes);
}
