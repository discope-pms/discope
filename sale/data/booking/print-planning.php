<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use core\setting\Setting;
use Dompdf\Dompdf;
use Dompdf\Options as DompdfOptions;
use sale\booking\Booking;
use Twig\Environment as TwigEnvironment;
use Twig\Extension\ExtensionInterface;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\FilesystemLoader as TwigFilesystemLoader;

[$params, $providers] = eQual::announce([
    'description'   => "Render pending bookings planning for a specific.",
    'params'        => [
        'view_id' =>  [
            'type'          => 'string',
            'description'   => "The identifier of the view <type.name>.",
            'default'       => 'print.pending'
        ],
        'lang' =>  [
            'type'          => 'string',
            'description'   => "Language in which labels and multilang field have to be returned (2 letters ISO 639-1).",
            'default'       => constant('DEFAULT_LANG')
        ],
        'output' =>  [
            'type'          => 'string',
            'description'   => "Output format of the document.",
            'selection'     => ['pdf', 'html'],
            'default'       => 'pdf'
        ]
    ],
    'constants'             => ['DEFAULT_LANG', 'L10N_LOCALE'],
    'access'        => [
        'visibility'        => 'protected',
        'groups'            => ['booking.default.user', 'camp.default.user'],
    ],
    'response'      => [
        'content-type'      => 'application/pdf',
        'accept-origin'     => '*'
    ],
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context  $context
 */
['context' => $context] = $providers;

// 1. Get view file path

$entity = 'sale\booking\Booking';
$parts = explode('\\', $entity);
$package = array_shift($parts);
$class_path = implode('/', $parts);
$parent = get_parent_class($entity);

$file = EQ_BASEDIR."/packages/$package/views/$class_path.{$params['view_id']}.html";

if(!file_exists($file)) {
    throw new Exception("unknown_view_id", EQ_ERROR_UNKNOWN_OBJECT);
}

// 2. Create map of customers by activities by days

$date_from = strtotime('Monday this week');
$date_to = strtotime('Sunday this week');

$now = time();

$bookings = Booking::search([
    [
        ['status', 'in', ['checkedin', 'checkedout']],
        ['date_from', '<=', $now],
        ['date_to', '>=', $now]
    ]
])
    ->read([
        'display_name',
        'date_from',
        'date_to',
        'booking_lines_groups_ids' => [
            'group_type',
            'has_person_with_disability',
            'rental_unit_assignments_ids' => [
                'is_accomodation'
            ],
            'age_range_assignments_ids' => [
                'age_from',
                'age_to',
                'qty'
            ]
        ],
        'booking_lines_ids' => [
            'product_id' => ['name', 'sku', 'is_meal', 'is_activity']
        ]
    ])
    ->get(true);

if(empty($bookings)) {
    throw new Exception("no_bookings", EQ_ERROR_INVALID_PARAM);
}

$date_format = Setting::get_value('core', 'locale', 'date_format', 'm/d/Y');

$title = sprintf('Semaine du %s au %s',
    date($date_format, $date_from),
    date($date_format, $date_to)
);

$icebox_skus = Setting::get_value('sale', 'booking', 'icebox_skus', '');
if(!empty($icebox_skus)) {
    $icebox_skus = array_map(
        fn($value) => trim($value),
        explode(',', $icebox_skus)
    );
}
else {
    $icebox_skus = [];
}

$has_bookings_with_accommodations = true;
$has_bookings_without_accommodations = true;

$bookings_planning = [];
foreach($bookings as $booking) {
    $quantities = [];
    $age = '';

    $has_accommodations = false;
    $has_planning = false;
    $has_icebox = false;
    $has_repartition = false;
    $has_disability = false;
    $has_meal = false;

    foreach($booking['booking_lines_groups_ids'] as $group) {
        if($group['has_person_with_disability']) {
            $has_disability = true;
        }

        foreach($group['rental_unit_assignments_ids'] as $rental_unit_assignment) {
            if($rental_unit_assignment['is_accomodation']) {
                $has_accommodations = true;
                $has_repartition = true;
            }
        }

        $has_bookings_with_accommodations = $has_accommodations;
        $has_bookings_without_accommodations = !$has_accommodations;

        if($group['group_type'] === 'sojourn') {
            foreach($group['age_range_assignments_ids'] as $age_range_assignment) {
                $quantities[] = $age_range_assignment['qty'];

                if(empty($age)) {
                    $age = $age_range_assignment['age_from'].' - '.$age_range_assignment['age_to'].' ans';
                }
            }
        }
    }

    foreach($booking['booking_lines_ids'] as $line) {
        if($line['product_id']['is_meal']) {
            $has_meal = true;
        }
        if($line['product_id']['is_activity']) {
            $has_planning = true;
        }
        if(in_array($line['sku'], $icebox_skus)) {
            $has_icebox = true;
        }
    }

    $bookings_planning[] = [
        'name'                  => $booking['display_name'],
        'dates'                 => date($date_format, $booking['date_from']).' - '.date($date_format, $booking['date_to']),
        'quantities'            => implode(' + ', $quantities),
        'age'                   => $age,
        'has_accommodations'    => $has_accommodations,
        'has_planning'          => $has_planning ? 'oui' : '',
        'has_icebox'            => $has_icebox ? 'oui' : '',
        'has_repartition'       => $has_repartition ? 'oui' : '',
        'has_disability'        => $has_disability ? 'oui' : '',
        'has_meal'              => $has_meal ? 'oui' : 'non'
    ];
}

$values = compact('title', 'bookings_planning', 'has_bookings_with_accommodations', 'has_bookings_without_accommodations');

// 3. Create html view

try {
    $loader = new TwigFilesystemLoader(EQ_BASEDIR . "/packages/{$package}/views/");
    $twig = new TwigEnvironment($loader);

    /**  @var ExtensionInterface $extension **/
    $extension  = new IntlExtension();
    $twig->addExtension($extension);

    $template = $twig->load("$class_path.{$params['view_id']}.html");
    $html = $template->render($values);
}
catch(Exception $e) {
    trigger_error("ORM::error while parsing template - ".$e->getMessage(), EQ_REPORT_DEBUG);
    throw new Exception("template_parsing_issue", EQ_ERROR_INVALID_CONFIG);
}

$result = [
    'headers'   => ['Content-Type' => 'text/html'],
    'body'      => $html
];

if($params['output'] == 'pdf') {
    $options = new DompdfOptions();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);

    $dompdf->setPaper('A4', 'landscape');
    $dompdf->loadHtml($html);
    $dompdf->render();

    $canvas = $dompdf->getCanvas();
    $font = $dompdf->getFontMetrics()->getFont("helvetica", "regular");
    $canvas->page_text(750, $canvas->get_height() - 35, "p. {PAGE_NUM} / {PAGE_COUNT}", $font, 9);

    $result = [
        'headers'   => ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="Planning_réservations".pdf"'],
        'body'      => $dompdf->output()
    ];
}

$response = $context->httpResponse();
foreach($result['headers'] as $header => $value) {
    $response->header($header, $value);
}

$response->body($result['body'])->send();
