<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2025
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\http\HttpRequest;
use finance\accounting\Invoice;

[$params, $providers] = eQual::announce([
    'description'   => "Generate the UBL file of a given invoice.",
    'params'        => [

        'id' =>  [
            'type'          => 'integer',
            'description'   => "Identifier of the invoice for which the UBL file has to be generated.",
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

/*$invoices = Invoice::search(
    [
        ['status', '=', 'invoice'],
        ['center_office_id', '=', 7]
    ],
    ['limit' => 3000]
)
    ->read(['partner_id' => ['partner_identity_id' => ['has_vat']]])
    ->get();

$invoice_id = 0;
foreach($invoices as $id => $inv) {
    if($inv['partner_id']['partner_identity_id']['has_vat']) {
        $invoice_id = $id;
        break;
    }
}

file_put_contents(QN_LOG_STORAGE_DIR.'/tmp.log', $invoice_id.PHP_EOL, FILE_APPEND | LOCK_EX);
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
    return str_replace([' ', '.'], '', $value);
};

$formatVatRate = function($value) {
    return number_format($value * 100, 1, ".", "");
};

/**
 * Action
 */

$invoice = Invoice::id($params['id'])
    ->read([
        'center_office_id' => [
            'organisation_id' => [
                'has_vat'
            ]
        ],
        'partner_id' => [
            'partner_identity_id' => [
                'has_vat'
            ]
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
    throw new Exception("organisation_no_vat", EQ_ERROR_INVALID_PARAM);
}

$api_uri = 'https://api.sandbox.falco-app.be/v1/invoices/imports/ubl';
$api_secret = 'as_test_MsH8DpD5ukSlSzMeflSx+w_JoTLkiRxaVFEmagwMx_WE0Y8KTVh7kiL';
$api_key = 'sk_test_tqN8+8JAtkWS+FT+pmmWEw_iPZ-hfX-zk-wDclTn90YYbC-M70xj957';

$request = new HttpRequest('POST '.$api_uri);

$request->header('Content-Type', 'multipart/form-data');
$request->header('accept', 'application/json');
$request->header('X-Falco-App-Secret', $api_secret);
$request->header('X-Falco-Api-Key', $api_key);

$body = [
    'file' => [
        'filename'  => 'invoice.xml',
        'content'   => eQual::run('get', 'finance_invoice_ubl', ['id' => $invoice['id']]),
        'type'      => 'application/xml',
    ]
];

$request->setBody($body);

$response = $request->send();

$status = $response->getStatusCode();

if($status != 200) {
    // upon request rejection, we stop the whole job
    throw new Exception("request_rejected", QN_ERROR_INVALID_PARAM);
}

$data = $response->body();

$context->httpResponse()
        ->status(200)
        ->send();