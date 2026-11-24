<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\booking\Booking;


[$params, $providers] = eQual::announce([
    'description'   => "Mark a booking as reimbursed (status mut be `credit_balance`).",
    'params'        => [
        'id' => [
            'type'          => 'integer',
            'description'   => 'Identifier of the targeted booking.',
            'min'           => 1,
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
    'providers'     => ['context', 'auth']
]);

/**
 * @var \equal\php\Context $context
 */
['context' => $context] = $providers;

// read booking object
/** @var Booking */
$booking = Booking::id($params['id'])
                  ->read(['id', 'name', 'status'])
                  ->first(true);

if(!$booking) {
    throw new Exception("unknown_booking", EQ_ERROR_UNKNOWN_OBJECT);
}

if($booking['status'] != 'credit_balance') {
    throw new Exception("invalid_status", EQ_ERROR_NOT_ALLOWED);
}

Booking::id($params['id'])->update(['status' => 'balanced']);

$context
    ->httpResponse()
    ->status(204)
    ->send();
