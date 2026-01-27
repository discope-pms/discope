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
    'constants'     => ['L10N_TIMEZONE'],
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context  $context
 */
['context' => $context] = $provider;

$now = new DateTime('now', new DateTimeZone(constant('L10N_TIMEZONE')));

$bookings = Booking::search([
    [
        ['date_to', '=', time()],
        ['time_to', '<=', $now->format('H:i:s')],
        ['status', '=', 'checkedin']
    ],
    [
        ['date_to', '>', time()],
        ['status', '=', 'checkedin']
    ]
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
