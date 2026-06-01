<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2026
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use documents\Export;
use equal\text\TextTransformer;
use identity\CenterOffice;
use finance\accounting\AccountingJournal;
use sale\booking\Invoice;
use sale\booking\Funding;

[$params, $providers] = eQual::announce([
    'description'   => "Creates an export archive containing all emitted invoices that haven't been exported yet (for external invoicing software).",
    'params'        => [

        'center_office_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'identity\CenterOffice',
            'description'       => 'Management Group to which the center belongs.',
            'required'          => true
        ],

        'journal_type' => [
            'type'              => 'string',
            'description'       => "The type of journal to export for the center office.",
            'selection'         => [
                'sales',
                'sales_peppol'
            ],
            'default'           => 'sales'
        ]

    ],
    'access'        => [
        'groups'        => ['finance.default.user'],
    ],
    'response'      => [
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'auth']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\auth\AuthenticationManager   $auth
 */
['context' => $context, 'auth' => $auth] = $providers;

/**
 * Methods
 */

$createFieldsSchema = function($fields_conf) {
    $index = 1;
    $position = 0;
    $fields_schema = [];
    foreach($fields_conf as $field => $field_conf) {
        // e.g., Field1=DBK,Char,04,00,00
        $fields_schema[] = sprintf('Field%d=%s,%s,%02d,%02d,%02d',
            $index,
            $field,
            $field_conf['type'],
            $field_conf['length'],
            $field_conf['decimals'],
            $position
        );

        $index++;
        $position += $field_conf['length'];
    }

    return implode("\r\n", $fields_schema);
};

// #memo - pad that can handle special characters
$mb_str_pad = function($input, $pad_length, $pad_string = " ", $pad_type = STR_PAD_RIGHT, $encoding = "UTF-8") {
    $input_length = mb_strlen($input, $encoding);
    $pad_needed = $pad_length - $input_length;

    if ($pad_needed <= 0) {
        return $input;
    }

    switch ($pad_type) {
        case STR_PAD_LEFT:
            return str_repeat($pad_string, $pad_needed) . $input;

        case STR_PAD_BOTH:
            $left = floor($pad_needed / 2);
            $right = ceil($pad_needed / 2);
            return str_repeat($pad_string, $left) . $input . str_repeat($pad_string, $right);

        case STR_PAD_RIGHT:
        default:
            return $input . str_repeat($pad_string, $pad_needed);
    }
};

/**
 * Action
 */

$office = CenterOffice::id($params['center_office_id'])
    ->read(['id'])
    ->first();

if(!$office) {
    throw new Exception("unknown_center_office", QN_ERROR_UNKNOWN_OBJECT);
}

$journal = AccountingJournal::search([
    ['center_office_id', '=', $office['id']],
    ['type', '=', $params['journal_type']]
])
    ->read(['code', 'type'])
    ->first();

if(!$journal) {
    throw new Exception("unknown_accounting_journal", QN_ERROR_UNKNOWN_OBJECT);
}

$domain = [
    [
        ['journal_id', '=', $journal['id']],
        ['is_exported', '=', false],
        ['center_office_id', '=', $params['center_office_id']],
        ['booking_id', '>', 0],
        ['status', '<>', 'proforma']
    ],
    [
        ['journal_id', '=', $journal['id']],
        ['is_exported', '=', false],
        ['center_office_id', '=', $params['center_office_id']],
        ['has_orders', '=', true],
        ['status', '<>', 'proforma']
    ]
];

$funding_fields = [
    'payment_reference',
    'due_date',
    'paid_amount'
];

$invoices = Invoice::search($domain, ['limit' => 100, 'sort' => ['number' => 'asc']])
    ->read([
        'name',
        'date',
        'due_date',
        'type',
        'status',
        // #memo - accounting price is the amount to be recorded in accountancy (does not include installment payments)
        'accounting_price',
        'is_deposit',
        'organisation_id',
        'subtotals_vat',
        'total',
        'total_vat',
        'price',
        'partner_id' => [
            '@domain' => ['state', 'in', ['instance', 'archive']],
            'name',
            'partner_identity_id' => [
                '@domain' => ['state', 'in', ['instance', 'archive']],
                'address_street',
                'address_dispatch',
                'address_city',
                'address_zip',
                'address_country',
                'vat_number',
                'phone',
                'fax',
                'lang_id' => [
                    'code'
                ]
            ]
        ],
        'has_orders',
        'center_office_id' => [
            'analytic_section_id' => [
                'code'
            ]
        ],
        'booking_id' => [
            'name',
            'date_from',
            'date_to',
            'center_id' => [
                'analytic_section_id' => [
                    'code'
                ]
            ]
        ],
        'funding_id' => $funding_fields,
        'invoice_lines_ids' => [
            'name',
            'unit_price',
            'qty',
            'total',
            'price',
            'product_id',
            'price_id' => [
                'vat_rate',
                'accounting_rule_id' => [
                    'accounting_rule_line_ids' => [
                        'account_id' => [
                            'code'
                        ],
                        'share'
                    ]
                ]
            ]
        ]
    ])
    ->get(true);

if(empty($invoices)) {
    // exit with no error
    throw new Exception("no_match");
}

/*
    Check invoices consistency : discard invalid invoices and emit a warning.
*/

foreach($invoices as $index => $invoice) {
    if(empty($invoice['partner_id']['partner_identity_id'])) {
        $invoice_info = print_r($invoice, true);
        trigger_error("APP::Ignoring invalid invoice : missing partner info for invoice {$invoice['name']} [{$invoice['id']}] - $invoice_info", EQ_REPORT_WARNING);
        unset($invoices[$index]);
    }
    elseif(!$invoice['has_orders'] && !isset($invoice['booking_id'])) {
        trigger_error("APP::Ignoring invalid invoice : missing booking info for invoice {$invoice['name']} [{$invoice['id']}]", EQ_REPORT_WARNING);
        unset($invoices[$index]);
    }
    // #memo - for cancelled invoices and orders invoices, it is ok not to have funding
}

/*
    Get fundings
 */

foreach($invoices as &$inv) {
    if($inv['funding_id']) {
        continue;
    }

    $funding = Funding::search(['invoice_id', '=', $inv['id']])
        ->read($funding_fields)
        ->first(true);

    $inv['funding_id'] = $funding;
}

/*
    Generate: CLIENTS_FACT.txt
*/

$customers_fields_conf = [
    'CID'               => ['type' => 'Char',           'length' => 10,     'decimals' => 0],
    'CCUSTYPE'          => ['type' => 'Char',           'length' => 1,      'decimals' => 0],
    'CSUPTYPE'          => ['type' => 'Char',           'length' => 1,      'decimals' => 0],
    'CNAME1'            => ['type' => 'Char',           'length' => 40,     'decimals' => 0],
    'CNAME2'            => ['type' => 'Char',           'length' => 40,     'decimals' => 0],
    'CADDRESS1'         => ['type' => 'Char',           'length' => 40,     'decimals' => 0],
    'CADDRESS2'         => ['type' => 'Char',           'length' => 40,     'decimals' => 0],
    'CZIPCODE'          => ['type' => 'Char',           'length' => 10,     'decimals' => 0],
    'CLOCALITY'         => ['type' => 'Char',           'length' => 40,     'decimals' => 0],
    'CLANGUAGE'         => ['type' => 'Char',           'length' => 2,      'decimals' => 0],
    'CISPERS'           => ['type' => 'Bool',           'length' => 1,      'decimals' => 0],
    'CCUSCAT'           => ['type' => 'Char',           'length' => 3,      'decimals' => 0],
    'CCURRENCY'         => ['type' => 'Char',           'length' => 3,      'decimals' => 0],
    'CVATCAT'           => ['type' => 'Char',           'length' => 1,      'decimals' => 0],
    'CVATREF'           => ['type' => 'Char',           'length' => 2,      'decimals' => 0],
    'CVATNO'            => ['type' => 'Char',           'length' => 12,     'decimals' => 0],
    'CTELNO'            => ['type' => 'Char',           'length' => 14,     'decimals' => 0],
    'CFAXNO'            => ['type' => 'Char',           'length' => 14,     'decimals' => 0],
    'CCUSVNAT1'         => ['type' => 'Char',           'length' => 3,      'decimals' => 0],
    'CCUSVNAT2'         => ['type' => 'Char',           'length' => 3,      'decimals' => 0],
    'CCUSVATCMP'        => ['type' => 'Float',          'length' => 20,     'decimals' => 2],
    'CCUSCTRACC'        => ['type' => 'Char',           'length' => 10,     'decimals' => 0],
    'CCUSIMPUTA'        => ['type' => 'Char',           'length' => 10,     'decimals' => 0],
    'CCTRYCODE'         => ['type' => 'Char',           'length' => 2,      'decimals' => 0],
    'CBANKCODE'         => ['type' => 'Char',           'length' => 6,      'decimals' => 0],
    'CBANKNO'           => ['type' => 'Char',           'length' => 19,     'decimals' => 0],
    'CISWARNING'        => ['type' => 'Bool',           'length' => 1,      'decimals' => 0],
    'CISREADONL'        => ['type' => 'Bool',           'length' => 1,      'decimals' => 0],
    'CISBLOCK'          => ['type' => 'Bool',           'length' => 1,      'decimals' => 0],
    'CISSECRET'         => ['type' => 'Bool',           'length' => 1,      'decimals' => 0],
    'CCUSPAYDELAY'      => ['type' => 'Char',           'length' => 6,      'decimals' => 0],
    'CREMCAT'           => ['type' => 'Char',           'length' => 5,      'decimals' => 0],
    'CREMSTATUS'        => ['type' => 'Char',           'length' => 1,      'decimals' => 0],
    'CREATEDATE'        => ['type' => 'TimeStamp',      'length' => 30,     'decimals' => 0],
    'MODIFYDATE'        => ['type' => 'TimeStamp',      'length' => 30,     'decimals' => 0],
    'AUTHOR'            => ['type' => 'Char',           'length' => 10,     'decimals' => 0],
    'CNATREGISTRYID'    => ['type' => 'Char',           'length' => 15,     'decimals' => 0],
    'CCUSPDISCDEL'      => ['type' => 'Long Integer',   'length' => 11,     'decimals' => 0],
    'CCUSTEMPLID'       => ['type' => 'Char',           'length' => 10,     'decimals' => 0],
    'CMEMO'             => ['type' => 'Char',           'length' => 200,    'decimals' => 0]
];

$customers_schema = implode("\r\n", ['[CLIENTS_FACT]', 'FileType=Fixed', 'CharSet=ascii'])."\r\n".$createFieldsSchema($customers_fields_conf);

$customers_data = [];
foreach($invoices as $invoice) {
    $customer_name = substr(strtoupper(TextTransformer::normalize($invoice['partner_id']['name'])), 0, 40);
    $customer_phone = substr(str_replace([' ', '.', '-', '_'], '', $invoice['partner_id']['partner_identity_id']['phone']), 0, 14);
    $customer_fax = substr(str_replace([' ', '.', '-', '_'], '', $invoice['partner_id']['partner_identity_id']['fax']), 0, 14);
    $customer_address = substr(strtoupper(TextTransformer::normalize(str_replace(["\n", "\t", "\r"], '', $invoice['partner_id']['partner_identity_id']['address_street']))), 0, 40);
    $customer_address_dispatch = substr(strtoupper(TextTransformer::normalize(str_replace(["\n", "\t", "\r"], '', $invoice['partner_id']['partner_identity_id']['address_dispatch']))), 0, 40);
    $customer_city = substr(strtoupper(TextTransformer::normalize($invoice['partner_id']['partner_identity_id']['address_city'])), 0, 40);
    $customer_country = substr(strtoupper($invoice['partner_id']['partner_identity_id']['address_country']), 0, 2);

    $customer_zip = str_replace([' ', '.', '-', '_'], '', $invoice['partner_id']['partner_identity_id']['address_zip']);
    if(!empty($customer_zip) && !empty($customer_country) && substr($customer_zip, 0, 2) !== $customer_country) {
        // #memo - add country prefix from zipcode
        $customer_zip = substr($customer_country.$customer_zip, 0, 10);
    }
    else {
        $customer_zip = substr($customer_zip, 0, 10);
    }

    $customer_vat = str_replace([' ', '.', '-', '_'], '', $invoice['partner_id']['partner_identity_id']['vat_number']);
    if(!empty($customer_vat) && !empty($customer_country) && substr($customer_vat, 0, 2) === $customer_country) {
        // #memo - remove country prefix from vat number
        $customer_vat = substr($customer_vat, 2, 12);
    }
    else {
        $customer_vat = substr($customer_vat, 0, 12);
    }

    $customer_lang = 'F';
    if(isset($invoice['partner_id']['partner_identity_id']['lang_id']['code']) && is_string($invoice['partner_id']['partner_identity_id']['lang_id']['code']) && strlen($invoice['partner_id']['partner_identity_id']['lang_id']['code']) >= 1) {
        // #memo - BOB uses a single letter
        $customer_lang = strtoupper(substr($invoice['partner_id']['partner_identity_id']['lang_id']['code'], 0, 1));
    }

    $map_values = [
        'CID'               => str_pad('C'.$invoice['partner_id']['partner_identity_id']['id'], 10, ' ', STR_PAD_RIGHT),
        'CCUSTYPE'          => str_pad('C', 1,' ',STR_PAD_LEFT),
        'CSUPTYPE'          => str_pad('U', 1,' ',STR_PAD_LEFT),
        'CNAME1'            => str_pad($customer_name, 40, ' ', STR_PAD_RIGHT),
        'CNAME2'            => str_pad('', 40, ' ', STR_PAD_RIGHT),
        'CADDRESS1'         => str_pad($customer_address, 40, ' ', STR_PAD_RIGHT),
        'CADDRESS2'         => str_pad($customer_address_dispatch, 40, ' ', STR_PAD_RIGHT),
        'CZIPCODE'          => str_pad($customer_zip, 10, ' ', STR_PAD_RIGHT),
        'CLOCALITY'         => str_pad($customer_city, 40, ' ', STR_PAD_RIGHT),
        'CLANGUAGE'         => str_pad($customer_lang, 2, ' ', STR_PAD_RIGHT),
        'CISPERS'           => str_pad('0', 1, ' ', STR_PAD_RIGHT),
        'CCUSCAT'           => str_pad('', 3, ' ', STR_PAD_RIGHT),
        'CCURRENCY'         => str_pad('EUR', 3, ' ', STR_PAD_RIGHT),
        'CVATCAT'           => str_pad('', 1, ' ', STR_PAD_RIGHT),
        'CVATREF'           => str_pad($customer_country, 2, ' ', STR_PAD_RIGHT),
        'CVATNO'            => str_pad($customer_vat, 12, ' ', STR_PAD_RIGHT),
        'CTELNO'            => str_pad($customer_phone, 14, ' ', STR_PAD_RIGHT),
        'CFAXNO'            => str_pad($customer_fax, 14, ' ', STR_PAD_RIGHT),
        'CCUSVNAT1'         => str_pad('', 3, ' ', STR_PAD_RIGHT),
        'CCUSVNAT2'         => str_pad('', 3, ' ', STR_PAD_RIGHT),
        'CCUSVATCMP'        => str_pad('', 20, ' ', STR_PAD_RIGHT),
        'CCUSCTRACC'        => str_pad('', 10, ' ', STR_PAD_RIGHT),
        'CCUSIMPUTA'        => str_pad('', 10, ' ', STR_PAD_RIGHT),
        'CCTRYCODE'         => str_pad($customer_country, 2, ' ', STR_PAD_RIGHT),
        'CBANKCODE'         => str_pad('', 6, ' ', STR_PAD_RIGHT),
        'CBANKNO'           => str_pad('', 19, ' ', STR_PAD_RIGHT),
        'CISWARNING'        => str_pad('0', 1, ' ', STR_PAD_RIGHT),
        'CISREADONL'        => str_pad('0', 1, ' ', STR_PAD_RIGHT),
        'CISBLOCK'          => str_pad('0', 1, ' ', STR_PAD_RIGHT),
        'CISSECRET'         => str_pad('0', 1, ' ', STR_PAD_RIGHT),
        'CCUSPAYDELAY'      => str_pad('', 6, ' ', STR_PAD_RIGHT),
        'CREMCAT'           => str_pad('', 5, ' ', STR_PAD_RIGHT),
        'CREMSTATUS'        => str_pad('', 1, ' ', STR_PAD_RIGHT),
        'CREATEDATE'        => str_pad('', 30, ' ', STR_PAD_RIGHT),
        'MODIFYDATE'        => str_pad('', 30, ' ', STR_PAD_RIGHT),
        'AUTHOR'            => str_pad('', 10, ' ', STR_PAD_RIGHT),
        'CNATREGISTRYID'    => str_pad('', 15, ' ', STR_PAD_RIGHT),
        'CCUSPDISCDEL'      => str_pad('', 11, ' ', STR_PAD_RIGHT),
        'CCUSTEMPLID'       => str_pad('', 10, ' ', STR_PAD_RIGHT),
        'CMEMO'             => str_pad('', 200, ' ', STR_PAD_RIGHT),
    ];

    $values = [];
    foreach(array_keys($customers_fields_conf) as $field) {
        $values[] = $map_values[$field];
    }

    $customers_data[] = implode('', $values);
}

/*
    Generate headers: IHDDOC_FACT.txt
*/

$invoices_fields_conf = [
    'DBK'           => ['type' => 'Char',           'length' => 4,      'decimals' => 0],
    'FYEAR'         => ['type' => 'Char',           'length' => 5,      'decimals' => 0],
    'DOCNO'         => ['type' => 'Long Integer',   'length' => 11,     'decimals' => 0],
    'YEAR'          => ['type' => 'Long Integer',   'length' => 11,     'decimals' => 0],
    'MONTH'         => ['type' => 'Long Integer',   'length' => 11,     'decimals' => 0],
    'DBKTYPE'       => ['type' => 'Char',           'length' => 3,      'decimals' => 0],
    'DOCDATE'       => ['type' => 'Date',           'length' => 11,     'decimals' => 0],
    'DUEDATE'       => ['type' => 'Date',           'length' => 11,     'decimals' => 0],
    'CPID'          => ['type' => 'Char',           'length' => 12,     'decimals' => 0],
    'CPTYPE'        => ['type' => 'Char',           'length' => 1,      'decimals' => 0],
    'INTREM'        => ['type' => 'Char',           'length' => 30,     'decimals' => 0],
    'EXTREM'        => ['type' => 'Char',           'length' => 30,     'decimals' => 0],
    'VCS'           => ['type' => 'Char',           'length' => 17,     'decimals' => 0],
    'TOTLINE'       => ['type' => 'Float',          'length' => 21,     'decimals' => 2],
    'BASEVATAMN'    => ['type' => 'Float',          'length' => 21,     'decimals' => 2],
    'PAYAMN'        => ['type' => 'Float',          'length' => 21,     'decimals' => 2],
    'XUSRACOMPTE'   => ['type' => 'Float',          'length' => 21,     'decimals' => 2]
];

$invoices_schema = implode("\r\n", ['[IHDDOC_FACT]', 'FileType=Fixed', 'CharSet=ascii'])."\r\n".$createFieldsSchema($invoices_fields_conf);

$invoices_data = [];
foreach($invoices as $invoice) {
    // when invoice is a credit note, PAYAMN must be inverted (in most cases should be negative)
    if($invoice['type'] == 'credit_note') {
        $invoice['accounting_price'] = -$invoice['accounting_price'];
    }
    // for orders, use static memo as internal comments
    if($invoice['has_orders']) {
        $comments = 'VENTES COMPTOIR';
    }
    // for bookings, use as internal comments
    else {
        $comments = $invoice['booking_id']['name'].' '.date('d/m/Y', $invoice['booking_id']['date_from']).'-'.date('d/m/Y', $invoice['booking_id']['date_to']);
    }

    $payment_reference = isset($invoice['funding_id']['payment_reference']) ? (substr($invoice['funding_id']['payment_reference'], 0, 17)) : '';

    $map_values = [
        'DBK'           => str_pad($journal['code'], 4, ' ', STR_PAD_RIGHT),
        'FYEAR'         => str_pad(date('Y', $invoice['date']), 5,' ', STR_PAD_RIGHT),
        'DOCNO'         => str_pad(str_replace('-', '', $invoice['name']), 11,' ', STR_PAD_RIGHT),
        'YEAR'          => str_pad(date('Y', $invoice['date']), 11,' ', STR_PAD_RIGHT),
        'MONTH'         => str_pad(date('m', $invoice['date']), 11,' ', STR_PAD_RIGHT),
        'DBKTYPE'       => str_pad('SAL', 3,' ', STR_PAD_RIGHT),
        'DOCDATE'       => str_pad(date('d/m/Y', $invoice['date']), 11,' ', STR_PAD_RIGHT),
        'DUEDATE'       => str_pad(date('d/m/Y', $invoice['due_date']), 11,' ', STR_PAD_RIGHT),
        'CPID'          => str_pad('C'.$invoice['partner_id']['partner_identity_id']['id'], 12, ' ', STR_PAD_RIGHT),
        'CPTYPE'        => str_pad('C', 1,' ',STR_PAD_LEFT),
        'INTREM'        => str_pad($payment_reference, 30, ' ', STR_PAD_RIGHT),
        'EXTREM'        => str_pad($comments, 30,' ', STR_PAD_RIGHT),
        'VCS'           => str_pad($payment_reference, 17, ' ', STR_PAD_RIGHT),
        'TOTLINE'       => str_pad(str_replace('.', ',', sprintf('%.02f', $invoice['total'])), 21,' ', STR_PAD_LEFT),
        'BASEVATAMN'    => str_pad(str_replace('.', ',', sprintf('%.02f', $invoice['total_vat'])), 21,' ', STR_PAD_LEFT),
        'PAYAMN'        => str_pad(str_replace('.', ',', sprintf('%.02f', $invoice['accounting_price'])), 21,' ', STR_PAD_LEFT),
        'XUSRACOMPTE'   => str_pad(str_replace('.', ',', sprintf('%.02f', $invoice['funding_id']['paid_amount'])), 21,' ', STR_PAD_LEFT)
    ];

    $values = [];
    foreach(array_keys($invoices_fields_conf) as $field) {
        $values[] = $map_values[$field];
    }

    $invoices_data[] = implode('', $values);
}

/*
    Generate headers: IHISTO_FACT.txt
*/

$invoices_lines_fields_conf = [
    'DBK'           => ['type' => 'Char',           'length' => 4,      'decimals' => 0],
    'FYEAR'         => ['type' => 'Char',           'length' => 5,      'decimals' => 0],
    'DOCNO'         => ['type' => 'Long Integer',   'length' => 11,     'decimals' => 0],
    'DOCLINE'       => ['type' => 'Long Integer',   'length' => 11,     'decimals' => 0],
    'YEAR'          => ['type' => 'Long Integer',   'length' => 11,     'decimals' => 0],
    'MONTH'         => ['type' => 'Long Integer',   'length' => 11,     'decimals' => 0],
    'DOCDATE'       => ['type' => 'Date',           'length' => 11,     'decimals' => 0],
    'CPID'          => ['type' => 'Char',           'length' => 10,     'decimals' => 0],
    'DBKTYPE'       => ['type' => 'Char',           'length' => 4,      'decimals' => 0],
    'LINETYPE'      => ['type' => 'Char',           'length' => 1,      'decimals' => 0],
    'ARTREF'        => ['type' => 'Char',           'length' => 21,     'decimals' => 0],
    'IMPUT'         => ['type' => 'Char',           'length' => 10,     'decimals' => 0],
    'QTYMVT'        => ['type' => 'Float',          'length' => 21,     'decimals' => 2],
    'QTYORDER'      => ['type' => 'Float',          'length' => 21,     'decimals' => 2],
    'QTYDELIV'      => ['type' => 'Float',          'length' => 21,     'decimals' => 2],
    'COMMENT'       => ['type' => 'Char',           'length' => 120,    'decimals' => 0],
    'VSTORED'       => ['type' => 'Char',           'length' => 10,     'decimals' => 0],
    'TVATTYPE'      => ['type' => 'Char',           'length' => 1,      'decimals' => 0],
    'TVANAT1'       => ['type' => 'Char',           'length' => 3,      'decimals' => 0],
    'WAREHOUSE'     => ['type' => 'Char',           'length' => 21,     'decimals' => 0],
    'PU'            => ['type' => 'Float',          'length' => 21,     'decimals' => 2],
    'PRCDISC'       => ['type' => 'Long Integer',   'length' => 11,     'decimals' => 0],
    'BASEAMN'       => ['type' => 'Float',          'length' => 21,     'decimals' => 2],
    'PAYAMN'        => ['type' => 'Float',          'length' => 21,     'decimals' => 2],
    'CPTYPE'        => ['type' => 'Char',           'length' => 1,      'decimals' => 0],
    'VATAMN'        => ['type' => 'Float',          'length' => 21,     'decimals' => 2],
    'NETPU'         => ['type' => 'Float',          'length' => 21,     'decimals' => 2],
    'DISCAMN'       => ['type' => 'Float',          'length' => 21,     'decimals' => 2],
    'PURPRICE'      => ['type' => 'Float',          'length' => 21,     'decimals' => 2]
];

$invoices_lines_schema = implode("\r\n", ['[IHISTO_FACT]', 'FileType=Fixed', 'CharSet=ascii'])."\r\n".$createFieldsSchema($invoices_lines_fields_conf);

$invoices_lines_data = [];

foreach($invoices as $invoice) {
    $index = 1;
    foreach($invoice['invoice_lines_ids'] as $line) {
        // for orders, use static memo as internal comments
        if($invoice['has_orders']) {
            $comments = ($invoice['type'] === 'invoice' ? 'F. ' : 'NC.').'VENTES COMPTOIR';
        }
        // for bookings, use invoice type, customer name and booking number
        else {
            $comments = ($invoice['type'] === 'invoice' ? 'F. ' : 'NC.').strtoupper( substr(TextTransformer::normalize($invoice['partner_id']['name']), 0, 30) ).'/'.$invoice['booking_id']['name'];
        }

        $account_code = '';
        foreach($line['price_id']['accounting_rule_id']['accounting_rule_line_ids'] as $rule_line) {
            $pos = strpos($rule_line['account_id']['code'], '_');
            $account_code = ($pos !== false) ? substr($rule_line['account_id']['code'], 0, $pos) : $rule_line['account_id']['code'];
            break;
        }

        $unit_price_discounted = $line['unit_price'];
        if($line['discount'] > 0) {
            $discount = $line['unit_price'] * $line['discount'];
            $unit_price_discounted = $line['unit_price'] - $discount;
        }

        $discount = '';
        if($line['discount'] > 0) {
            $discount = ($line['discount'] * 100).'';
        }

        $name = substr(TextTransformer::toAscii($line['name']), 0, 120);

        $map_values = [
            'DBK'       => str_pad($journal['code'], 4, ' ', STR_PAD_RIGHT),
            'FYEAR'     => str_pad(date('Y', $invoice['date']), 5,' ', STR_PAD_RIGHT),
            'DOCNO'     => str_pad(str_replace('-', '', $invoice['name']), 11,' ', STR_PAD_RIGHT),
            'DOCLINE'   => str_pad($index, 11,' ', STR_PAD_RIGHT),
            'YEAR'      => str_pad(date('Y', $invoice['date']), 11,' ', STR_PAD_RIGHT),
            'MONTH'     => str_pad(date('m', $invoice['date']), 11,' ', STR_PAD_RIGHT),
            'DOCDATE'   => str_pad(date('d/m/Y', $invoice['date']), 11,' ', STR_PAD_RIGHT),
            'CPID'      => str_pad('C'.$invoice['partner_id']['partner_identity_id']['id'], 10, ' ', STR_PAD_RIGHT),
            'DBKTYPE'   => str_pad('SAL', 4,' ', STR_PAD_RIGHT),
            'LINETYPE'  => str_pad('O', 1,' ',STR_PAD_LEFT),
            'ARTREF'    => str_pad('', 21,' ',STR_PAD_LEFT),
            'IMPUT'     => str_pad($account_code, 10,' ', STR_PAD_RIGHT),
            'QTYMVT'    => str_pad('', 21,' ', STR_PAD_LEFT),
            'QTYORDER'  => str_pad('1', 21,' ', STR_PAD_LEFT),
            'QTYDELIV'  => str_pad(sprintf('%.02f', $line['qty']), 21,' ', STR_PAD_LEFT),
            'COMMENT'   => str_pad($name, 120, ' ', STR_PAD_RIGHT),
            'VSTORED'   => str_pad('NSS  '.intval($line['price_id']['vat_rate'] * 100), 10,' ', STR_PAD_RIGHT),
            'TVATTYPE'  => str_pad('N', 1,' ', STR_PAD_RIGHT),
            'TVANAT1'   => str_pad('V', 3,' ', STR_PAD_RIGHT),
            'WAREHOUSE' => str_pad('', 21,' ',STR_PAD_LEFT),
            'PU'        => str_pad(str_replace('.', ',', sprintf('%.02f', $line['unit_price'])), 21,' ', STR_PAD_LEFT),
            'PRCDISC'   => str_pad($discount, 11,' ', STR_PAD_RIGHT),
            'BASEAMN'   => str_pad(str_replace('.', ',', sprintf('%.02f', $line['total'])), 21,' ', STR_PAD_LEFT),
            'PAYAMN'    => str_pad(str_replace('.', ',', sprintf('%.02f', $line['price'])), 21,' ', STR_PAD_LEFT),
            'CPTYPE'    => str_pad('C', 1,' ',STR_PAD_LEFT),
            'NETPU'     => str_pad(str_replace('.', ',', sprintf('%.02f', $unit_price_discounted)), 21,' ', STR_PAD_LEFT),
            'DISCAMN'   => str_pad('', 21,' ', STR_PAD_LEFT),
            'PURPRICE'  => str_pad('', 21,' ', STR_PAD_LEFT)
        ];

        $values = [];
        foreach(array_keys($invoices_lines_fields_conf) as $field) {
            $values[] = $map_values[$field];
        }

        $invoices_lines_data[] = implode('', $values);

        $index++;
    }
}

// generate the zip archive
$tmp_file = tempnam(sys_get_temp_dir(), "zip");
$zip = new ZipArchive();
if($zip->open($tmp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    // could not create the ZIP archive
    throw new Exception('Unable to create a ZIP file.', QN_ERROR_UNKNOWN);
}

// embed schema files
$zip->addFromString('CLIENTS_FACT.sch', $customers_schema);
$zip->addFromString('IHDDOC_FACT.sch', $invoices_schema);
$zip->addFromString('IHISTO_FACT.sch', $invoices_lines_schema);

// embed data files
$zip->addFromString('CLIENTS_FACT.txt', implode("\r\n", $customers_data)."\r\n");
$zip->addFromString('IHDDOC_FACT.txt', implode("\r\n", $invoices_data)."\r\n");
$zip->addFromString('IHISTO_FACT.txt', implode("\r\n", $invoices_lines_data)."\r\n");

$zip->close();

// read raw data
$data = file_get_contents($tmp_file);
unlink($tmp_file);

if($data === false) {
    throw new Exception('Unable to retrieve ZIP file content.', QN_ERROR_UNKNOWN);
}

// switch to root user
$auth->su();

// create the export archive
$export = Export::create([
    'center_office_id'      => $params['center_office_id'],
    'export_type'           => $journal['type'] === 'sales' ? 'invoices' : 'invoices_peppol',
    'data'                  => $data,
    'object_class'          => Invoice::getType(),
    'object_ids'            => json_encode(array_column($invoices, 'id'))
])
    ->read(['id'])
    ->first();

try {
    // mark processed invoices as exported
    // TODO: uncomment line below
    // Invoice::ids(array_keys($invoices_header_data))->update(['is_exported' => true]);
}
catch(Exception $e) {
    // remove export if error triggered while flagging invoices as exported
    Export::id($export['id'])->delete();
    throw $e;
}

$context->httpResponse()
        ->status(201)
        ->send();

