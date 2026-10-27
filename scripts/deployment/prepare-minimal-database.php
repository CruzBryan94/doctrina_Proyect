<?php

// Read-only export of existing MySQL structure, catalogues and exactly one active admin.
// No Laravel boot, migrations, seeders or writes to the source database.
require dirname(__DIR__, 2).'/vendor/autoload.php';

try {
    $root = dirname(__DIR__, 2);
    $env = Dotenv\Dotenv::parse(file_get_contents($root.'/.env'));
    if (($env['DB_CONNECTION'] ?? 'mysql') !== 'mysql' || ! empty($env['DATABASE_URL'])) {
        throw new RuntimeException('Expected an explicit local MySQL DB_* source.');
    }
    $db = new PDO('mysql:host='.$env['DB_HOST'].';port='.($env['DB_PORT'] ?? '3306').';dbname='.$env['DB_DATABASE'].';charset=utf8mb4',
        $env['DB_USERNAME'], $env['DB_PASSWORD'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $db->exec('SET TRANSACTION READ ONLY');
    $db->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT');
    $tables = $db->query('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'')->fetchAll(PDO::FETCH_COLUMN);
    sort($tables);
    $adminRows = $db->query("SELECT u.* FROM users u WHERE u.is_active = 1 AND EXISTS (SELECT 1 FROM model_has_roles m JOIN roles r ON r.id=m.role_id WHERE m.model_id=u.id AND m.model_type='App\\\\Models\\\\User' AND r.name='admin' AND r.guard_name='web')")->fetchAll(PDO::FETCH_ASSOC);
    if (count($adminRows) !== 1) {
        throw new RuntimeException('Source must contain exactly one active administrator; found '.count($adminRows));
    }
    $admin = $adminRows[0];
    $admin['remember_token'] = null;
    if (array_key_exists('google_id', $admin)) {
        $admin['google_id'] = null;
    }
    if (! empty($admin['must_change_password']) || ! password_get_info($admin['password'])['algoName'] || password_get_info($admin['password'])['algoName'] === 'unknown') {
        throw new RuntimeException('Admin credential requires review before transfer.');
    }
    $catalogues = ['grades', 'committee_type', 'military_units', 'manual_phases', 'catalog_subphases',
        'doctrinal_maps', 'manual_types', 'roles', 'permissions', 'role_has_permissions',
        'phase_durations', 'migrations'];
    foreach ($catalogues as $table) {
        if (! in_array($table, $tables, true)) {
            throw new RuntimeException('Missing expected catalogue: '.$table);
        }
    }
    $schemaName = $db->query('SELECT DATABASE()')->fetchColumn();
    $fkStatement = $db->prepare('SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION');
    $fkStatement->execute([$schemaName]);
    $foreignKeys = $fkStatement->fetchAll(PDO::FETCH_ASSOC);
    $pending = array_fill_keys($tables, true);
    $order = [];
    while ($pending) {
        $progress = false;
        foreach (array_keys($pending) as $table) {
            foreach ($foreignKeys as $fk) {
                if ($fk['TABLE_NAME'] === $table && $fk['REFERENCED_TABLE_NAME'] !== $table && isset($pending[$fk['REFERENCED_TABLE_NAME']])) {
                    continue 2;
                }
            }
            $order[] = $table;
            unset($pending[$table]);
            $progress = true;
        }
        if (! $progress) {
            throw new RuntimeException('Cyclic schema dependencies require explicit review.');
        }
    }
    $schema = [];
    $rows = [];
    foreach ($order as $table) {
        if (! preg_match('/^[a-zA-Z0-9_]+$/D', $table)) {
            throw new RuntimeException('Unexpected table identifier.');
        }
        $schema[$table] = preg_replace('/ AUTO_INCREMENT=\d+/', '', $db->query('SHOW CREATE TABLE `'.$table.'`')->fetch(PDO::FETCH_NUM)[1]);
        $rows[$table] = in_array($table, $catalogues, true)
            ? $db->query('SELECT * FROM `'.$table.'`')->fetchAll(PDO::FETCH_ASSOC) : [];
    }
    $rows['users'] = [$admin];
    $role = $db->query("SELECT id FROM roles WHERE name='admin' AND guard_name='web'")->fetchColumn();
    $rows['model_has_roles'] = [['role_id' => $role, 'model_type' => 'App\\Models\\User', 'model_id' => $admin['id']]];
    $db->commit();

    // Validate every foreign key against the selected rows, before anything leaves this machine.
    foreach ($foreignKeys as $fk) {
        $parents = array_column($rows[$fk['REFERENCED_TABLE_NAME']], $fk['REFERENCED_COLUMN_NAME']);
        foreach ($rows[$fk['TABLE_NAME']] as $row) {
            $value = $row[$fk['COLUMN_NAME']];
            if ($value !== null && ! in_array($value, $parents, false)) {
                throw new RuntimeException('Selected data has an unresolved FK in '.$fk['TABLE_NAME'].'.'.$fk['COLUMN_NAME']);
            }
        }
    }
    $directory = $root.'/storage/app/backups/railway-minimal-'.date('Ymd-His');
    if (! mkdir($directory, 0700, true)) {
        throw new RuntimeException('Cannot create private export directory.');
    }
    $package = ['format' => 1, 'generated_at' => date(DATE_ATOM), 'source_version' => $db->query('SELECT VERSION()')->fetchColumn(),
        'schema' => $schema, 'rows' => $rows, 'foreign_keys' => $foreignKeys];
    $json = json_encode($package, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    file_put_contents($directory.'/package.private.json', $json, LOCK_EX);
    chmod($directory.'/package.private.json', 0600);
    $summary = ['source_version' => $package['source_version'], 'table_count' => count($schema),
        'foreign_key_column_count' => count($foreignKeys), 'rows' => array_map('count', $rows),
        'admin_id' => (int) $admin['id'], 'admin_email' => $admin['email'],
        'sha256' => hash('sha256', $json), 'excluded' => 'Operational records, people, other users, histories, audits, tokens, jobs and sessions'];
    file_put_contents($directory.'/manifest.json', json_encode($summary, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    echo json_encode(['directory' => $directory, 'manifest' => $summary], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), PHP_EOL;
} catch (Throwable $error) {
    // Never print DSNs, credentials, row contents or password hashes on failure.
    fwrite(STDERR, 'Preparation failed ('.get_class($error).'); inspect source/schema prerequisites without exposing credentials.'.PHP_EOL);
    exit(1);
}
