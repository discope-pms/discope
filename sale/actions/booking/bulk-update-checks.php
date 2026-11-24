<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

[$params, $providers] = eQual::announce([
    'description'   => "Runs a checks update on a selection of Booking.",
    'params'        => [
        'ids' => [
            'type'          => 'array',
            'description'   => 'List of Booking identifiers the check against emptiness.'
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
    eQual::run('do', 'sale_booking_do-update-check', ['id' => $id]);
}

$context
    ->httpResponse()
    ->status(204)
    ->send();
