<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use identity\Partner;
use sale\booking\Booking;
use sale\booking\Funding;
use sale\booking\Invoice;


[$params, $providers] = eQual::announce([
    'description'   => "Generates the proforma for the balance invoice for a booking.",
    'params'        => [
        'id' => [
            'type'          => 'integer',
            'description'   => 'Identifier of the booking for which the invoice has to be generated.',
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



// read booking object
$booking = Booking::id($params['id'])
    ->read(['type_id', 'status', 'nb_pers', 'is_from_channelmanager', 'booking_lines_ids' => ['product_id'], 'customer_id'])
    ->first(true);

if(!$booking) {
    throw new Exception("unknown_booking", EQ_ERROR_UNKNOWN_OBJECT);
}

if($booking['status'] != 'checkedout') {
    throw new Exception("incompatible_status", EQ_ERROR_INVALID_PARAM);
}

if($booking['is_from_channelmanager'] !== true && $booking['type_id'] !== 1) {
    throw new Exception("incompatible_booking", EQ_ERROR_INVALID_PARAM);
}

$has_invalid_line = false;
foreach($booking['booking_lines_ids'] as $line) {
    if(is_null($line['product_id'])) {
        $has_invalid_line = true;
        break;
    }
}

if($has_invalid_line) {
    throw new Exception("invalid_line", EQ_ERROR_INVALID_PARAM);
}


// 1) Generate balance invoice (proforma) (raise exception on failure)
eQual::run('do', 'sale_booking_do-invoice', $params);


// 2) Emit invoice
$invoice = Invoice::search([ ['booking_id', '=', $booking['id']], ['type', '=', 'invoice'], ['status', '=', 'proforma'], ['is_deposit', '=', false] ], [ 'sort' => ['created' => 'desc']])
    ->read(['id'])
    ->first(true);

if(!$invoice) {
    throw new Exception("invoice_not_found", EQ_ERROR_MISSING_PARAM);
}

eQual::run('do', 'sale_booking_invoice_do-emit', $invoice);

$context
    ->httpResponse()
    ->status(204)
    ->send();
