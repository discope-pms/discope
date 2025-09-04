<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use core\setting\Setting;
use Dompdf\Dompdf;
use Dompdf\Options as DompdfOptions;
use identity\Identity;
use sale\booking\Booking;
use sale\booking\BookingLine;
use sale\booking\BookingLineGroup;
use Twig\Environment as TwigEnvironment;
use Twig\Extension\ExtensionInterface;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\FilesystemLoader as TwigFilesystemLoader;
use Twig\TwigFilter;

[$params, $providers] = eQual::announce([
    'description'   => "Render a contract given its ID as a PDF document, for Lathus.",
    'params'        => [
        'id' => [
            'type'          => 'integer',
            'description'   => 'Identifier of the contract to print.',
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
            'selection'     => ['grouped', 'detailed'],
            'default'       => 'grouped'
        ],
        'output' =>  [
            'description'   => 'Output format of the document.',
            'type'          => 'string',
            'selection'     => ['pdf', 'html'],
            'default'       => 'pdf'
        ]
    ],
    'access'        => [
        'visibility'        => 'protected',
        'groups'            => ['booking.default.user'],
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

/*
    1) retrieve the requested template
*/

$entity = 'lathus\sale\booking\Booking';
$parts = explode('\\', $entity);
$package = array_shift($parts);
$class_path = implode('/', $parts);
$parent = get_parent_class($entity);

$file = QN_BASEDIR."/packages/{$package}/views/{$class_path}.{$params['view_id']}.html";

if(!file_exists($file)) {
    throw new Exception("unknown_view_id", QN_ERROR_UNKNOWN_OBJECT);
}

/*
    2) check booking exists
*/

$booking = Booking::id($params['id'])
    ->read(['id'])
    ->first();

if(is_null($booking)) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

/*
    3) create valus array to inject data in template
*/

$booking = Booking::id($booking['id'])
    ->read([
        'date_expiry',
        'date_from',
        'date_to',
        'time_from',
        'time_to',
        'price',
        'center_id'     => ['organisation_id'],
        'customer_id'   => ['partner_identity_id']
    ])
    ->first();

/*
      3.1) get customer data
*/

$customer = Identity::id($booking['customer_id']['partner_identity_id'])
    ->read(['display_name', 'address_street', 'address_zip', 'address_dispatch', 'address_city'])
    ->first();

/*
      3.2) handle img url
*/

$organisation = Identity::id($booking['center_id']['organisation_id'])
    ->read(['logo_document_id' => ['data', 'type']])
    ->first();

$img_url = '';

$logo_document_data = $organisation['logo_document_id']['data'] ?? null;
if($logo_document_data) {
    $content_type = $organisation['logo_document_id']['type'] ?? 'image/png';
    $img_url = "data:{$content_type};base64, ".base64_encode($logo_document_data);
}

/*
    3.3) handle lines
*/

$booking_lines = BookingLine::search(['booking_id', '=', $booking['id']])
    ->read([
        'product_id' => [
            'grouping_code_id' => [
                'name',
                'code'
            ],
            'product_model_id' => [
                'grouping_code_id' => [
                    'name',
                    'code'
                ]
            ]
        ]
    ])
    ->get();

$map_products_groupings = [];
foreach($booking_lines as $line) {
    if(isset($map_products_groupings[$line['product_id']['id']])) {
        continue;
    }

    $grouping_code = null;
    if(isset($line['product_id']['grouping_code_id'])) {
        $grouping_code = $line['product_id']['grouping_code_id'];
    }
    elseif(isset($line['product_id']['product_model_id']['grouping_code_id'])) {
        $grouping_code = $line['product_id']['product_model_id']['grouping_code_id'];
    }

    if(is_null($grouping_code) || $grouping_code['code'] === 'invisible') {
        continue;
    }

    $map_products_groupings[$line['product_id']['id']] = $grouping_code;
}

$booking_lines = BookingLine::search(['booking_id', '=', $booking['id']])
    ->read([
        'name',
        'description',
        'qty',
        'free_qty',
        'unit_price',
        'vat_rate',
        'total',
        'price',
        'product_id' => ['label']
    ])
    ->get();

$map_groupings_lines = [];
foreach($booking_lines as $line) {
    $grouping_name = $line['product_id']['label'];
    if(isset($map_products_groupings[$line['product_id']['id']])) {
        $grouping_name = $map_products_groupings[$line['product_id']['id']]['name'];
    }
    elseif(!empty($line['description'])) {
        $grouping_name = $line['description'];
    }

    if(!isset($map_groupings_lines[$grouping_name])) {
        $map_groupings_lines[$grouping_name] = [];
    }

    $map_groupings_lines[$grouping_name][] = $line;
}

$lines = [];
foreach($map_groupings_lines as $grouping_name => $grouping_lines) {
    switch($params['mode']) {
        case 'grouped':
            $total = 0;
            $price = 0;
            foreach($grouping_lines as $line) {
                $total += $line['total'];
                $price += $line['price'];
            }

            $lines[] = [
                'name'          => $grouping_name,
                'qty'           => 1,
                'free_qty'      => null,
                'unit_price'    => $total,
                'vat_rate'      => null,
                'total'         => $total,
                'price'         => $price,
                'is_group'      => true
            ];
            break;
        case 'detailed':
            $lines[] = [
                'name'          => $grouping_name,
                'qty'           => null,
                'free_qty'      => null,
                'unit_price'    => null,
                'vat_rate'      => null,
                'total'         => null,
                'price'         => null,
                'is_group'      => true
            ];

            foreach($grouping_lines as $line) {
                $lines[] = [
                    'name'          => $line['name'],
                    'qty'           => $line['qty'],
                    'free_qty'      => $line['free_qty'],
                    'unit_price'    => $line['unit_price'],
                    'vat_rate'      => $line['vat_rate'],
                    'total'         => $line['total'],
                    'price'         => $line['price'],
                    'is_group'      => false
                ];
            }
            break;
    }
}

/*
      3.3) handle children and adults qty
*/

$sojourn_groups = BookingLineGroup::search([
    ['booking_id', '=', $booking['id']],
    ['group_type', '=', 'sojourn']
])
    ->read(['age_range_assignments_ids' => ['age_to', 'qty']])
    ->get();

$people_qty = 0;
$adults_qty = 0;
foreach($sojourn_groups as $group) {
    foreach($group['age_range_assignments_ids'] as $age_range_assignment) {
        if($age_range_assignment['age_to'] > 18) {
            $adulst_qty += $age_range_assignment['qty'];
        }
        $people_qty +=$age_range_assignment['qty'];
    }
}

/*
      3.4) set values
*/

$today = time();

$values = compact('booking', 'customer', 'img_url', 'today', 'lines', 'people_qty', 'adults_qty');

/*
    4) inject all values into the template
*/

try {
    $loader = new TwigFilesystemLoader(QN_BASEDIR."/packages/{$package}/views/");

    $twig = new TwigEnvironment($loader);
    /**  @var ExtensionInterface **/
    $extension  = new IntlExtension();
    $twig->addExtension($extension);
    $currency = Setting::get_value('core', 'locale', 'currency', '€');
    // do not rely on system locale (LC_*)
    $filter = new TwigFilter('format_money', function ($value) use($currency) {
        return number_format((float)($value), 2, ",", ".") . ' ' .$currency;
    });
    $twig->addFilter($filter);

    $date_format = Setting::get_value('core', 'locale', 'date_format', 'm/d/Y');
    $date_filter = new TwigFilter('format_date', function($value) use($date_format) {
        return date($date_format, $value);
    });
    $twig->addFilter($date_filter);

    $map_days = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
    $map_months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $date_filter = new TwigFilter('format_date_long', function($value) use($map_days, $map_months) {
        return $map_days[date('w', $value)].' '.date('j', $value).' '.$map_months[date('n', $value) - 1].' '.date('Y', $value);
    });
    $twig->addFilter($date_filter);

    $date_filter = new TwigFilter('format_time', function($value) {
        return sprintf('%02dh%02d', $value / 3600, $value / 60 % 60);
    });
    $twig->addFilter($date_filter);

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

/*
    5) convert HTML to PDF
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


/*
    6) handle response
*/

$output = $dompdf->output();

$context->httpResponse()
        // ->header('Content-Disposition', 'attachment; filename="document.pdf"')
        ->header('Content-Disposition', 'inline; filename="document.pdf"')
        ->body($output)
        ->send();

