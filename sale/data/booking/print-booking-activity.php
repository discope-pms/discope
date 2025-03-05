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
use equal\data\DataFormatter;
use sale\booking\Booking;
use sale\booking\BookingActivity;
use sale\booking\Consumption;
use sale\booking\TimeSlot;
use Twig\Environment as TwigEnvironment;
use Twig\Extension\ExtensionInterface;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\FilesystemLoader as TwigFilesystemLoader;

list($params, $providers) = announce([
    'description'   => "Render a booking quote as a PDF document, given its id.",
    'params'        => [
        'id' => [
            'description'   => 'Identifier of the booking to print.',
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

$entity = 'sale\booking\Booking';
$parts = explode('\\', $entity);
$package = array_shift($parts);
$class_path = implode('/', $parts);
$parent = get_parent_class($entity);

$file = QN_BASEDIR."/packages/{$package}/views/{$class_path}.{$params['view_id']}.html";

if(!file_exists($file)) {
    throw new Exception("unknown_view_id", QN_ERROR_UNKNOWN_OBJECT);
}


// read booking
$fields = [
    'name',
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
            'address_street',
            'address_zip',
            'address_city',
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
    ]

];


$booking = Booking::id($params['id'])->read($fields, $params['lang'])->first(true);

if(!$booking) {
    throw new Exception("unknown_contract", QN_ERROR_UNKNOWN_OBJECT);
}

$logo_document_data = $booking['center_id']['organisation_id']['logo_document_id']['data'] ?? null;
if($logo_document_data) {
    $content_type = $booking['center_id']['organisation_id']['logo_document_id']['type'] ?? 'image/png';
    $img_url = "data:{$content_type};base64, ".base64_encode($logo_document_data);
}

$values = [
    'activities_map'             => '',
    'attn_address1'              => '',
    'attn_address2'              => '',
    'attn_name'                  => '',
    'code'                       => sprintf("%03d.%03d", intval($booking['name']) / 1000, intval($booking['name']) % 1000),
    'company_name'               => $booking['center_id']['organisation_id']['legal_name'],
    'company_reg_number'         => $booking['center_id']['organisation_id']['registration_number'],
    'customer_address1'          => $booking['customer_id']['partner_identity_id']['address_street'],
    'customer_address2'          => $booking['customer_id']['partner_identity_id']['address_zip'].' '.$booking['customer_id']['partner_identity_id']['address_city'].(($booking['customer_id']['partner_identity_id']['address_country'] != 'BE')?(' - '.$booking['customer_id']['partner_identity_id']['address_country']):''),
    'customer_name'              => substr($booking['customer_id']['partner_identity_id']['display_name'], 0, 66),
    'header_img_url'             => $img_url ?? 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8Xw8AAoMBgDTD2qgAAAAASUVORK5CYII=',
    'postal_address'             => sprintf("%s - %s %s", $booking['center_id']['organisation_id']['address_street'], $booking['center_id']['organisation_id']['address_zip'], $booking['center_id']['organisation_id']['address_city']),
    'time_slots_activities'      => [],
];

$values['i18n'] = [
    'activity_schedule'    => Setting::get_value('lodging', 'locale', 'i18n.activity_schedule', null, [], $params['lang']),
    'booking_ref'          => Setting::get_value('lodging', 'locale', 'i18n.booking_ref', null, [], $params['lang']),
    'children'             => Setting::get_value('lodging', 'locale', 'i18n.children', null, [], $params['lang']),
    'company_registry'      => Setting::get_value('lodging', 'locale', 'i18n.company_registry', null, [], $params['lang']),
    'date'                 => Setting::get_value('lodging', 'locale', 'i18n.date', null, [], $params['lang']),
    'day'                  => Setting::get_value('lodging', 'locale', 'i18n.day', null, [], $params['lang']),
    'people'               => Setting::get_value('lodging', 'locale', 'i18n.people', null, [], $params['lang']),
    'vat'                  => Setting::get_value('lodging', 'locale', 'i18n.vat', null, [], $params['lang']),
    'vat_number'           => Setting::get_value('lodging', 'locale', 'i18n.vat_number', null, [], $params['lang']),
];



if($booking['customer_id']['partner_identity_id']['id'] != $booking['customer_identity_id']['id']) {
    $values['attn_name'] = substr($booking['customer_identity_id']['display_name'], 0, 33);
    $values['attn_address1'] = $booking['customer_identity_id']['address_street'];
    $values['attn_address2'] = $booking['customer_identity_id']['address_zip'].' '.$booking['customer_identity_id']['address_city'].(($booking['customer_identity_id']['address_country'] != 'BE')?(' - '.$booking['customer_identity_id']['address_country']):'');
}


if($booking['center_id']['use_office_details']) {
    $office = $booking['center_id']['center_office_id'];
    $values['postal_address'] = $office['address_street'].' - '.$office['address_zip'].' '.$office['address_city'];
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


$activities_map = [];

$time_slots_activities = TimeSlot::search(["is_meal", "=", false])->read(['id', 'name','code', 'order' ,'schedule_from', 'schedule_to'], $params['lang'])->get();
usort($time_slots_activities, function ($a, $b) {
    return $a['order'] <=> $b['order'];
});


$values['time_slots_activities'] = $time_slots_activities;


$booking_activities = BookingActivity::search(['booking_id', '=', $booking['id'] ])
    ->read([
        'id',
        'name',
        'activity_date',
        'activity_booking_line_id' => ['id', 'description', 'product_id' => ['id','name', 'description'] ],
        'booking_line_group_id' => ['id', 'name', 'nb_pers', 'nb_children'],
        'time_slot_id' => ['id', 'code','name'],
    ])
    ->get();


usort($booking_activities, function ($a, $b) {
    return $a['booking_line_group_id']['id'] <=> $b['booking_line_group_id']['id']
        ?: $a['activity_date'] <=> $b['activity_date'];
});

foreach ($booking_activities as $activity) {
    $group = $activity['booking_line_group_id']['id'];

    if (!isset($activities_map[$group])) {
        $activities_map[$group] = [
            'info' => [
                'name' => $activity['booking_line_group_id']['name'],
                'nb_pers' => $activity['booking_line_group_id']['nb_pers'],
                'nb_children' => $activity['booking_line_group_id']['nb_children']
            ],
            'dates' => []
        ];
    }

    $date = date('d/m/Y', $activity['activity_date']) . ' (' . $days_names[date('w', $activity['activity_date'])] . ')';
    if (!isset($activities_map[$group]['dates'][$date])) {
        $activities_map[$group]['dates'][$date] = [
            'time_slots' => []
        ];

        foreach ($time_slots_activities as $time_slot) {
            $activities_map[$group]['dates'][$date]['time_slots'][$time_slot['code']] = [];
        }
    }

    $time_slot_name = $activity['time_slot_id']['code'];
    if (isset($activities_map[$group]['dates'][$date]['time_slots'][$time_slot_name])) {
        $activities_map[$group]['dates'][$date]['time_slots'][$time_slot_name] = [
            'activity' => $activity['name'],
            'description' => $activity['activity_booking_line_id']['description'],
            'product' => $activity['activity_booking_line_id']['product_id']['description']
        ];
    }
}

$values['activities_map'] = $activities_map;

try {
    $loader = new TwigFilesystemLoader(QN_BASEDIR."/packages/{$package}/views/");

    $twig = new TwigEnvironment($loader);
    /**  @var ExtensionInterface **/
    $extension  = new IntlExtension();
    $twig->addExtension($extension);
    $filter = new \Twig\TwigFilter('format_money', function ($value) {
        return number_format((float)($value),2,",",".").' €';
    });
    $twig->addFilter($filter);
    $template = $twig->load("{$class_path}.{$params['view_id']}.html");
    $html = $template->render($values);
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

$options = new DompdfOptions();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$dompdf->setPaper('A4', 'portrait');
$dompdf->loadHtml((string) $html);
$dompdf->render();

$canvas = $dompdf->getCanvas();
$font = $dompdf->getFontMetrics()->getFont("helvetica", "regular");
$canvas->page_text(530, $canvas->get_height() - 35, "p. {PAGE_NUM} / {PAGE_COUNT}", $font, 9, array(0,0,0));


$output = $dompdf->output();

$context->httpResponse()
        ->header('Content-Disposition', 'inline; filename="document.pdf"')
        ->body($output)
        ->send();


