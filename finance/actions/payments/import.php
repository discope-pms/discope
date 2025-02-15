<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2022
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use identity\CenterOffice;
use sale\booking\BankStatement;
use sale\booking\BankStatementLine;

list($params, $providers) = eQual::announce([
    'description'   => "Import a Bank statements file and return the list of created statements. Already existing statements are ignored.",
    'help'          => "This controller must be called using POST requests (experience shows that HTTP header quickly reaches the nginx max-header limit).",
    'params'        => [
        'data' =>  [
            'description'   => 'TXT file holding the data to import as statements.',
            'type'          => 'file',
            'required'      => true
        ]
    ],
    'access' => [
        'visibility'        => 'protected',
        'groups'            => ['sale.default.user'],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);


list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];

$user_id = $auth->userId();

if($user_id <= 0) {
    // restricted to identified users
    throw new Exception('unknown_user', QN_ERROR_NOT_ALLOWED);
}

// parse the CODA data
$data = eQual::run('get', 'sale_booking_payments_coda-parse', ['data' => $params['data']]);

if(empty($data)) {
    throw new Exception('invalid_file', QN_ERROR_INVALID_PARAM);
}

$result = [];
$statements = $data;

// #memo - there should be only one statement per file
foreach($statements as $statement) {

    $iban = BankStatement::convertBbanToIban($statement['account']['number']);

    $center_office = CenterOffice::search(['bank_account_iban', '=', trim($iban)])->read(['id'])->first(true);

    if(!$center_office) {
        throw new Exception('unknown_account_number', QN_ERROR_INVALID_PARAM);
    }

    $fields = [
            'raw_data'              => $params['data'],
            'date'                  => $statement['date'],
            'old_balance'           => $statement['old_balance'],
            'new_balance'           => $statement['new_balance'],
            'bank_account_number'   => trim($statement['account']['number']),
            'bank_account_bic'      => trim($statement['account']['bic']),
            'center_office_id'      => $center_office['id'],
            'status'                => 'pending'
        ];

    $bank_statement = BankStatement::search([
            ['old_balance', '=', $fields['old_balance']],
            ['new_balance', '=', $fields['new_balance']],
            ['date', '=', $fields['date']]
        ])
        ->first(true);

    if($bank_statement) {
        throw new Exception('already_imported', QN_ERROR_CONFLICT_OBJECT);
    }

    // unique constraint on ['date', 'old_balance', 'new_balance'] will apply
    $bank_statement = BankStatement::create($fields)
        ->read([
            'id',
            'bank_account_number',
            'bank_account_bic',
            'date',
            'old_balance',
            'new_balance'
        ])
        ->adapt('json')
        ->first(true);

    try {
        foreach($statement['transactions'] as $transaction) {
            $fields = [
                'bank_statement_id'     => $bank_statement['id'],
                'date'                  => $statement['date'],
                'amount'                => $transaction['amount'],
                'account_holder'        => substr($transaction['account']['name'], 0, 255),
                // #memo - should be an IBAN (though could theoretically not be)
                'account_iban'          => $transaction['account']['number'],
                'message'               => substr($transaction['message'], 0, 255),
                'structured_message'    => trim($transaction['structured_message']),
                'center_office_id'      => $center_office['id']
            ];
            // will trigger auto-reconcile (through `onupdateCenterOfficeId()`)
            BankStatementLine::create($fields);
        }
    }
    catch(Exception $e) {
        trigger_error('APP::faulty statement: '.$e->getMessage(), QN_REPORT_ERROR);
        // rollback
        BankStatement::id($bank_statement['id'])->delete(true);
        throw new Exception('import_error', QN_ERROR_UNKNOWN);
    }

    $result[] = $bank_statement;
}

$context->httpResponse()
        ->status(200)
        ->body($result)
        ->send();
