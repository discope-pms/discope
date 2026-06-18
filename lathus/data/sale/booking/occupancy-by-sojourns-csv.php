<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use core\setting\Setting;
use identity\Center;
use realestate\RentalUnit;
use realestate\RentalUnitCategory;
use sale\booking\Booking;

[$params, $providers] = eQual::announce([
    'description'   => "Provides data about current Centers capacities (according to configuration).",
    'params'        => [
        'center_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'identity\Center',
            'description'       => "Output: Center of the sojourn / Input: The center for which the stats are required.",
            'default'           => fn() => Center::search()->ids()[0] ?? null
        ],
        'date_from' => [
            'type'              => 'date',
            'description'       => "Last date of the time interval.",
            'default'           => strtotime("today")
        ],
        'date_to' => [
            'type'              => 'date',
            'description'       => "First date of the time interval.",
            'default'           => strtotime("tomorrow")
        ]
    ],
    'response'      => [
        'content-type'          => 'text/csv',
        'content-disposition'   => 'inline; filename="occupations_batiments.csv"',
        'charset'               => 'utf-8',
        'accept-origin'         => '*'
    ],
    'providers'     => ['context', 'orm', 'adapt']
]);

/**
 * @var \equal\php\Context                      $context
 * @var \equal\orm\ObjectManager                $orm
 * @var \equal\data\adapt\DataAdapterProvider   $dap
 */
['context' => $context, 'orm' => $orm, 'adapt' => $dap] = $providers;

/**
 * Methods
 */

$getTopParentRentalUnit = function($rental_units, $parent_rental_unit_id) use(&$getTopParentRentalUnit) {
    $parent_rental_unit = $rental_units[$parent_rental_unit_id];

    return ($parent_rental_unit['has_parent'] && $parent_rental_unit['parent_id']) ? $getTopParentRentalUnit($rental_units, $parent_rental_unit['parent_id']) : $parent_rental_unit;
};

/**
 * Data controller
 */

$occupations_by_sojourns = eQual::run('get', 'sale_booking_occupancy-by-sojourns', $params);

$map_occupations = [
    'journée'   => [],
    'marabout'  => [],
    'camping'   => [],
    'permanent' => []
];
foreach($occupations_by_sojourns as $sojourn_occupation) {
    $map_occupations[$sojourn_occupation['rental_unit_name']][] = $sojourn_occupation;
}

$data = [
    ['Du', 'Au', 'Client', 'Participants', '', '']
];

$map_types_titles = [
    'journée'   => 'À la journée',
    'marabout'  => 'En Marabout',
    'camping'   => 'En camping',
    'permanent' => 'En dur'
];

foreach($map_types_titles as $type => $title) {
    if(empty($map_occupations[$type])) {
        continue;
    }

    $data[] = ['', '', '', '', '', ''];

    $data[] = [$title, '', '', '', '', ''];
    foreach($map_occupations[$type] as $occupation_data) {
        $nb_adults = $occupation_data['nb_pers'] - $occupation_data['nb_children'];

        $data[] = [
            $occupation_data['sojourn_date_from'],
            $occupation_data['sojourn_date_to'],
            $occupation_data['customer'],
            $occupation_data['nb_pers'],
            $occupation_data['nb_children'] > 0 ? "{$occupation_data['nb_children']}+$nb_adults" : '',
            $occupation_data['age_ranges']
        ];
    }
}

$tmp_file = tempnam(sys_get_temp_dir(), 'csv');

$fp = fopen($tmp_file, 'w');
foreach($data as $row) {
    fputcsv($fp, $row, ';');
}
fclose($fp);

$output = file_get_contents($tmp_file);

$output = str_replace('"', '', $output);

$context
    ->httpResponse()
    ->body($output)
    ->send();
