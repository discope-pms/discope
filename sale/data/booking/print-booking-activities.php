<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use Dompdf\Dompdf;
use Dompdf\Options as DompdfOptions;
use sale\booking\BookingActivity;
use sale\booking\TimeSlot;
use Twig\Environment as TwigEnvironment;
use Twig\Extension\ExtensionInterface;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\FilesystemLoader as TwigFilesystemLoader;
use Twig\TwigFilter;

[$params, $providers] = eQual::announce([
    'description'   => "Render booking activities planning for a specific date and time slot.",
    'params'        => [
        'date_from' => [
            'type'          => 'date',
            'description'   => "Start date of the interval (included).",
            'default'       => strtotime('Monday this week')
        ],
        'date_to' => [
            'type'          => 'date',
            'description'   => "End date of the interval (included).",
            'default'       => strtotime('Sunday this week')
        ],
        'time_slot_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\booking\TimeSlot',
            'description'       => "Time slot of the planning.",
            'required'          => true,
            'domain'            => ['code', 'in', ['AM', 'PM', 'EV']]
        ],
        'view_id' =>  [
            'type'          => 'string',
            'description'   => "The identifier of the view <type.name>.",
            'default'       => 'print.all'
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
    'access' => [
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

/**
 * Methods
 */

$map_days = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
$formatDateDay = function($value) use($map_days) {
    return ucfirst($map_days[date('w', $value)]);
};

/**
 * Action
 */

// 1. Get view file path

$entity = 'sale\booking\BookingActivity';
$parts = explode('\\', $entity);
$package = array_shift($parts);
$class_path = implode('/', $parts);
$parent = get_parent_class($entity);

$file = EQ_BASEDIR."/packages/$package/views/{$class_path}.{$params['view_id']}.html";

if(!file_exists($file)) {
    throw new Exception("unknown_view_id", EQ_ERROR_UNKNOWN_OBJECT);
}

// 2. Create map of customers by activities by days

$time_slot = TimeSlot::id($params['time_slot_id'])
    ->read(['name'])
    ->first();

if(is_null($time_slot)) {
    throw new Exception("unknown_time_slot", EQ_ERROR_UNKNOWN_OBJECT);
}

$booking_activities = BookingActivity::search([
    ['activity_date', '>=', $params['date_from']],
    ['activity_date', '<=', $params['date_to']],
    ['time_slot_id', '=', $time_slot['id']],
    ['product_id', '<>', null]
])
    ->read([
        'activity_date',
        'product_id'            => ['name'],
        'booking_id'            => ['status', 'customer_identity_id'  => ['address_city']],
        'booking_line_group_id' => ['activity_group_num'],
        'camp_id'               => ['status', 'sojourn_number', 'short_name'],
        'camp_group_id'         => ['activity_group_num']
    ])
    ->get(true);

if(empty($booking_activities)) {
    throw new Exception("no_activities", EQ_ERROR_INVALID_PARAM);
}

$title = sprintf('Semaine du %s au %s %s',
    date('d/m/Y', $params['date_from']),
    date('d/m/Y', $params['date_to']),
    $time_slot['name']
);

$map_week_days_activities_customers = [];
$map_activities = [];

$date = $params['date_from'];
while($date <= $params['date_to']) {
    $day_index = $formatDateDay($date);
    $map_week_days_activities_customers[$day_index] = [];

    foreach($booking_activities as $activity) {
        // skip: not currently handled date
        if($activity['activity_date'] !== $date) {
            continue;
        }

        $status = null;
        $customer_name = '';
        if(!is_null($activity['booking_id'])) {
            // booking activity
            $status = $activity['booking_id']['status'];
            $customer_name = sprintf('%d. %s',
                $activity['booking_line_group_id']['activity_group_num'],
                strtoupper($activity['booking_id']['customer_identity_id']['address_city'])
            );
        }
        else {
            // camp activity
            $status = $activity['camp_id']['status'];
            $customer_name = sprintf('%d. %s %s',
                $activity['camp_group_id']['activity_group_num'],
                str_pad($activity['camp_id']['sojourn_number'], 3, '0', STR_PAD_LEFT),
                $activity['camp_id']['short_name']
            );
        }

        // skip: cancelled
        if($status === 'cancelled') {
            continue;
        }

        $product_name = preg_replace('/\s*\(\d+\)$/', '', $activity['product_id']['name']);

        if(!isset($map_week_days_activities_customers[$day_index][$product_name])) {
            $map_week_days_activities_customers[$day_index][$product_name] = [];
        }

        $map_week_days_activities_customers[$day_index][$product_name][] = $customer_name;

        $map_activities[$product_name] = true;
    }

    $date += 86400;
}

$activities = array_keys($map_activities);
usort($activities, fn($a, $b) => strcmp($a,$b));

$values = compact('title', 'map_week_days_activities_customers', 'activities');

// 3. Create html view

try {
    $loader = new TwigFilesystemLoader(EQ_BASEDIR . "/packages/{$package}/views/");
    $twig = new TwigEnvironment($loader);

    /**  @var ExtensionInterface $extension **/
    $extension  = new IntlExtension();
    $twig->addExtension($extension);

    $filter_date_day = new TwigFilter('format_date_day', $formatDateDay);
    $twig->addFilter($filter_date_day);

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

    $dompdf->setPaper('A4');
    $dompdf->loadHtml($html);
    $dompdf->render();

    $canvas = $dompdf->getCanvas();
    $font = $dompdf->getFontMetrics()->getFont("helvetica", "regular");
    $canvas->page_text(530, $canvas->get_height() - 35, "p. {PAGE_NUM} / {PAGE_COUNT}", $font, 9);

    $result = [
        'headers'   => ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="Planning_activités_condensé".pdf"'],
        'body'      => $dompdf->output()
    ];
}

$response = $context->httpResponse();
foreach($result['headers'] as $header => $value) {
    $response->header($header, $value);
}

$response->body($result['body'])->send();
