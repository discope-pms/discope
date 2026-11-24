<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\booking\Booking;
use sale\booking\Funding;
use sale\booking\Invoice;

[$params, $providers] = eQual::announce([
    'description'   => "Delete a proforma invoice.",
    'params'        => [
        'id' => [
            'type'          => 'integer',
            'description'   => 'Identifier of the invoice to emit.',
            'min'           => 1,
            'required'      => true
        ]
    ],
    'access'        => [
        'visibility'    => 'protected',
        'groups'        => ['finance.default.user', 'booking.default.user']
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

$invoice = Invoice::id($params['id'])
    ->read(['id', 'status', 'type', 'is_deposit', 'funding_id', 'fundings_ids', 'booking_id'])
    ->first(true);

if($invoice['status'] != 'proforma') {
    throw new Exception("incompatible_status", EQ_ERROR_INVALID_PARAM);
}

// detach fundings from deleted invoice
Funding::ids($invoice['fundings_ids'])->update(['invoice_id' => null]);

// if invoice had been created to convert an installment to a downpayment, revert the related funding
if($invoice['is_deposit']) {
    Funding::id($invoice['funding_id'])->update(['invoice_id' => null, 'type' => 'installment']);

    $funding = Funding::id($invoice['funding_id'])
        ->read(['paid_amount'])
        ->first();

    // remove funding if it hasn't received any payment yet
    if($funding['paid_amount'] == 0) {
        Funding::id($funding['id'])->delete(true);
    }
}
// if balanced proforma invoice deleted, set the status back to "checkedout"
elseif($invoice['type'] === 'invoice') {
    Booking::id($invoice['booking_id'])->update(['status' => 'checkedout']);
}

$invoice = Invoice::id($params['id'])->delete(true);

$context
    ->httpResponse()
    ->status(204)
    ->send();
