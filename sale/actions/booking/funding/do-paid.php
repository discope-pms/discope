<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\booking\Booking;
use sale\booking\Funding;

[$params, $providers] = eQual::announce([
    'description'   => "Arbitrary mark a funding as paid for the booking. Applies only on bookings that are not from channel manager.",
    'help'          => "This breaks consistency between booking and payments. Avoid using it an prefer `add-manual-payment`",
    'deprecated'    => true,
    'params'        => [
        'id' => [
            'type'          => 'integer',
            'description'   => 'Identifier of the targeted funding.',
            'min'           => 1,
            'required'      => true
        ],
        'confirm' => [
            'type'          => 'boolean',
            'description'   => 'Manual confirmation.',
            'required'      => true
        ]
    ],
    'access'        => [
        'groups'        => ['admins', 'finance.accounting.administrator']
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm']
]);

/**
 * @var \equal\php\Context          $context
 * @var \equal\orm\ObjectManager    $om
 */
['context' => $context, 'orm' => $om] = $providers;

if(!$params['confirm']) {
    throw new Exception('missing_confirmation', EQ_ERROR_MISSING_PARAM);
}

$funding = Funding::id($params['id'])
    ->read([
        'booking_id' =>[
            'has_tour_operator',
            'is_from_channelmanager',
            'center_id' => [
                'id', 'sojourn_type_id' => ['id', 'name']
            ]
        ],
        'type', 'is_paid', 'due_amount', 'paid_amount'
    ])
    ->first(true);

if(!$funding) {
    throw new Exception("unknown_funding", EQ_ERROR_UNKNOWN_OBJECT);
}

if($funding['is_paid']) {
    throw new Exception("funding_already_paid", EQ_ERROR_INVALID_PARAM);
}

$delta = abs(round($funding['due_amount'], 2) - round($funding['paid_amount'], 2));
if($delta <= 0) {
    throw new Exception("nothing_to_pay", EQ_ERROR_INVALID_PARAM);
}

$booking = $funding['booking_id'];
if(!$booking) {
    throw new Exception("unknown_booking", EQ_ERROR_UNKNOWN_OBJECT);
}

if($booking['is_from_channelmanager']) {
    throw new Exception("invalid_booking_type", EQ_ERROR_INVALID_PARAM);
}

Funding::id($params['id'])
    ->update([
        'paid_amount'   => $funding['due_amount'],
        'is_paid'       => true,
        'status'        => 'paid'
    ]);

Booking::updateStatusFromFundings($om, (array) $booking['id'], [], 'en');

$context
    ->httpResponse()
    ->status(204)
    ->send();
