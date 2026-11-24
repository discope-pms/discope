<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\pos\OrderPayment;

[$params, $providers] = eQual::announce([
    'description'   => "Validate a partial payment of a cashdesk order.",
    'params'        => [
        'id' => [
            'type'          => 'integer',
            'description'   => 'Identifier of the targeted payment.',
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
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context $context
 */
['context' => $context] = $providers;

// read payment object
$payment = OrderPayment::id($params['id'])
                        ->read([
                            'id', 'creator', 'status', 'total_paid', 'total_due'
                        ])
                        ->first();

if(!$payment) {
    throw new Exception("unknown_payment", EQ_ERROR_UNKNOWN_OBJECT);
}

if($payment['status'] == 'paid') {
    throw new Exception("invalid_status", EQ_ERROR_INVALID_PARAM);
}

if($payment['total_paid'] < $payment['total_due']) {
    throw new Exception("unbalanced_payment", EQ_ERROR_NOT_ALLOWED);
}

// set payment status to paid
OrderPayment::id($params['id'])->update(['status' => 'paid']);

$context
    ->httpResponse()
    ->status(204)
    ->send();
