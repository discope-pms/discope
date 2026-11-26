<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\booking\Booking;

// announce script and fetch parameters values
[$params, $providers] = eQual::announce([
    'description'   => "Mark a selection of Booking as updated.",
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
    eQual::run('do', 'sale_booking_update-status', ['id' => $id]);
}

$context
    ->httpResponse()
    ->status(204)
    ->send();
