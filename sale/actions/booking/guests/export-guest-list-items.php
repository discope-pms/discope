<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2026
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use core\User;
use discope\setting\Setting;
use identity\Center;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use sale\booking\Booking;
use sale\booking\GuestListItem;

[$params, $providers] = eQual::announce([
    'description'   => "Returns a view populated with a collection of guest list items, and outputs it as an XLS spreadsheet.",
    'params'        => [
        'date_from' => [
            'type'              => 'date',
            'description'       => 'Date interval lower limit.',
            'default'           => fn() => strtotime('Monday this week')
        ],
        'date_to' => [
            'type'              => 'date',
            'description'       => 'Date interval upper limit.',
            'default'           => fn() => strtotime('Sunday this week')
        ],
        'center_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'identity\Center',
            'description'       => 'The center the guest list items must be part of..'
        ],
        'params' => [
            'type'              => 'array',
            'description'       => 'List of filter params provided by the view.',
            'default'           => []
        ],
        'lang' => [
            'type'              => 'string',
            'description '      => 'Specific language for multilang field.',
            'default'           => constant('DEFAULT_LANG')
        ]
    ],
    'access' => [
        'visibility'    => 'protected',
        'groups'        => ['booking.guestlist.user'],
        'level'         => 2
    ],
    'response'      => [
        'content-type'  => 'application/zip',
        'accept-origin' => '*'
    ],
    'constants'     => ['DEFAULT_LANG'],
    'providers'     => ['context', 'orm', 'auth']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\orm\ObjectManager            $orm
 * @var \equal\auth\AuthenticationManager   $auth
 */
['context' => $context, 'orm' => $orm, 'auth' => $auth] = $providers;

$getLabels = function($lang, $default_labels = []) {
    $view_i18n_file_path = sprintf('%s/packages/sale/i18n/%s/_parts/labels.json', EQ_BASEDIR, $lang);

    $readLabels = function($path) {
        if(!$path || !file_exists($path)) {
            return [];
        }
        $labels = json_decode(file_get_contents($path), true);
        return is_array($labels) ? $labels : [];
    };

    return array_merge(
        $default_labels,
        $readLabels($view_i18n_file_path)
    );
};

$generateZip = function($files) {
    $tmp_file = tempnam(sys_get_temp_dir(), 'zip');
    $zip = new ZipArchive();
    if($zip->open($tmp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new Exception("unable_to_create_zip_file", EQ_ERROR_UNKNOWN);
    }

    foreach($files as $file_name => $file_data) {
        $zip->addFromString($file_name, $file_data);
    }

    $zip->close();

    $data = file_get_contents($tmp_file);
    unlink($tmp_file);

    if($data === false) {
        throw new Exception("unable_retrieve_zip_file_content", EQ_ERROR_UNKNOWN);
    }

    return $data;
};

if(!empty($params['params'])) {
    if(!empty($params['params']['center_id'])) {
        $params['center_id'] = $params['params']['center_id'];
    }
    if(!empty($params['params']['date_from'])) {
        $params['date_from'] = strtotime($params['params']['date_from']);
    }
    if(!empty($params['params']['date_to'])) {
        $params['date_to'] = strtotime($params['params']['date_to']);
    }
}

$bookings_domain = [
    ['status', 'not in', ['quote', 'option', 'cancelled']],
    ['is_cancelled', '=', false],
    ['date_from', '>=', $params['date_from']],
    ['date_from', '<=', $params['date_to']]
];
if(isset($params['center_id'])) {
    $bookings_domain[] = ['center_id', '=', $params['center_id']];
}

$bookings_ids = Booking::search($bookings_domain)->ids();

$guests = GuestListItem::search(['booking_id', 'in', $bookings_ids])
    ->read([
        'center_id',
        'date_from',
        'date_to',
        'firstname',
        'lastname',
        'gender',
        'date_of_birth',
        'citizen_identification',
        'is_coordinator',
        'address_street',
        'address_zip',
        'address_city',
        'address_country',
        'booking_id' => ['name'],
        'booking_line_group_id' => ['name']
    ])
    ->get();

$map_centers_years_weeks_guests = [];
foreach($guests as $guest) {
    $year = date('Y', $guest['date_from']);
    $week = date('W', $guest['date_from']);
    $map_centers_years_weeks_guests[$guest['center_id']][$year][$week][] = $guest;
}

$centers = Center::search(['id', 'in', array_keys($map_centers_years_weeks_guests)])
    ->read(['name'])
    ->get();

$user = User::id($auth->userId())
    ->read(['login'])
    ->first(true);

$date_format = Setting::get_value('core', 'locale', 'date_format', 'm/d/Y');

$i18n = eQual::run('get', 'config_i18n', [
    'entity'    => GuestListItem::getType(),
    'lang'      => $params['lang']
]);

$header_fields = [
    'center_name'               => 'center_id',
    'booking_name'              => 'booking_id',
    'booking_group_name'        => 'booking_line_group_id',
    'date_from'                 => 'date_from',
    'date_to'                   => 'date_to',
    'firstname'                 => 'firstname',
    'lastname'                  => 'lastname',
    'gender'                    => 'gender',
    'date_of_birth'             => 'date_of_birth',
    'citizen_identification'    => 'citizen_identification',
    'is_coordinator'            => 'is_coordinator',
    'address_street'            => 'address_street',
    'address_zip'               => 'address_zip',
    'address_city'              => 'address_city',
    'address_country'           => 'address_country'
];

$map_header = [];
foreach($header_fields as $column => $field) {
    $label = $field;
    if(!empty($i18n['model'][$field]['label'])) {
        $label = $i18n['model'][$field]['label'];
    }
    else {
        if(str_ends_with($label, '_id')) {
            $label = substr($label, 0, -strlen('_id'));
        }

        $label = ucfirst(str_replace('_', ' ', $label));
    }

    $map_header[$column] = $label;
}

$labels = $getLabels($params['lang']);

$files = [];
foreach($map_centers_years_weeks_guests as $center_id => $years) {
    $center_name = $centers[$center_id]['name'];

    foreach($years as $year => $weeks) {
        foreach($weeks as $week => $guests) {
            $doc = new Spreadsheet();

            $doc->getProperties()
                ->setCreator($user['login'])
                ->setTitle("Export-guests-list_$year-$center_name-$week")
                ->setDescription('Exported with eQual library');

            $doc->setActiveSheetIndex(0);

            $sheet = $doc->getActiveSheet();
            $sheet->setTitle("guests");

            $row = 1;
            $column = 'A';
            foreach($map_header as $col_name) {
                $sheet->setCellValue($column.$row, $col_name);
                $sheet->getColumnDimension($column)->setAutoSize(true);
                $sheet->getStyle($column.$row)->getFont()->setBold(true);

                $column++;
            }

            foreach($guests as $guest) {
                $row++;
                $column = 'A';

                $guest_data = array_merge(
                    $guest,
                    [
                        'center_name'           => $center_name,
                        'booking_name'          => $guest['booking_id']['name'],
                        'booking_group_name'    => $guest['booking_line_group_id']['name'],
                        'date_from'             => date($date_format, $guest['date_from']),
                        'date_to'               => date($date_format, $guest['date_to']),
                        'date_of_birth'         => date($date_format, $guest['date_of_birth'])
                    ]
                );

                foreach(array_keys($map_header) as $key) {
                    $value = $guest_data[$key];
                    if(is_bool($value)) {
                        $value = $value ? $labels['yes'] : $labels['no'];
                    }

                    $sheet->setCellValue($column.$row, $value);

                    $column++;
                }
            }

            $writer = IOFactory::createWriter($doc, "Xlsx");

            ob_start();
            $writer->save('php://output');
            $files["$year-$center_name-$week.xlsx"] = ob_get_clean();
        }
    }
}

$zip = $generateZip($files);

$context
    ->httpResponse()
    ->header('Content-Disposition', 'inline; filename="export-guests-list.zip"')
    ->body($zip)
    ->send();
