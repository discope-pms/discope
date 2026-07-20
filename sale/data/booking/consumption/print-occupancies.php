<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use discope\setting\Setting;
use core\User;
use Dompdf\Dompdf;
use Dompdf\Options as DompdfOptions;
use equal\orm\Domain;
use identity\Center;
use realestate\RentalUnit;
use sale\booking\Booking;
use sale\booking\Consumption;
use Twig\Environment as TwigEnvironment;
use Twig\Extension\ExtensionInterface;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\FilesystemLoader as TwigFilesystemLoader;
use Twig\TwigFilter;

[$params, $providers] = eQual::announce([
    'description'   => "Render room occupancies.",
    'params'        => [
        'view_id' =>  [
            'type'              => 'string',
            'description'       => "The identifier of the view <type.name>.",
            'default'           => 'print.occupancies'
        ],
        'lang' =>  [
            'type'              => 'string',
            'description'       => "Language in which labels and multilang field have to be returned (2 letters ISO 639-1).",
            'default'           => constant('DEFAULT_LANG')
        ],
        'output' =>  [
            'type'              => 'string',
            'description'       => "Output format of the document.",
            'selection'         => ['pdf', 'html'],
            'default'           => 'pdf'
        ],
        'domain' => [
            'description'   => 'Criterias that results have to match (serie of conjunctions)',
            'type'          => 'array',
            'default'       => []
        ],
        'date_from' => [
            'type'              => 'date',
            'description'       => "Date interval lower limit.",
            'default'           => strtotime('Today')
        ],
        'date_to' => [
            'type'              => 'date',
            'description'       => 'Date interval Upper limit.',
            'default'           => strtotime('+7 days midnight')
        ],
        'center_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'identity\Center',
            'description'       => 'The center to which the booking relates to.',
            'default'           => function() {
                return ($centers = Center::search())->count() === 1 ? current($centers->ids()) : null;
            }
        ],
        'is_not_option' => [
            'type'              => 'boolean',
            'description'       => 'Discard quote and option bookings.',
            'default'           =>  true
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

$date_format = Setting::get_value('core', 'locale', 'date_format', 'm/d/Y');

$formatDate = fn($value) => date($date_format, $value);

/**
 * Data controller
 */

// 1. Get view file path

$entity = 'sale\booking\Consumption';
$parts = explode('\\', $entity);
$package = array_shift($parts);
$class_path = implode('/', $parts);
$parent = get_parent_class($entity);

$file = EQ_BASEDIR."/packages/$package/views/$class_path.{$params['view_id']}.html";

if(!file_exists($file)) {
    throw new Exception("unknown_view_id", EQ_ERROR_UNKNOWN_OBJECT);
}

/*
    Add conditions to the domain to consider advanced parameters
*/
$domain = $params['domain'];

if(isset($params['center_id']) && $params['center_id'] > 0) {
    // add constraint on center_id
    $domain = Domain::conditionAdd($domain, ['center_id', '=', $params['center_id']]);
}
else {
    // if no center is provided, fallback to current users'
    $user = User::id($auth->userId())->read(['centers_ids'])->first(true);
    if(count($user['centers_ids']) == 1) {
        $domain = Domain::conditionAdd($domain, ['center_id', '=', reset($user['centers_ids'])]);
    }
    else {
        $domain = Domain::conditionAdd($domain, ['center_id', '=', 0]);
    }
}

if(isset($params['date_from'])) {
    // add constraint on date_from
    $domain = Domain::conditionAdd($domain, ['date', '>=', $params['date_from']]);
}

if(isset($params['date_to'])) {
    // add constraint on date_to
    $domain = Domain::conditionAdd($domain, ['date', '<=', $params['date_to']]);
}

if(isset($params['is_not_option']) && $params['is_not_option']) {
    $bookings_ids = Booking::search([['status', 'not in', ['quote','option']], ['is_cancelled', '=', false]])->ids();
    $domain = Domain::conditionAdd($domain, ['booking_id', 'in', $bookings_ids]);
}

$rental_units = RentalUnit::search(['has_children', '=', false], ['sort' => ['name' => 'asc']])
    ->read(['name', 'capacity'])
    ->get();

$consumptions = Consumption::search($domain)
    ->read([
        'date',
        'rental_unit_id',
        'customer_id' => [
            'name'
        ]
    ])
    ->get();

$has_consumptions = count($consumptions) > 0;

$date_from = $params['date_from'];
$date_to = $params['date_to'];

$fmt = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, 'EEE dd/MM');

$map_date_indexes = [];
for($date = $date_from; $date <= $date_to; $date += 86400) {
    $map_date_indexes[date('Y-m-d', $date)] = $fmt->format($date);
}

$map_rental_units_consumptions = [];
foreach($consumptions as $consumption) {
    if(!isset($map_rental_units_consumptions[$consumption['rental_unit_id']])) {
        $map_rental_units_consumptions[$consumption['rental_unit_id']] = [];
    }

    $map_rental_units_consumptions[$consumption['rental_unit_id']][] = $consumption;
}

$map_rental_units_dates_consumptions = [];
foreach($rental_units as $id => $rental_unit) {
    $map_rental_units_dates_consumptions[$id] = [];
    foreach(($map_rental_units_consumptions[$id] ?? []) as $consumption) {
        $date_index = date('Y-m-d', $consumption['date']);
        if(!isset($map_rental_units_dates_consumptions[$id][$date_index])) {
            $map_rental_units_dates_consumptions[$id][$date_index] = [];
        }
        $map_rental_units_dates_consumptions[$id][$date_index][] = $consumption;
    }
}

foreach($rental_units as $id => $rental_unit) {
    $rental_unit_has_consumptions = false;
    foreach($map_rental_units_dates_consumptions[$id] as $date_index => $consumptions) {
        if(!empty($consumptions)) {
            $rental_unit_has_consumptions = true;
            break;
        }
    }

    if(!$rental_unit_has_consumptions) {
        unset($rental_units[$id]);
    }
}

$title = 'Répartition par chambre';

$subtitle = sprintf('Du %s au %s',
    $formatDate($date_from),
    $formatDate($date_to)
);

$values = compact('title', 'subtitle', 'has_consumptions', 'map_date_indexes', 'rental_units', 'map_rental_units_dates_consumptions');

// 3. Create html view

try {
    $loader = new TwigFilesystemLoader(EQ_BASEDIR . "/packages/{$package}/views/");
    $twig = new TwigEnvironment($loader);

    /**  @var ExtensionInterface $extension **/
    $extension  = new IntlExtension();
    $twig->addExtension($extension);

    $date_filter = new TwigFilter('format_date', $formatDate);
    $twig->addFilter($date_filter);

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

    $dompdf->setPaper('A4', 'portrait');
    $dompdf->loadHtml($html);
    $dompdf->render();

    $canvas = $dompdf->getCanvas();
    $font = $dompdf->getFontMetrics()->getFont("helvetica", "regular");
    $canvas->page_text(750, $canvas->get_height() - 35, "p. {PAGE_NUM} / {PAGE_COUNT}", $font, 9);

    $result = [
        'headers'   => ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="Occupations_chambres.pdf"'],
        'body'      => $dompdf->output()
    ];
}

$response = $context->httpResponse();
foreach($result['headers'] as $header => $value) {
    $response->header($header, $value);
}

$response->body($result['body'])->send();
