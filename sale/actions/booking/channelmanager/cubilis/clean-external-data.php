<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\booking\Booking;

[$params, $providers] = eQual::announce([
    'description'   => "Clean cubilis bookings external_data field for ended bookings.",
    'params'        => [
    ],
    'access'        => [
        'visibility'    => 'protected',
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context  $context
 */
['context' => $context] = $providers;

Booking::search([
    [
        ['is_from_channelmanager', '=', true],
        ['external_data', '<>', null],
        ['external_data', '<>', ''],
        ['status', 'in', ['balanced', 'cancelled']]
    ],
    [
        ['is_from_channelmanager', '=', true],
        ['external_data', '<>', null],
        ['external_data', '<>', ''],
        ['is_cancelled', '=', true],
        ['status', '=', 'checkedout']
    ]
])
    ->update(['external_data' => '']);

$context
    ->httpResponse()
    ->send();
