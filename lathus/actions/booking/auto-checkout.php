<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\booking\Booking;

[$params, $provider] = eQual::announce([
    'description'   => "Auto checkout all bookings that are checkedin but have already ended.",
    'params'        => [],
    'access'        => [
        'visibility'        => 'protected'
    ],
    'response'      => [
        'content-type'      => 'application/json',
        'charset'           => 'utf-8',
        'accept-origin'     => '*'
    ],
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context  $context
 */
['context' => $context] = $provider;

$bookings = Booking::search([
    ['date_to', '<', strtotime('midnight')],
    ['status', '=', 'checkedin']
])
    ->read(['name'])
    ->get();

$result = [
    'successes' => [],
    'errors'    => []
];

foreach($bookings as $id =>  $booking) {
    try {
        eQual::run('do', 'sale_booking_do-checkout', ['id' => $id]);

        $result['successes'][] = "Booking {$booking['name']} successfully checked out.";
    }
    catch(Exception $e) {
        $result['errors'][] = "Unable to checkout booking {$booking['name']} : ".$e->getMessage();
    }
}

$context->httpResponse()
        ->body($result)
        ->status(200)
        ->send();
