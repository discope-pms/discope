<?php

use equal\data\adapt\DataAdapterProviderSql;

[$params, $providers] = eQual::announce([
    'description'   => 'Migrate the model discriminator for sale_contract_contractlinegroup.',
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
$table = 'sale_contract_contractlinegroup';
if(!in_array($table, $db->getTables(), true)) {
    throw new Exception('missing_table:' . $table, EQ_ERROR_INVALID_CONFIG);
}

$columns = $db->getTableColumns($table);
if(!in_array('model', $columns, true)) {
    $sql_adapter_provider = new DataAdapterProviderSql();
    $sql_type = $sql_adapter_provider->get('text/plain')->castOutType('text/plain:200');
    $db->sendQuery($db->getQueryAddColumn($table, 'model', ['type' => $sql_type, 'null' => true]));
    $columns = $db->getTableColumns($table);
}

$required_columns = ['id', 'model', 'contract_id'];
$missing_columns = array_values(array_diff($required_columns, $columns));
if(count($missing_columns)) {
    throw new Exception('missing_columns:' . implode(',', $missing_columns), EQ_ERROR_INVALID_CONFIG);
}

// Load and validate the related records used for classification.
$loadRows = static function (string $related_table, array $related_fields) use($db) : array {
    if(!in_array($related_table, $db->getTables(), true)) {
        throw new Exception('missing_table:' . $related_table, EQ_ERROR_INVALID_CONFIG);
    }
    $missing_columns = array_values(array_diff($related_fields, $db->getTableColumns($related_table)));
    if(count($missing_columns)) {
        throw new Exception(
            'missing_columns:' . $related_table . ':' . implode(',', $missing_columns),
            EQ_ERROR_INVALID_CONFIG
        );
    }
    $related_rows = [];
    $result = $db->getRecords($related_table, $related_fields);
    while($row = $db->fetchArray($result)) {
        $related_rows[(int) $row['id']] = $row;
    }
    return $related_rows;
};
$contracts = $loadRows('sale_contract_contract', ['id', 'booking_id']);

// Resolve every row to one concrete ORM model before opening a transaction.
$classify = static function (array $row) use($contracts) : string {
    $id = (int) ($row['contract_id'] ?? 0);
    if(!$id || !isset($contracts[$id])) {
        throw new Exception('orphan_contract_line_group', EQ_ERROR_INVALID_CONFIG);
    }
    if(!empty($contracts[$id]['booking_id'])) {
        return 'sale\\booking\\ContractLineGroup';
    }
    return 'sale\\contract\\ContractLineGroup';
};

// Group only rows whose discriminator must change.
$model_ids = [];
$row_count = 0;
$result = $db->getRecords($table, $required_columns);
while($row = $db->fetchArray($result)) {
    ++$row_count;
    $model = $classify($row);
    if(($row['model'] ?? null) !== $model) {
        $model_ids[$model][] = (int) $row['id'];
    }
}

// Apply the grouped updates atomically.
$migrated_rows = 0;
if(count($model_ids)) {
    $db->sendQuery('START TRANSACTION');
    try {
        foreach($model_ids as $model => $ids) {
            foreach(array_chunk($ids, 1000) as $chunk) {
                $db->setRecords($table, $chunk, ['model' => $model]);
                $migrated_rows += count($chunk);
            }
        }
        $db->sendQuery('COMMIT');
    } catch (Throwable $e) {
        $db->sendQuery('ROLLBACK');
        throw $e;
    }
}

// Re-read the table and verify the final discriminator of every row.
$result = $db->getRecords($table, $required_columns);
while($row = $db->fetchArray($result)) {
    if(($row['model'] ?? null) !== $classify($row)) {
        throw new Exception('model_migration_verification_failed', EQ_ERROR_UNKNOWN);
    }
}

$context
    ->httpResponse()
    ->status(200)
    ->body([
        'table'         => $table,
        'reported_rows' => $row_count,
        'current_rows'  => $row_count,
        'migrated_rows' => $migrated_rows
    ])
    ->send();
