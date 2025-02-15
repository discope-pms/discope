<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use communication\Template;
use communication\TemplatePart;
use core\setting\Setting;
use Dompdf\Dompdf;
use Dompdf\Options as DompdfOptions;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;
use equal\data\DataFormatter;
use sale\booking\Consumption;
use sale\booking\Contract;
use sale\booking\TimeSlot;
use SepaQr\Data;
use Twig\Environment as TwigEnvironment;
use Twig\Extension\ExtensionInterface;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\FilesystemLoader as TwigFilesystemLoader;

list($params, $providers) = announce([
    'description'   => "Render a contract given its ID as a PDF document.",
    'params'        => [
        'id' => [
            'description'   => 'Identifier of the contract to print.',
            'type'          => 'integer',
            'required'      => true
        ],
        'view_id' =>  [
            'description'   => 'The identifier of the view <type.name>.',
            'type'          => 'string',
            'default'       => 'print.default'
        ],
        'mode' =>  [
            'description'   => 'Mode in which document has to be rendered: simple or detailed.',
            'type'          => 'string',
            'selection'     => ['simple', 'grouped', 'detailed'],
            'default'       => 'grouped'
        ],
        'lang' =>  [
            'description'   => 'Language in which labels and multilang field have to be returned (2 letters ISO 639-1).',
            'type'          => 'string',
            'default'       => constant('DEFAULT_LANG')
        ],
        'output' =>  [
            'description'   => 'Output format of the document.',
            'type'          => 'string',
            'selection'     => ['pdf', 'html'],
            'default'       => 'pdf'
        ]
    ],
    'constants'             => ['DEFAULT_LANG', 'L10N_LOCALE'],
    'access' => [
        'visibility'        => 'protected',
        'groups'            => ['booking.default.user'],
    ],
    'response'      => [
        'content-type'      => 'application/pdf',
        'accept-origin'     => '*'
    ],
    'providers'     => ['context', 'orm']
]);


list($context, $orm) = [$providers['context'], $providers['orm']];

/*
    Retrieve the requested template
*/

$entity = 'sale\booking\Contract';
$parts = explode('\\', $entity);
$package = array_shift($parts);
$class_path = implode('/', $parts);
$parent = get_parent_class($entity);

$file = QN_BASEDIR."/packages/{$package}/views/{$class_path}.{$params['view_id']}.html";

if(!file_exists($file)) {
    throw new Exception("unknown_view_id", QN_ERROR_UNKNOWN_OBJECT);
}


// read contract
$fields = [
    'created',
    'booking_id' => [
        'id', 'name', 'modified', 'date_from', 'date_to', 'time_from', 'time_to', 'price',
        'type_id' => [
            'id',
            'booking_schedule_layout'
        ],
        'customer_identity_id' => [
                'id',
                'display_name',
                'address_street', 'address_dispatch', 'address_city', 'address_zip', 'address_country',
                'phone',
                'mobile',
                'email'
        ],
        'customer_id' => [
            'partner_identity_id' => [
                'id',
                'display_name',
                'type',
                'address_street', 'address_dispatch', 'address_city', 'address_zip', 'address_country',
                'type',
                'phone',
                'mobile',
                'email',
                'has_vat',
                'vat_number'
            ]
        ],
        'center_id' => [
            'name',
            'manager_id' => ['name'],
            'address_street',
            'address_city',
            'address_zip',
            'phone',
            'email',
            'bank_account_iban',
            'bank_account_bic',
            'template_category_id',
            'use_office_details',
            'center_office_id' => [
                'code',
                'address_street',
                'address_city',
                'address_zip',
                'phone',
                'email',
                'signature',
                'bank_account_iban',
                'bank_account_bic'
            ],
            'organisation_id' => [
                'id',
                'legal_name',
                'address_street', 'address_zip', 'address_city',
                'email',
                'phone',
                'fax',
                'website',
                'registration_number',
                'has_vat',
                'vat_number',
                'bank_account_iban',
                'bank_account_bic',
                'signature',
                'logo_document_id' => ['id', 'type', 'data']
            ]
        ],
        'contacts_ids' => [
            'type',
            'partner_identity_id' => [
                'display_name',
                'phone',
                'mobile',
                'email',
                'title'
            ]
        ],
        'fundings_ids' => [
            'description',
            'due_date',
            'is_paid',
            'due_amount',
            'payment_reference',
            'payment_deadline_id' => ['name']
        ]
    ],
    'contract_line_groups_ids' => [
        'name',
        'is_pack',
        'fare_benefit',
        'total',
        'price',
        'rate_class_id' => ['id', 'name', 'description'],
        'contract_line_id' => [
            'name',
            'qty',
            'unit_price',
            'discount',
            'free_qty',
            'vat_rate',
            'total',
            'price'
        ],
        'contract_lines_ids' => [
            'name',
            'description',
            'qty',
            'unit_price',
            'discount',
            'free_qty',
            'vat_rate',
            'total',
            'price'
        ]
    ],
    'price',
    'total'
];


$contract = Contract::id($params['id'])->read($fields, $params['lang'])->first(true);

if(!$contract) {
    throw new Exception("unknown_contract", QN_ERROR_UNKNOWN_OBJECT);
}


/*
    extract required data and compose the $value map for the twig template
*/

$booking = $contract['booking_id'];


if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

$logo_document_data = $booking['center_id']['organisation_id']['logo_document_id']['data'] ?? null;
if($logo_document_data) {
    $content_type = $booking['center_id']['organisation_id']['logo_document_id']['type'] ?? 'image/png';
    $img_url = "data:{$content_type};base64, ".base64_encode($logo_document_data);
}

$member_name = lodging_booking_print_contract_formatMember($booking);

$center_office_code = (isset( $booking['center_id']['center_office_id']['code']) && $booking['center_id']['center_office_id']['code'] == 1) ? 'GG' : 'GA';

$values = [
    'header_img_url'        => $img_url ?? 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8Xw8AAoMBgDTD2qgAAAAASUVORK5CYII=',
    'contract_header_html'  => '',
    'contract_notice_html'  => '',


    // by default, there is no ATTN - if required, it is set below
    'attn_name'             => '',
    'attn_address1'         => '',
    'attn_address2'         => '',

    'contact_name'          => '',
    'contact_phone'         => (strlen($booking['customer_id']['partner_identity_id']['phone']))?$booking['customer_id']['partner_identity_id']['phone']:$booking['customer_id']['partner_identity_id']['mobile'],
    'contact_email'         => $booking['customer_id']['partner_identity_id']['email'],

    'customer_name'         => substr($booking['customer_id']['partner_identity_id']['display_name'], 0,  66),
    'customer_address1'     => $booking['customer_id']['partner_identity_id']['address_street'],
    'customer_address_dispatch'     => $booking['customer_id']['partner_identity_id']['address_dispatch'],
    'customer_address2'     => $booking['customer_id']['partner_identity_id']['address_zip'].' '.$booking['customer_id']['partner_identity_id']['address_city'].(($booking['customer_id']['partner_identity_id']['address_country'] != 'BE')?(' - '.$booking['customer_id']['partner_identity_id']['address_country']):''),
    'customer_country'      => $booking['customer_id']['partner_identity_id']['address_country'],
    'customer_has_vat'      => (int) $booking['customer_id']['partner_identity_id']['has_vat'],
    'customer_vat'          => $booking['customer_id']['partner_identity_id']['vat_number'],

    'member'                => substr($member_name, 0, 33) . ((strlen($member_name) > 33) ? '...' : ''),
    'date'                  => date('d/m/Y', $contract['created']),
    'code'                  => sprintf("%03d.%03d", intval($booking['name']) / 1000, intval($booking['name']) % 1000),
    'center'                => $booking['center_id']['name'],
    'center_address'        => $booking['center_id']['address_street'].' - '.$booking['center_id']['address_zip'].' '.$booking['center_id']['address_city'],
    'postal_address'        => sprintf("%s - %s %s", $booking['center_id']['organisation_id']['address_street'], $booking['center_id']['organisation_id']['address_zip'], $booking['center_id']['organisation_id']['address_city']),
    'center_contact1'       => (isset($booking['center_id']['manager_id']['name']))?$booking['center_id']['manager_id']['name']:'',
    'center_contact2'       => DataFormatter::format($booking['center_id']['phone'], 'phone').' - '.$booking['center_id']['email'],

    // by default, we use center contact details (overridden in case Center has a management Office, see below)
    'center_phone'          => DataFormatter::format($booking['center_id']['phone'], 'phone'),
    'center_email'          => $booking['center_id']['email'],
    'center_signature'      => $booking['center_id']['organisation_id']['signature'],
    'center_office'         => $center_office_code,
    'period'                => date('d/m/Y', $booking['date_from']).' '.date('H:i', $booking['time_from']).' - '.date('d/m/Y', $booking['date_to']).' '.date('H:i', $booking['time_to']),

    'price'                 => $contract['price'],
    'total'                 => $contract['total'],

    'company_name'          => $booking['center_id']['organisation_id']['legal_name'],
    'company_address'       => sprintf("%s %s %s", $booking['center_id']['organisation_id']['address_street'], $booking['center_id']['organisation_id']['address_zip'], $booking['center_id']['organisation_id']['address_city']),
    'company_email'         => $booking['center_id']['organisation_id']['email'],
    'company_phone'         => DataFormatter::format($booking['center_id']['organisation_id']['phone'], 'phone'),
    'company_fax'           => DataFormatter::format($booking['center_id']['organisation_id']['fax'], 'phone'),
    'company_website'       => $booking['center_id']['organisation_id']['website'],
    'company_reg_number'    => $booking['center_id']['organisation_id']['registration_number'],
    'company_has_vat'       => $booking['center_id']['organisation_id']['has_vat'],
    'company_vat_number'    => $booking['center_id']['organisation_id']['vat_number'],


    // by default, we use organisation payment details (overridden in case Center has a management Office, see below)
    'company_iban'          => DataFormatter::format($booking['center_id']['organisation_id']['bank_account_iban'], 'iban'),
    'company_bic'           => DataFormatter::format($booking['center_id']['organisation_id']['bank_account_bic'], 'bic'),

    'installment_date'      => '',
    'installment_amount'    => 0,
    'installment_reference' => '',
    // default to transparent pixel
    'installment_qr_url'    => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8Xw8AAoMBgDTD2qgAAAAASUVORK5CYII=',
    'fundings'                   => [],
    'lines'                      => [],
    'tax_lines'                  => [],
    'consumptions_tye'           => isset($booking['type_id']['booking_schedule_layout'])?$booking['type_id']['booking_schedule_layout']:'simple',
    'consumptions_map_simple'    => [],
    'consumptions_map_detailed'  => [],
    'benefit_lines'              => []
];

/*
    retrieve terms translations
*/
$values['i18n'] = [
    'invoice'           => Setting::get_value('sale', 'locale', 'terms.invoice', null, [], $params['lang']),
    'quote'             => Setting::get_value('sale', 'locale', 'terms.quote', null, [], $params['lang']),
    'option'            => Setting::get_value('sale', 'locale', 'terms.option', null, [], $params['lang']),
    'contract'          => Setting::get_value('sale', 'locale', 'terms.contract', null, [], $params['lang']),
    'booking_invoice'   => Setting::get_value('lodging', 'locale', 'i18n.booking_invoice', null, [], $params['lang']),
    'booking_quote'     => Setting::get_value('lodging', 'locale', 'i18n.booking_quote', null, [], $params['lang']),
    'booking_contract'  => Setting::get_value('lodging', 'locale', 'i18n.booking_contract', null, [], $params['lang']),
    'credit_note'       => Setting::get_value('lodging', 'locale', 'i18n.credit_note', null, [], $params['lang']),
    'company_registry'  => Setting::get_value('lodging', 'locale', 'i18n.company_registry', null, [], $params['lang']),
    'vat_number'        => Setting::get_value('lodging', 'locale', 'i18n.vat_number', null, [], $params['lang']),
    'vat'               => Setting::get_value('lodging', 'locale', 'i18n.vat', null, [], $params['lang']),
    'your_stay_at'      => Setting::get_value('lodging', 'locale', 'i18n.your_stay_at', null, [], $params['lang']),
    'contact'           => Setting::get_value('lodging', 'locale', 'i18n.contact', null, [], $params['lang']),
    'period'            => Setting::get_value('lodging', 'locale', 'i18n.period', null, [], $params['lang']),
    'member'            => Setting::get_value('lodging', 'locale', 'i18n.member', null, [], $params['lang']),
    'phone'             => Setting::get_value('lodging', 'locale', 'i18n.phone', null, [], $params['lang']),
    'email'             => Setting::get_value('lodging', 'locale', 'i18n.email', null, [], $params['lang']),
    'booking_ref'       => Setting::get_value('lodging', 'locale', 'i18n.booking_ref', null, [], $params['lang']),
    'your_reference'    => Setting::get_value('lodging', 'locale', 'i18n.your_reference', null, [], $params['lang']),
    'number_short'      => Setting::get_value('lodging', 'locale', 'i18n.number_short', null, [], $params['lang']),
    'date'              => Setting::get_value('lodging', 'locale', 'i18n.date', null, [], $params['lang']),
    'status'            => Setting::get_value('lodging', 'locale', 'i18n.status', null, [], $params['lang']),
    'paid'              => Setting::get_value('lodging', 'locale', 'i18n.paid', null, [], $params['lang']),
    'to_pay'            => Setting::get_value('lodging', 'locale', 'i18n.to_pay', null, [], $params['lang']),
    'to_refund'         => Setting::get_value('lodging', 'locale', 'i18n.to_refund', null, [], $params['lang']),
    'product_label'     => Setting::get_value('lodging', 'locale', 'i18n.product_label', null, [], $params['lang']),
    'quantity_short'    => Setting::get_value('lodging', 'locale', 'i18n.quantity_short', null, [], $params['lang']),
    'freebies_short'    => Setting::get_value('lodging', 'locale', 'i18n.freebies_short', null, [], $params['lang']),
    'unit_price'        => Setting::get_value('lodging', 'locale', 'i18n.unit_price', null, [], $params['lang']),
    'discount_short'    => Setting::get_value('lodging', 'locale', 'i18n.discount_short', null, [], $params['lang']),
    'taxes'             => Setting::get_value('lodging', 'locale', 'i18n.taxes', null, [], $params['lang']),
    'price'             => Setting::get_value('lodging', 'locale', 'i18n.price', null, [], $params['lang']),
    'total'             => Setting::get_value('lodging', 'locale', 'i18n.total', null, [], $params['lang']),
    'price_tax_excl'    => Setting::get_value('lodging', 'locale', 'i18n.price_tax_excl', null, [], $params['lang']),
    'total_tax_excl'    => Setting::get_value('lodging', 'locale', 'i18n.total_tax_excl', null, [], $params['lang']),
    'total_tax_incl'    => Setting::get_value('lodging', 'locale', 'i18n.total_tax_incl', null, [], $params['lang']),
    'stay_total_tax_incl'   => Setting::get_value('lodging', 'locale', 'i18n.stay_total_tax_incl', null, [], $params['lang']),
    'balance_of'            => Setting::get_value('lodging', 'locale', 'i18n.balance_of', null, [], $params['lang']),
    'to_be_paid_before'     => Setting::get_value('lodging', 'locale', 'i18n.to_be_paid_before', null, [], $params['lang']),
    'communication'         => Setting::get_value('lodging', 'locale', 'i18n.communication', null, [], $params['lang']),
    'amount_to_be refunded' => Setting::get_value('lodging', 'locale', 'i18n.amount_to_be refunded', null, [], $params['lang']),
    'advantage_included'    => Setting::get_value('lodging', 'locale', 'i18n.advantage_included', null, [], $params['lang']),
    'fare_category'         => Setting::get_value('lodging', 'locale', 'i18n.fare_category', null, [], $params['lang']),
    'advantage'             => Setting::get_value('lodging', 'locale', 'i18n.advantage', null, [], $params['lang']),
    'consumptions_details'  => Setting::get_value('lodging', 'locale', 'i18n.consumptions_details', null, [], $params['lang']),
    'day'                   => Setting::get_value('lodging', 'locale', 'i18n.day', null, [], $params['lang']),
    'meals_morning'         => Setting::get_value('lodging', 'locale', 'i18n.meals_morning', null, [], $params['lang']),
    'meals_midday'          => Setting::get_value('lodging', 'locale', 'i18n.meals_midday', null, [], $params['lang']),
    'meal_evening'          => Setting::get_value('lodging', 'locale', 'i18n.meal_evening', null, [], $params['lang']),
    'nights'                => Setting::get_value('lodging', 'locale', 'i18n.nights', null, [], $params['lang']),
    'payments_schedule'     => Setting::get_value('lodging', 'locale', 'i18n.payments_schedule', null, [], $params['lang']),
    'payment'               => Setting::get_value('lodging', 'locale', 'i18n.payment', null, [], $params['lang']),
    'already_paid'          => Setting::get_value('lodging', 'locale', 'i18n.already_paid', null, [], $params['lang']),
    'amount'                => Setting::get_value('lodging', 'locale', 'i18n.amount', null, [], $params['lang']),
    'yes'                   => Setting::get_value('lodging', 'locale', 'i18n.yes', null, [], $params['lang']),
    'no'                    => Setting::get_value('lodging', 'locale', 'i18n.no', null, [], $params['lang']),
    'the_amount_of'         => Setting::get_value('lodging', 'locale', 'i18n.the_amount_of', null, [], $params['lang']),
    'must_be_paid_before'   => Setting::get_value('lodging', 'locale', 'i18n.must_be_paid_before', null, [], $params['lang']),
    'date_and_signature'    => Setting::get_value('lodging', 'locale', 'i18n.date_and_signature', null, [], $params['lang']),
    'time_slot'             => Setting::get_value('lodging', 'locale', 'i18n.time_slot', null, [], $params['lang']),
    'snack'                 => Setting::get_value('lodging', 'locale', 'i18n.snack', null, [], $params['lang']),
    'meals'                 => Setting::get_value('lodging', 'locale', 'i18n.meals', null, [], $params['lang']),
];

/**
 * Add info for ATTN, if required.
 * If the invoice is emitted to a partner distinct from the booking customer, the latter is ATTN and the former is considered as the customer.
 */

if($booking['customer_id']['partner_identity_id']['id'] != $booking['customer_identity_id']['id']) {
    $values['attn_name'] = substr($booking['customer_identity_id']['display_name'], 0, 33);
    $values['attn_address1'] = $booking['customer_identity_id']['address_street'];
    $values['attn_address2'] = $booking['customer_identity_id']['address_zip'].' '.$booking['customer_identity_id']['address_city'].(($booking['customer_identity_id']['address_country'] != 'BE')?(' - '.$booking['customer_identity_id']['address_country']):'');
}

/*
    override contact and payment details with center's office, if set
*/
if($booking['center_id']['use_office_details']) {
    $office = $booking['center_id']['center_office_id'];
    $values['company_iban'] = DataFormatter::format($office['bank_account_iban'], 'iban');
    $values['company_bic'] = DataFormatter::format($office['bank_account_bic'], 'bic');
    $values['center_phone'] = DataFormatter::format($office['phone'], 'phone');
    $values['center_email'] = $office['email'];
    $values['center_signature'] = $office['signature'];
    $values['postal_address'] = $office['address_street'].' - '.$office['address_zip'].' '.$office['address_city'];
}



/*
    retrieve templates
*/
if($booking['center_id']['template_category_id']) {

    $template = Template::search([
            ['category_id', '=', $booking['center_id']['template_category_id']],
            ['code', '=', 'contract'],
            ['type', '=', 'contract']
        ])
        ->read(['parts_ids' => ['name', 'value']], $params['lang'])
        ->first(true);

    foreach($template['parts_ids'] as $part_id => $part) {
        if($part['name'] == 'header') {
            $values['contract_header_html'] = $part['value'].$values['center_signature'];
        }
        elseif($part['name'] == 'notice') {
            $values['contract_notice_html'] = $part['value'];
        }
    }

}

// fetch template parts that are common to all offices

$template_part = TemplatePart::search(['name', '=', 'advantage_notice'])
    ->read(['value'], $params['lang'])
    ->first(true);

if($template_part) {
    $values['advantage_notice_html'] = $template_part['value'];
}

$template_part = TemplatePart::search(['name', '=', 'contract_agreement'])
    ->read(['value'], $params['lang'])
    ->first(true);

if($template_part) {
    $values['contract_agreement_html'] = $template_part['value'];
}

/*
    feed lines
*/

$lines = [];

// all lines are in groups
foreach($contract['contract_line_groups_ids'] as $contract_line_group) {
    // generate group label
    $group_label = (strlen($contract_line_group['name']))?$contract_line_group['name']:'';

    if($contract_line_group['is_pack']) {
        // group is a product pack (bundle) with own price: add a single line with details
        $group_is_pack = true;

        $line = [
            'name'          => $group_label,
            'price'         => $contract_line_group['contract_line_id']['price'],
            'total'         => $contract_line_group['contract_line_id']['total'],
            'unit_price'    => $contract_line_group['contract_line_id']['unit_price'],
            'vat_rate'      => $contract_line_group['contract_line_id']['vat_rate'],
            'qty'           => $contract_line_group['contract_line_id']['qty'],
            'free_qty'      => $contract_line_group['contract_line_id']['free_qty'],
            'discount'      => $contract_line_group['contract_line_id']['discount'],
            'is_group'      => false,
            'is_pack'       => true
        ];
        $lines[] = $line;

        if($params['mode'] == 'detailed') {
            foreach($contract_line_group['contract_lines_ids'] as $contract_line) {
                $line = [
                    'name'          => $contract_line['name'],
                    'qty'           => $contract_line['qty'],
                    'price'         => null,
                    'total'         => null,
                    'unit_price'    => null,
                    'vat_rate'      => null,
                    'discount'      => null,
                    'free_qty'      => null,
                    'is_group'      => false,
                    'is_pack'       => false
                ];
                $lines[] = $line;
            }
        }
    }
    else {
        // group is a pack with no own price
        $group_is_pack = false;

        if($params['mode'] == 'grouped') {
            $line = [
                'name'          => $group_label,
                'price'         => $contract_line_group['price'],
                'total'         => $contract_line_group['total'],
                'unit_price'    => $contract_line_group['total'],
                'vat_rate'      => (floatval($contract_line_group['price'])/floatval($contract_line_group['total']) - 1.0),
                'qty'           => 1,
                'free_qty'      => 0,
                'discount'      => 0,
                'is_group'      => true,
                'is_pack'       => false
            ];
        }
        else {
            $line = [
                'name'          => $group_label,
                'price'         => null,
                'total'         => null,
                'unit_price'    => null,
                'vat_rate'      => null,
                'qty'           => null,
                'free_qty'      => null,
                'discount'      => null,
                'is_group'      => true
            ];
        }
        $lines[] = $line;

        $group_lines = [];

        foreach($contract_line_group['contract_lines_ids'] as $contract_line) {

            if($params['mode'] == 'grouped') {
                $line = [
                    'name'          => (strlen($contract_line['description']) > 0)?$contract_line['description']:$contract_line['name'],
                    'price'         => null,
                    'total'         => null,
                    'unit_price'    => null,
                    'vat_rate'      => null,
                    'qty'           => $contract_line['qty'],
                    'discount'      => null,
                    'free_qty'      => null,
                    'is_group'      => false,
                    'is_pack'       => false
                ];
            }
            else {
                $line = [
                    'name'          => (strlen($contract_line['description']) > 0)?$contract_line['description']:$contract_line['name'],
                    'price'         => $contract_line['price'],
                    'total'         => $contract_line['total'],
                    'unit_price'    => $contract_line['unit_price'],
                    'vat_rate'      => $contract_line['vat_rate'],
                    'qty'           => $contract_line['qty'],
                    'discount'      => $contract_line['discount'],
                    'free_qty'      => $contract_line['free_qty'],
                    'is_group'      => false,
                    'is_pack'       => false
                ];

            }

            $group_lines[] = $line;
        }

        if($params['mode'] == 'detailed' || $params['mode'] == 'grouped') {
            foreach($group_lines as $line) {
                $lines[] = $line;
            }
        }
        // mode is 'simple' : group lines by VAT rate
        else {
            $group_tax_lines = [];
            foreach($group_lines as $line) {
                $vat_rate = strval($line['vat_rate']);
                if(!isset($group_tax_lines[$vat_rate])) {
                    $group_tax_lines[$vat_rate] = 0;
                }
                $group_tax_lines[$vat_rate] += $line['total'];
            }

            if(count(array_keys($group_tax_lines)) <= 1) {
                $pos = count($lines)-1;
                foreach($group_tax_lines as $vat_rate => $total) {
                    $lines[$pos]['qty'] = 1;
                    $lines[$pos]['vat_rate'] = $vat_rate;
                    $lines[$pos]['total'] = $total;
                    $lines[$pos]['price'] = $total * (1 + $vat_rate);
                }
            }
            else {
                foreach($group_tax_lines as $vat_rate => $total) {
                    $line = [
                        'name'      => 'Services avec TVA '.($vat_rate*100).'%',
                        'qty'       => 1,
                        'vat_rate'  => $vat_rate,
                        'total'     => $total,
                        'price'     => $total * (1 + $vat_rate)
                    ];
                    $lines[] = $line;
                }
            }
        }
    }
}

$values['lines'] = $lines;


/*
    compute fare benefit detail
*/
$values['benefit_lines'] = [];

foreach($contract['contract_line_groups_ids'] as $group) {
    if($group['fare_benefit'] == 0) {
        continue;
    }
    $index = $group['rate_class_id']['description'];
    if(!isset($values['benefit_lines'][$index])) {
        $values['benefit_lines'][$index] = [
            'name'  => $index,
            'value' => $group['fare_benefit']
        ];
    }
    else {
        $values['benefit_lines'][$index]['value'] += $group['fare_benefit'];
    }
}


/*
    retrieve final VAT and group by rate
*/
foreach($lines as $line) {
    $vat_rate = $line['vat_rate'];
    $tax_label = $values['i18n']['vat'].' '.strval( intval($vat_rate * 100) ).'%';
    $vat = $line['price'] - $line['total'];
    if(!isset($values['tax_lines'][$tax_label])) {
        $values['tax_lines'][$tax_label] = 0;
    }
    $values['tax_lines'][$tax_label] += $vat;
}


/*
    retrieve contact for booking
*/
foreach($booking['contacts_ids'] as $contact) {
    if(strlen($values['contact_name']) == 0 || $contact['type'] == 'booking') {
        // overwrite data of customer with contact info
        $values['contact_name'] = str_replace(["Dr", "Ms", "Mrs", "Mr", "Pr"], ["Dr", "Melle", "Mme", "Mr", "Pr"], $contact['partner_identity_id']['title']) . ' ' . $contact['partner_identity_id']['display_name'];
        $values['contact_phone'] = (strlen($contact['partner_identity_id']['phone']))?$contact['partner_identity_id']['phone']:$contact['partner_identity_id']['mobile'];
        $values['contact_email'] = $contact['partner_identity_id']['email'];
    }
}

/*
    inject expected fundings and find the first installment
*/
$installment_date = PHP_INT_MAX;
$installment_amount = 0;
$installment_ref = '';

foreach($booking['fundings_ids'] as $funding) {

    if($funding['due_date'] < $installment_date && !$funding['is_paid']) {
        $installment_date = $funding['due_date'];
        $installment_ref = $funding['payment_reference'];
        $installment_amount = $funding['due_amount'];
    }
    $line = [
        'name'          => (strlen($funding['payment_deadline_id']['name']))?$funding['payment_deadline_id']['name']:$funding['description'],
        'due_date'      => date('d/m/Y', $funding['due_date']),
        'due_amount'    => $funding['due_amount'],
        'is_paid'       => $funding['is_paid'],
        'reference'     =>  DataFormatter::format($funding['payment_reference'], 'scor')
    ];
    $values['fundings'][] = $line;
}


if($installment_date == PHP_INT_MAX) {
    // no funding found : the final invoice will be release and generate a funding
    // qr code is not generated
}
else if ($installment_amount > 0) {
    $values['installment_date'] = date('d/m/Y', $installment_date);
    $values['installment_amount'] = (float) $installment_amount;
    $values['installment_reference'] = DataFormatter::format($installment_ref, 'scor');

    // generate a QR code
    try {
        $paymentData = Data::create()
            ->setServiceTag('BCD')
            ->setIdentification('SCT')
            ->setName($values['company_name'])
            ->setIban(str_replace(' ', '', $booking['center_id']['bank_account_iban']))
            ->setBic(str_replace(' ', '', $booking['center_id']['bank_account_bic']))
            ->setRemittanceReference($values['installment_reference'])
            ->setAmount($values['installment_amount']);

        $result = Builder::create()
            ->data($paymentData)
            ->errorCorrectionLevel(new ErrorCorrectionLevelMedium()) // required by EPC standard
            ->build();

        $dataUri = $result->getDataUri();
        $values['installment_qr_url'] = $dataUri;

    }
    catch(Exception $exception) {
        // unknown error
    }
}

$days_languages = [
    ['fr' => 'Dimanche',  'en' => 'Sunday',    'nl' => 'Zondag'],
    ['fr' => 'Lundi',     'en' => 'Monday',    'nl' => 'Maandag'],
    ['fr' => 'Mardi',     'en' => 'Tuesday',   'nl' => 'Dinsdag'],
    ['fr' => 'Mercredi',  'en' => 'Wednesday', 'nl' => 'Woensdag'],
    ['fr' => 'Jeudi',     'en' => 'Thursday',  'nl' => 'Donderdag'],
    ['fr' => 'Vendredi',  'en' => 'Friday',    'nl' => 'Vrijdag'],
    ['fr' => 'Samedi',    'en' => 'Saturday',  'nl' => 'Zaterdag']
];

$days_names = array_map(function($day) use ($params) {
    return $day[$params['lang']];
}, $days_languages);


/*
    Generate consumptions map simple
*/


$consumptions_map_simple = [];

$consumptions_simple = Consumption::search([ ['booking_id', '=', $booking['id']], ['type', '=', 'book'] ])
    ->read([
        'id',
        'date',
        'qty',
        'is_meal',
        'rental_unit_id',
        'is_accomodation',
        'time_slot_id' => ['id', 'code'],
        'schedule_to'
    ])
    ->get();

// sort consumptions on dates
usort($consumptions_simple, function ($a, $b) {
    return strcmp($a['date'], $b['date']);
});

foreach($consumptions_simple as $cid => $consumption) {
    // #memo - we only count overnight accommodations (a sojourn always has nb_nights+1 days)
    // ignore accommodation consumptions that do not end at midnight (24:00:00)
    if($consumption['is_accomodation'] && $consumption['schedule_to'] != 86400) {
        continue;
    }

    $date = date('d/m/Y', $consumption['date']).' ('.$days_names[date('w', $consumption['date'])].')';

    if(!isset($consumptions_map_simple[$date])) {
        $consumptions_map_simple[$date] = [];
    }
    if(!isset($consumptions_map_simple['total'])) {
        $consumptions_map_simple['total'] = [];
    }

    if($consumption['is_meal']) {
        $consumption_time_slot_code = $consumption['time_slot_id']['code'];
        // #todo - remove this temporary mapping (once time_slot_id will be correctly set)
        $consumption_time_slot_code = [
                1 => 'B',
                2 => 'L',
                3 => 'D',
                4 => 'D'
            ][$consumption['time_slot_id']['id']];

        if(!isset($consumptions_map_simple[$date][$consumption_time_slot_code])) {
            $consumptions_map_simple[$date][$consumption_time_slot_code] = 0;
        }
        if(!isset($consumptions_map_simple['total'][$consumption_time_slot_code])) {
            $consumptions_map_simple['total'][$consumption_time_slot_code] = 0;
        }
        $consumptions_map_simple[$date][$consumption_time_slot_code] += $consumption['qty'];
        $consumptions_map_simple['total'][$consumption_time_slot_code] += $consumption['qty'];
    }
    elseif($consumption['is_accomodation']) {
        if(!isset($consumptions_map_simple[$date]['night'])) {
            $consumptions_map_simple[$date]['night'] = 0;
        }
        if(!isset($consumptions_map_simple['total']['night'])) {
            $consumptions_map_simple['total']['night'] = 0;
        }
        $consumptions_map_simple[$date]['night'] += $consumption['qty'];
        $consumptions_map_simple['total']['night'] += $consumption['qty'];
    }
}

$values['consumptions_map_simple'] = $consumptions_map_simple;


/*
    Generate consumptions map detailed
*/



$consumptions_map_detailed = [
        'total' => [
            'total_snack'  => 0,
            'total_meals'  => 0,
            'total_nights' => 0
        ]
    ];

$consumptions_detailed = Consumption::search(['booking_id', '=', $booking['id'] ])
    ->read([
        'id',
        'name',
        'date',
        'qty',
        'is_meal',
        'is_snack',
        'rental_unit_id',
        'is_accomodation',
        'type',
        'time_slot_id' => ['id', 'code','name'],
        'schedule_to'
    ])
    ->get();

$time_slots_ids = TimeSlot::search(["code", "<>", "EV"])->read(['id', 'code', 'order'], $params['lang'])->get();

usort($time_slots_ids, function ($a, $b) {
    return $a['order'] <=> $b['order'];
});


// sort consumptions on dates
usort($consumptions_detailed, function ($a, $b) {
    return strcmp($a['date'], $b['date']);
});

foreach($consumptions_detailed as $cid => $consumption) {
    // #memo - we only count overnight accommodations (a sojourn always has nb_nights+1 days)
    // ignore accommodation consumptions that do not end at midnight (24:00:00)
    if($consumption['is_accomodation'] && $consumption['schedule_to'] != 86400) {
        continue;
    }

    $date = date('d/m/Y', $consumption['date']).' ('.$days_names[date('w', $consumption['date'])].')';
    if (!isset($consumptions_map_detailed[$date])) {
        $consumptions_map_detailed[$date] = [
            'total_nights'  => 0,
            'time_slots'    => [],
        ];
        foreach ($time_slots_ids as $time_slot_id) {
            $time_slot = TimeSlot::id($time_slot_id)->read(['name'], $params['lang'])->first(true);
            $time_slot_index = $time_slot['name'];
            $consumptions_map_detailed[$date]['time_slots'][$time_slot_index] = [
                'total_snack'   => 0,
                'total_meals'   => 0,
            ];
        }
    }

    $consumption_time_slot = TimeSlot::search(["code", "=", "AM"])->read(['id'])->first(true);
    $consumption_time_slot_id = $consumption_time_slot['id'];
    if (isset($consumption['time_slot_id'])) {
        if ($consumption['is_accomodation']) {
            $consumption_time_slot = TimeSlot::search(["code", "=", "EV"])->read(['id'])->first(true);
            $consumption_time_slot_id = $consumption_time_slot['id'];
        } else {
            $consumption_time_slot_id = $consumption['time_slot_id']['id'];
        }
    }
    $time_slot = TimeSlot::id($consumption_time_slot_id)->read(['name'], $params['lang'])->first(true);
    $time_slot_index = $time_slot['name'];

    if($consumption['is_meal']) {
        $consumptions_map_detailed[$date]['time_slots'][$time_slot_index]['total_meals'] += $consumption['qty'];
        $consumptions_map_detailed['total']['total_meals'] += $consumption['qty'];
    }
    elseif($consumption['is_snack']) {
        $consumptions_map_detailed[$date]['time_slots'][$time_slot_index]['total_snack'] += $consumption['qty'];
        $consumptions_map_detailed['total']['total_snack'] += $consumption['qty'];
    }
    elseif($consumption['is_accomodation'] && $consumption['type'] == 'book') {
        $consumptions_map_detailed[$date]['total_nights'] += $consumption['qty'];
        $consumptions_map_detailed['total']['total_nights'] += $consumption['qty'];
    }
}

$values['consumptions_map_detailed'] = $consumptions_map_detailed;

/*
    Inject all values into the template
*/

try {
    $loader = new TwigFilesystemLoader(QN_BASEDIR."/packages/{$package}/views/");

    $twig = new TwigEnvironment($loader);
    /**  @var ExtensionInterface **/
    $extension  = new IntlExtension();
    $twig->addExtension($extension);
    $currency = Setting::get_value('core', 'units', 'currency', '€');
    // #todo - temp workaround against LOCALE mixups
    $filter = new \Twig\TwigFilter('format_money', function ($value) use($currency) {
        return number_format((float)($value), 2, ",", ".") . ' ' .$currency;
    });
    $twig->addFilter($filter);

    $template = $twig->load("{$class_path}.{$params['view_id']}.html");

    // #todo - use localization prefs for rendering (independent from locale)
    // setlocale(LC_ALL, constant('L10N_LOCALE'));
    // render template
    $html = $template->render($values);
    // restore original locale
    // setlocale(LC_ALL, 0);
}
catch(Exception $e) {
    trigger_error("ORM::error while parsing template - ".$e->getMessage(), QN_REPORT_DEBUG);
    throw new Exception("template_parsing_issue", QN_ERROR_INVALID_CONFIG);
}

if($params['output'] == 'html') {
    $context->httpResponse()
        ->header('Content-Type', 'text/html')
        ->body($html)
        ->send();
    exit(0);
}

/*
    Convert HTML to PDF
*/

// instantiate and use the dompdf class
$options = new DompdfOptions();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$dompdf->setPaper('A4', 'portrait');
$dompdf->loadHtml((string) $html);
$dompdf->render();

$canvas = $dompdf->getCanvas();
$font = $dompdf->getFontMetrics()->getFont("helvetica", "regular");
$canvas->page_text(530, $canvas->get_height() - 35, "p. {PAGE_NUM} / {PAGE_COUNT}", $font, 9, array(0,0,0));
// $canvas->page_text(40, $canvas->get_height() - 35, "Export", $font, 9, array(0,0,0));


// get generated PDF raw binary
$output = $dompdf->output();

$context->httpResponse()
        // ->header('Content-Disposition', 'attachment; filename="document.pdf"')
        ->header('Content-Disposition', 'inline; filename="document.pdf"')
        ->body($output)
        ->send();



function lodging_booking_print_contract_formatMember($booking) {
    $id = $booking['customer_id']['partner_identity_id']['id'];
    $code = ltrim(sprintf("%3d.%03d.%03d", intval($id) / 1000000, (intval($id) / 1000) % 1000, intval($id)% 1000), '0');
    return $code.' - '.$booking['customer_id']['partner_identity_id']['display_name'];
}
