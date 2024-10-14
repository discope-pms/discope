<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2024
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use finance\accounting\Invoice;

list($params, $providers) = announce([
    'description'   => "Sets invoice as invoiced.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the invoice.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ],
    ],
    'access' => [
        'visibility'        => 'protected',
        'groups'            => ['finance.default.user'],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context']
]);


$context = $providers['context'];

$invoice = Invoice::id($params['id'])->read(['id', 'state','status','invoice_lines_ids'])->first(true);


if(!$invoice) {
    throw new Exception("unknown_invoice", QN_ERROR_UNKNOWN_OBJECT);
}

if($invoice['state'] != 'instance' || $invoice['status'] != 'proforma') {
    throw new Exception("incompatible_status", QN_ERROR_INVALID_PARAM);
}

if(count($invoice['invoice_lines_ids']) <= 0) {
    throw new Exception("empty_invoice", QN_ERROR_INVALID_PARAM);
}

Invoice::id($invoice['id'])->update(['status' => 'invoice']);

$context->httpResponse()
        ->status(204)
        ->send();