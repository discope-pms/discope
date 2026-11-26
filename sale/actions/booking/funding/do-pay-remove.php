<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\booking\Booking;
use sale\booking\Funding;
use sale\booking\Payment;

[$params, $providers] = eQual::announce([
    'description'   => "Remove the manual payment attached to the funding, if any, and unmark funding as paid.",
    'help'          => "Manual payments can be undone while the booking is not fully balanced (and invoiced).",
    'params'        => [
        'id' => [
            'type'          => 'integer',
            'description'   => 'Identifier of the targeted funding.',
            'min'           => 1,
            'required'      => true
        ]
    ],
    'access'        => [
        'groups'        => ['finance.default.administrator', 'finance.default.user']
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

$funding = Funding::id($params['id'])
    ->read(['paid_amount', 'booking_id' => ['status']])
    ->first(true);

if(!$funding) {
    throw new Exception("unknown_funding", EQ_ERROR_UNKNOWN_OBJECT);
}

if($funding['paid_amount'] === 0.0) {
    throw new Exception("funding_not_paid", EQ_ERROR_INVALID_PARAM);
}

if(is_null($funding['booking_id'])) {
    throw new Exception("not_booking_funding", EQ_ERROR_INVALID_PARAM);
}

if($funding['booking_id']['status'] == 'balanced') {
    throw new Exception("booking_balanced", EQ_ERROR_INVALID_PARAM);
}

if($funding['booking_id']['status'] == 'cancelled') {
    throw new Exception("booking_cancelled", EQ_ERROR_INVALID_PARAM);
}

// remove manual payments, if any
$payments = Payment::search([
        ['funding_id', '=', $funding['id']],
        ['is_manual', '=', true]
    ])
    ->delete(true);

Funding::id($params['id'])
    ->update(['status' => 'pending'])
    ->update(['paid_amount' => null])
    ->update(['is_paid' => null]);

Booking::updateStatusFromFundings($om, (array) $funding['booking_id']['id'], [], 'en');

$context
    ->httpResponse()
    ->status(205)
    ->send();
