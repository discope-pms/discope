<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use core\alert\Message;
use sale\booking\channelmanager\Booking;
use sale\booking\channelmanager\Funding;
use sale\booking\channelmanager\Payment;

[$params, $providers] = eQual::announce([
    'description'   => "Fully removes a booking along with all objects relating to it.",
    'help'          => "This action is made for booking that were erroneously imported while not yet validated at channelmanager side. WARNING: this cannot be undone (double-check with Channel Manager).",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the targeted booking.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ]
    ],
    'access'        => [
        'groups'        => ['admins', 'booking.default.administrator']
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

$booking = Booking::id($params['id'])
    ->read(['id', 'name', 'status', 'is_from_channelmanager', 'fundings_ids'])
    ->first(true);

if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

if(!$booking['is_from_channelmanager']) {
    throw new Exception("non_applicable_booking", QN_ERROR_INVALID_PARAM);
}

// remove any pending alert
Message::search([
    ['object_class', '=', 'sale\booking\Booking'],
    ['object_id', '=', $params['id']]
])
    ->delete(true);

// remove fundings
foreach($booking['fundings_ids'] as $funding_id) {
    $funding = Funding::id($funding_id)->read(['payments_ids'])->first(true);
    Payment::ids($funding['payments_ids'])->delete(true);
}

// remove booking
Booking::id($params['id'])->delete(true);

$context->httpResponse()
        ->status(205)
        ->send();
