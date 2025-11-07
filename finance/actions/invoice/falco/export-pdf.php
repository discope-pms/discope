<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2025
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\http\HttpRequest;
use finance\accounting\Invoice;

[$params, $providers] = eQual::announce([
    'description'   => "Export the PDF file of a given invoice to Falco API.",
    'params'        => [

        'id' =>  [
            'type'          => 'integer',
            'description'   => "Identifier of the invoice for which the PDF file has to be exported.",
            'min'           => 1,
            'required'      => true
        ]

    ],
    'access' => [
        'visibility'    => 'protected',
        'groups'        => ['finance.default.user'],
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

// TODO: remove following
/*$invoices = Invoice::search(
    [
        ['status', '=', 'invoice'],
        ['center_office_id', '=', 7]
    ],
    ['limit' => 3000]
)
    ->read(['partner_id' => ['partner_identity_id' => ['has_vat']], 'invoice_lines_ids' => ['unit_price']])
    ->get();

$invoice_ids = [];
foreach($invoices as $id => $inv) {
    if($inv['partner_id']['partner_identity_id']['has_vat']) {
        if(empty($inv['invoice_lines_ids']) || count($inv['invoice_lines_ids']) === 1) {
            continue;
        }

        foreach($inv['invoice_lines_ids'] as $line) {
            $parts = explode('.', (string)$line['unit_price']);

            if(isset($parts[1]) && strlen($parts[1]) > 2) {
                continue 2;
            }
        }

        $invoice_ids[] = $id;
    }
}

file_put_contents(QN_LOG_STORAGE_DIR.'/tmp.log', json_encode($invoice_ids).PHP_EOL, FILE_APPEND | LOCK_EX);
die();*/

/**
 * Methods
 */

$formatQty = function($value) {
    return number_format($value, 1, ".", "");
};

$formatMoney = function($value) {
    return number_format($value, 2, ".", "");
};

$formatVatNumber = function($value) {
    return str_replace([" ", "."], "", $value);
};

$formatVatRate = function($value) {
    return number_format($value * 100, 2, ".", "");
};

/**
 * Action
 */

$invoice = Invoice::id($params['id'])
    ->read([
        'type',
        'date',
        'due_date',
        'number',
        'total',
        'subtotals',
        'subtotals_vat',
        'price',
        'center_office_id' => [
            'organisation_id' => [
                'legal_name',
                'has_vat',
                'vat_number',
                'address_street',
                'address_zip',
                'address_city',
                'address_country',
            ]
        ],
        'partner_id' => [
            'partner_identity_id' => [
                'legal_name',
                'has_vat',
                'vat_number',
                'address_street',
                'address_zip',
                'address_city',
                'address_country',
            ]
        ],
        'invoice_lines_ids' => [
            'name',
            'description',
            'qty',
            'unit_price',
            'vat_rate',
            'total',
            'price'
        ]
    ])
    ->first();

if(is_null($invoice)) {
    throw new Exception("unknown_invoice", EQ_ERROR_UNKNOWN_OBJECT);
}

if(!$invoice['center_office_id']['organisation_id']['has_vat']) {
    throw new Exception("organisation_no_vat", EQ_ERROR_INVALID_PARAM);
}

if(!$invoice['partner_id']['partner_identity_id']['has_vat']) {
    throw new Exception("partner_no_vat", EQ_ERROR_INVALID_PARAM);
}

$api_uri = 'https://api.sandbox.falco-app.be/v1/invoices/imports/pdf';
$api_secret = 'as_test_MsH8DpD5ukSlSzMeflSx+w_JoTLkiRxaVFEmagwMx_WE0Y8KTVh7kiL';
$api_key = 'sk_test_tqN8+8JAtkWS+FT+pmmWEw_iPZ-hfX-zk-wDclTn90YYbC-M70xj957';

$request = new HttpRequest('POST '.$api_uri);

$request->header('Content-Type', 'multipart/form-data');
$request->header('accept', 'application/json');
$request->header('X-Falco-App-Secret', $api_secret);
$request->header('X-Falco-Api-Key', $api_key);

$sender = [
    /* Commented because using Falco SANDBOX env
    'name'          => $invoice['center_office_id']['organisation_id']['legal_name'],
    'address'       => [
        'line1'         => $invoice['center_office_id']['organisation_id']['address_street'],
        'zip'           => $invoice['center_office_id']['organisation_id']['address_zip'],
        'city'          => $invoice['center_office_id']['organisation_id']['address_city'],
        'country'       => $invoice['center_office_id']['organisation_id']['address_country']
    ],
    'vat_number'    => $formatVatNumber($invoice['center_office_id']['organisation_id']['vat_number'])
    */
    'name'          => 'app_1',
    'address'       => [
        'line1'         => 'Sandbox street, 1',
        'zip'           => '1000',
        'city'          => 'Brussels',
        'country'       => 'BE'
    ],
    'vat_number'    => 'BE0000000097'
];

$receiver = [
    'name'          => $invoice['partner_id']['partner_identity_id']['legal_name'],
    'address'       => [
        'line1'         => $invoice['partner_id']['partner_identity_id']['address_street'],
        'zip'           => $invoice['partner_id']['partner_identity_id']['address_zip'],
        'city'          => $invoice['partner_id']['partner_identity_id']['address_city'],
        'country'       => $invoice['partner_id']['partner_identity_id']['address_country']
    ],
    'vat_number'    => $formatVatNumber($invoice['partner_id']['partner_identity_id']['vat_number'])
];

$lines = [];
foreach($invoice['invoice_lines_ids'] as $line) {
    $vat_rate = $formatVatRate($line['vat_rate']);

    $lines[] = [
        'name'              => $line['name'],
        'description'       => !empty($line['description']) ? $line['description'] : $line['name'],
        'quantity'          => $formatQty($line['qty']),
        'unit_price'        => $formatMoney($line['unit_price']),  // TODO: wont work until falco accepts unit_price with 4 decimals
        'tax_rate'          => $vat_rate,
        'base_amount'       => $formatMoney($line['total']),
        'total_amount'      => $formatMoney($line['price']),
        'tax_regime_type'   => 'standard',
    ];
}

$tax_subtotals = [];
foreach($invoice['subtotals'] as $vat_rate_index => $subtotal) {
    $vat_rate = ((float) $vat_rate_index) / 100;

    $tax_subtotals[$vat_rate_index] = [
        'tax_rate'      => $formatVatRate($vat_rate),
        'base_amount'   => $subtotal,
        'tax_amount'    => $invoice['subtotals_vat'][$vat_rate_index],
        'tax_regime'    => [
            'type'          => 'standard'
        ]
    ];
}

$metadata = [
    'document_type'     => $invoice['type'] === 'invoice' ? 'sale_invoice' : 'sale_credit_note',
    'document_date'     => date('Y-m-d', $invoice['date']),
    'due_date'          => date('Y-m-d', $invoice['due_date']),
    'number'            => $invoice['number'],
    'buyer_reference'   => (string) $invoice['partner_id']['partner_identity_id']['id'],
    'sender'            => $sender,
    'receiver'          => $receiver,
    'currency'          => 'EUR',
    'base_amount'       => $formatMoney($invoice['total']),
    'total_amount'      => $formatMoney($invoice['price']),
    'tax_subtotals'     => array_values($tax_subtotals),
    'lines'             => $lines,
    'send_peppol'       => false,
    'send_accounting'   => false
];

$body = [
    'file' => [
        'filename'  => 'invoice.pdf',
        'content'   => eQual::run('get', 'sale_booking_print-invoice', ['id' => $invoice['id']]),
        'type'      => 'application/pdf',
    ],
    'metadata' => json_encode($metadata)
];

file_put_contents(QN_LOG_STORAGE_DIR.'/tmp.log', json_encode($metadata).PHP_EOL, FILE_APPEND | LOCK_EX);

$request->setBody($body);

$response = $request->send();

$status = $response->getStatusCode();

if($status != 200) {
    // upon request rejection, we stop the whole job
    throw new Exception("request_rejected", EQ_ERROR_INVALID_PARAM);
}

$data = $response->body();

$context->httpResponse()
        ->status(200)
        ->send();
