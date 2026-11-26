<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use identity\Partner;
use sale\booking\Funding;


[$params, $providers] = eQual::announce([
    'description'   => "Convert given funding to a downpayment invoice.",
    'params'        => [
        'id' => [
            'type'          => 'integer',
            'description'   => 'Identifier of the funding to be converted.',
            'min'           => 1,
            'required'      => true
        ],
        'partner_id' => [
            'type'          => 'integer',
            'description'   => 'Identifier of the partner (organisation) to who the invoice must be emitted (can be arbitrary).',
            'min'           => 1,
            'required'      => true
        ],
        'payment_terms_id' => [
            'type'          => 'integer',
            'description'   => 'Identifier of the payment terms to apply.',
            'min'           => 1,
            'default'       => 1
        ]
    ],
    'access'        => [
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


$partners = Partner::search(['id', '=', $params['partner_id']])->get();

if(!count($partners)) {
    throw new Exception("unknown_partner", EQ_ERROR_UNKNOWN_OBJECT);
}

$funding = Funding::id($params['id'])
                    ->read([
                        'type',
                        'booking_id' => ['status']
                    ])
                    ->first(true);

if(!$funding) {
    // unknown funding
    throw new Exception("unknown_funding", EQ_ERROR_UNKNOWN_OBJECT);
}

if($funding['type'] == 'invoice') {
    // already an invoice
    throw new Exception("incompatible_status", EQ_ERROR_INVALID_PARAM);
}


if(in_array($funding['booking_id']['status'], ['proforma', 'invoiced', 'debit_balance', 'credit_balance', 'balanced', 'cancelled'])) {
    // deposit invoice cannot be emitted after balance invoice
    throw new Exception("incompatible_booking_status", EQ_ERROR_INVALID_PARAM);
}

// convert the installment to a proforma invoice
$res = $orm->call(Funding::getType(), '_convertToInvoice', $params['id'], $params);
if($res !== true) {
    if($res === EQ_ERROR_NOT_ALLOWED) {
        throw new Exception("partner_not_allowed", EQ_ERROR_NOT_ALLOWED);
    }
    else {
        throw new Exception("error_while_converting", EQ_ERROR_UNKNOWN);
    }
}

// #memo - we avoid emitting invoices automatically because it prevents choosing the date for newer invoices

$context
    ->httpResponse()
    ->status(204)
    ->send();
