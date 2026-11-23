<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

[$params, $providers] = eQual::announce([
    'description'   => "Assign a deposit number to multiple bank checks.",
    'help'          => "This action assigns an official deposit number to a list of bank checks.\n"
                    ."It iterates through the provided bank check IDs and updates each one with the specified deposit number.",
    'params'        => [
        'ids' => [
            'type'          => 'array',
            'description'   => 'List of Bank Check identifiers the check against emptiness.'
        ],
        'deposit_number' => [
            'type'          => 'string',
            'description'   => 'The official deposit number provided by the bank, used to track all associated checks.',
            'required'      => true
        ]
    ],
    'access'        => [
        'visibility'    => 'protected',
        'groups'        => ['booking.default.user']
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

foreach($params['ids'] as $id) {
    try {
        eQual::run('do', 'sale_booking_bankcheck_add-deposit-number', ['id' => $id, 'deposit_number' => $params['deposit_number'] ]);
    }
    catch (Exception $e) {
        $errors[] = "Failed to assign the deposit number to Bank Check ID {$id}: " . $e->getMessage();
    }
}

$context
    ->httpResponse()
    ->status(204)
    ->send();
