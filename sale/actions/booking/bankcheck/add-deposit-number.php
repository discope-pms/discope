<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\booking\BankCheck;

[$params, $providers] = eQual::announce([
    'description'   => "Assign a deposit number to a bank check and mark it as assigned.",
    'help'          => "This action updates a bank check by assigning an official deposit number provided by the bank.\n"
                    ."The check must not have been previously assigned or already have a deposit number.",
    'params'        => [
        'id' => [
            'type'          => 'integer',
            'description'   => 'Identifier of the targeted bank check.',
            'required'      => true
        ],
        'deposit_number' => [
            'type'          => 'string',
            'description'   => 'The official deposit number provided by the bank, used to track all associated checks.',
            'required'      => true
        ]
    ],
    'access'        => [
        'groups'        => ['booking.default.user', 'finance.default.administrator', 'finance.default.user']
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context $context
 */
['context' => $context] = $providers;

$bankCheck = BankCheck::id($params['id'])->read(['id','status'])->first(true);

if(!$bankCheck){
    throw new Exception("unknown_bankcheck", EQ_ERROR_UNKNOWN_OBJECT);
}

if(!empty($bankCheck['deposit_number'])){
    throw new Exception("bank_check_already_deposit_number", EQ_ERROR_INVALID_PARAM);
}

BankCheck::id($bankCheck['id'])
    ->update([
        'deposit_number' => $params['deposit_number']
    ]);

$context
    ->httpResponse()
    ->status(204)
    ->send();
