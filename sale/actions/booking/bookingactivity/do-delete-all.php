<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\booking\BookingActivity;
use sale\booking\BookingActivitySet;

[$params, $providers] = eQual::announce([
    'description'   => "Removes a booking activity and all activities within the same set.",
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

$bookingActivity = BookingActivity::id($params['id'])
    ->read(['booking_id', 'camp_id', 'booking_activity_set_id'])
    ->first();

if(!$bookingActivity) {
    throw new Exception("unknown_activity", EQ_ERROR_UNKNOWN_OBJECT);
}

if(!is_null($bookingActivity['booking_id']) || !is_null($bookingActivity['camp_id'])) {
    throw new Exception("not_allowed", EQ_ERROR_NOT_ALLOWED);
}

// #memo - all related BookingActivity items will be deleted in cascade
BookingActivitySet::id($bookingActivity['booking_activity_set_id'])->delete(true);

$context->httpResponse()
        ->status(205)
        ->send();
