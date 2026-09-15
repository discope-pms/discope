<?php

use equal\data\adapt\DataAdapterProviderSql;

[$params, $providers] = eQual::announce([
    'description'   => 'Migrate the model discriminator for core_test_test.',
    'params'        => [
        'confirm' => [
            'description'   => 'Explicit confirmation required to execute the migration.',
            'type'          => 'boolean',
            'required'      => true,
        ],
    ],
    'access'        => [
        'visibility'    => 'protected',
        'groups'        => ['admins'],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'UTF-8',
        'accept-origin' => '*'
    ],
    'constants'     => ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_DBMS'],
    'providers'     => ['context', 'db']
]);

['context' => $context, 'db' => $dbConnector] = $providers;

// Require an explicit opt-in before any schema or data change.
if(($params['confirm'] ?? false) !== true) {
    throw new Exception('missing_confirmation', EQ_ERROR_MISSING_PARAM);
}

eQual::run('do', 'test_db-access');
$db = $dbConnector->connect();
if(!$db) {
    throw new Exception('missing_database', EQ_ERROR_INVALID_CONFIG);
}

// Validate the target schema and add the ORM-managed discriminator when absent.
$table = 'core_test_test';
$tables = array_fill_keys($db->getTables(), true);
if(!isset($tables[$table])) {
    throw new Exception('missing_table:' . $table, EQ_ERROR_INVALID_CONFIG);
}

$columns = $db->getTableColumns($table);
if(!in_array('model', $columns, true)) {
    $sql_adapter_provider = new DataAdapterProviderSql();
    $sql_type = $sql_adapter_provider->get('text/plain')->castOutType('text/plain:200');
    $db->sendQuery($db->getQueryAddColumn($table, 'model', ['type' => $sql_type, 'null' => true]));
    $columns = $db->getTableColumns($table);
}

$required_columns = ['id', 'model'];
$missing_columns = array_values(array_diff($required_columns, $columns));
if(count($missing_columns)) {
    throw new Exception('missing_columns:' . implode(',', $missing_columns), EQ_ERROR_INVALID_CONFIG);
}

$row_count = 0;
$result = $db->getRecords($table, ['id']);
if($db->fetchArray($result) !== false) {
    ++$row_count;
}

$context
    ->httpResponse()
    ->status(200)
    ->body([
        'table'         => $table,
        'reported_rows' => $row_count,
        'current_rows'  => $row_count,
        'migrated_rows' => 0
    ])
    ->send();
