<?php

// Sent through SSH standard input with a reviewed private package in $packageJson.
// Only the exact authorized empty Railway database is accepted. Never drop/truncate.
$stage = 'preflight';
$table = '';
$db = null;
$locked = false;
try {
    if (getenv('RAILWAY_PROJECT_ID') !== 'b437a3bd-4765-4ec2-aca4-4e6f7346390c'
        || getenv('RAILWAY_SERVICE_ID') !== '8366b39a-c537-41bf-acf1-20cca0bdc3a4'
        || getenv('RAILWAY_GIT_BRANCH') !== 'produccion'
        || getenv('APP_ENV') !== 'production' || getenv('DB_DATABASE') !== 'railway') {
        throw new RuntimeException('Unauthorized target context.');
    }
    if (! isset($packageJson, $expectedSha256) || hash('sha256', $packageJson) !== $expectedSha256) {
        throw new RuntimeException('Package checksum mismatch.');
    }
    $package = json_decode($packageJson, true, 512, JSON_THROW_ON_ERROR);
    if (($package['format'] ?? null) !== 1 || count($package['rows']['users'] ?? []) !== 1
        || count($package['schema'] ?? []) !== 33 || count($package['rows']['model_has_roles'] ?? []) !== 1) {
        throw new RuntimeException('Unexpected initialization package.');
    }
    $allowed = ['grades', 'committee_type', 'military_units', 'manual_phases', 'catalog_subphases',
        'doctrinal_maps', 'manual_types', 'roles', 'permissions', 'role_has_permissions',
        'phase_durations', 'migrations', 'users', 'model_has_roles'];
    foreach ($package['rows'] as $name => $data) {
        if (! in_array($name, $allowed, true) && $data) {
            throw new RuntimeException('Operational data is not authorized.');
        }
    }
    foreach ($package['schema'] as $name => $ddl) {
        if (! preg_match('/^[a-zA-Z0-9_]+$/D', $name) || ! str_starts_with($ddl, 'CREATE TABLE `'.$name.'` (')
            || ! str_contains($ddl, 'ENGINE=InnoDB')) {
            throw new RuntimeException('Unsupported schema statement.');
        }
    }
    $db = new PDO('mysql:host='.getenv('DB_HOST').';port='.(getenv('DB_PORT') ?: '3306').';dbname=railway;charset=utf8mb4',
        getenv('DB_USERNAME'), getenv('DB_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    if (! str_starts_with($db->query('SELECT VERSION()')->fetchColumn(), '8.4.')) {
        throw new RuntimeException('Unexpected target server version.');
    }
    $locked = (int) $db->query("SELECT GET_LOCK('artemis:initialization:railway',0)")->fetchColumn() === 1;
    if (! $locked || $db->query('SHOW FULL TABLES')->fetchAll()) {
        throw new RuntimeException('Target is not empty or another initializer is running.');
    }
    echo "Verified empty target: railway, MySQL 8.4, exclusive initialization lock.\n";
    if (($operation ?? '') === 'preflight-only') {
        $db->query("SELECT RELEASE_LOCK('artemis:initialization:railway')");
        echo "Package and target preflight passed; no schema or row changes.\n";
        return;
    }
    if (($operation ?? '') !== 'import-reviewed-package') {
        throw new RuntimeException('Explicit import operation required.');
    }
    // MySQL DDL commits implicitly. On failure, keep maintenance enabled and inspect;
    // never retry against partial tables or delete them automatically.
    $stage = 'schema';
    foreach ($package['schema'] as $table => $ddl) {
        $db->exec($ddl);
    }
    $stage = 'catalogues-and-admin';
    $db->beginTransaction();
    $dataOrder = array_keys($package['schema']);
    $dataOrder = array_values(array_diff($dataOrder, ['model_has_roles']));
    $dataOrder[] = 'model_has_roles';
    foreach ($dataOrder as $table) {
        foreach ($package['rows'][$table] as $row) {
            $columns = array_keys($row);
            foreach ($columns as $column) {
                if (! preg_match('/^[a-zA-Z0-9_]+$/D', $column)) {
                    throw new RuntimeException('Unexpected column identifier.');
                }
            }
            $insert = $db->prepare('INSERT INTO `'.$table.'` (`'.implode('`,`', $columns).'`) VALUES ('.implode(',', array_fill(0, count($columns), '?')).')');
            $insert->execute(array_values($row));
        }
    }
    $stage = 'verification';
    $counts = [];
    $normalize = static function (array $rows): array {
        $normalized = [];
        foreach ($rows as $row) {
            ksort($row);
            $row = array_map(static fn ($value) => $value === null ? null : (string) $value, $row);
            $normalized[] = hash('sha256', json_encode($row, JSON_THROW_ON_ERROR));
        }
        sort($normalized);
        return $normalized;
    };
    foreach ($package['rows'] as $table => $expectedRows) {
        $actualRows = $db->query('SELECT * FROM `'.$table.'`')->fetchAll(PDO::FETCH_ASSOC);
        if ($normalize($actualRows) !== $normalize($expectedRows)) {
            throw new RuntimeException('Verification mismatch.');
        }
        $counts[$table] = count($actualRows);
    }
    foreach ($package['foreign_keys'] as $fk) {
        $table = $fk['TABLE_NAME'];
        $column = $fk['COLUMN_NAME'];
        $parent = $fk['REFERENCED_TABLE_NAME'];
        $parentColumn = $fk['REFERENCED_COLUMN_NAME'];
        $orphans = $db->query('SELECT COUNT(*) FROM `'.$table.'` c LEFT JOIN `'.$parent.'` p ON c.`'.$column.'`=p.`'.$parentColumn.'` WHERE c.`'.$column.'` IS NOT NULL AND p.`'.$parentColumn.'` IS NULL')->fetchColumn();
        if ((int) $orphans !== 0) {
            throw new RuntimeException('Orphaned relationship found.');
        }
    }
    $fkCount = (int) $db->query("SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA='railway' AND REFERENCED_TABLE_NAME IS NOT NULL")->fetchColumn();
    if ($fkCount !== count($package['foreign_keys'])) {
        throw new RuntimeException('Foreign key schema mismatch.');
    }
    $db->commit();
    echo json_encode(['result' => 'verified', 'tables' => count($counts), 'foreign_key_columns' => $fkCount,
        'counts' => $counts, 'package_sha256' => $expectedSha256], JSON_THROW_ON_ERROR), PHP_EOL;
    $db->query("SELECT RELEASE_LOCK('artemis:initialization:railway')");
} catch (Throwable $error) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    // Do not leak hashes, credentials, SQL values or account data into remote logs.
    fwrite(STDERR, 'Initialization stopped at '.$stage.' table '.$table.' ('.get_class($error).'). No automatic cleanup or retry; inspect state before proceeding.'.PHP_EOL);
    exit(1);
}
