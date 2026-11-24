<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\booking\Booking;

// announce script and fetch parameters values
[$params, $providers] = eQual::announce([
    'description'   => "Update a booking status after balance invoice has been emitted.",
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
    'providers'     => ['context', 'orm']
]);

/**
 * @var \equal\php\Context          $context
 * @var \equal\orm\ObjectManager    $orm
 */
['context' => $context, 'orm' => $orm] = $providers;


// read booking object
$booking = Booking::id($params['id'])
                  ->read(['id', 'status', 'is_invoiced'])
                  ->first(true);

if(!$booking) {
    throw new Exception("unknown_booking", EQ_ERROR_UNKNOWN_OBJECT);
}

// #memo - is_invoiced seems not adding any additional information (read as balance invoice has been emitted, which is the same as having a status from 'invoiced' and beyond)
if(/*!$booking['is_invoiced'] || */!in_array($booking['status'], ['invoiced', 'credit_balance', 'debit_balance'])) {
    throw new Exception("incompatible_status", EQ_ERROR_INVALID_PARAM);
}


Booking::updateStatusFromFundings($orm, (array) $params['id']);


$context
    ->httpResponse()
    ->status(204)
    ->send();
