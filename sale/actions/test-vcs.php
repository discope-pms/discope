<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use sale\booking\Booking;


list($params, $providers) = announce([
    'description'   => "This will generate an invoice for the given order. Order is expected to be paid already.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the Booking to test.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ]
    ],
    'access' => [
        'groups'            => ['booking.default.user', 'pos.default.user'],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);

list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];

$booking = Booking::id($params['id'])
    ->read([
        'id',
        'name'
    ])
    ->first();

if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

$code_ref = '150';

$booking_code = intval($booking['name']);

                $control = ((76 * intval($code_ref)) + $booking_code) % 97;
                $control = ($control == 0) ? 97 : $control;
                $reference_value = sprintf('%3d%04d%03d%02d', $code_ref, $booking_code / 1000, $booking_code % 1000, $control);



$context->httpResponse()
        ->body([
            'reference_value' => $reference_value
        ])
        ->send();


