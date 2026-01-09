<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use hr\employee\Employee;

[$params, $providers] = eQual::announce([
    'description'   => "Deactivates employees that don't have a pending contract.",
    'help'          => "Can be used as a recurring task, triggered at the start of every day.",
    'params'        => [
    ],
    'access'        => [
        'visibility'    => 'private',
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm']
]);

/**
 * @var \equal\php\Context          $context
 * @var \equal\orm\ObjectManager    $orm
 */
['context' => $context, 'orm' => $orm] = $providers;

$employees = Employee::search([
    [
        ['is_active', '=', true],
        ['date_start', '>', time()]
    ],
    [
        ['is_active', '=', true],
        ['date_end', 'is not', null],
        ['date_end', '<', time()]
    ]
])
    ->update(['is_active' => false]);


$context->httpResponse()
        ->status(200)
        ->send();
