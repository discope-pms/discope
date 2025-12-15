<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\booking\BookingActivity;

[$params, $providers] = eQual::announce([
    'description'   => "Removes a booking activity.",
    'params'        => [

        'id' =>  [
            'type'          => 'many2one',
            'description'   => "Identifier of the targeted booking activity.",
            'min'           => 1,
            'required'      => true
        ]

    ],
    'access'        => [
        'visibility'    => 'protected',
        'groups'        => ['booking.default.user', 'camp.default.user'],
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

$booking_activity = BookingActivity::id($params['id'])
    ->read(['booking_id', 'camp_id'])
    ->first();

if(is_null($booking_activity)) {
    throw new Exception("unknown_activity", EQ_ERROR_UNKNOWN_OBJECT);
}

if(!is_null($booking_activity['booking_id']) || !is_null($booking_activity['camp_id'])) {
    throw new Exception("not_allowed", EQ_ERROR_NOT_ALLOWED);
}

BookingActivity::id($booking_activity['id'])->delete();

$context->httpResponse()
        ->status(205)
        ->send();
