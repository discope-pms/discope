<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2022
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\booking\Booking;

// announce script and fetch parameters values
[$params, $providers] = eQual::announce([
    'description'   => "Batch for the updated Booking bulk.",
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

$bookings = Booking::search(['status', 'in', ['invoiced', 'credit_balance', 'debit_balance']])->ids();

if($bookings){
    eQual::run('do', 'sale_booking_bulk-update-status', ['ids' => $bookings]);
}

$context
    ->httpResponse()
    ->status(204)
    ->send();
