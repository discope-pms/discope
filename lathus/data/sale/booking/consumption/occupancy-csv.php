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

$date_format = Setting::get_value('core', 'locale', 'date_format', 'm/d/Y');

$center = Center::id($params['center_id'])
    ->read(['name'])
    ->first();

if(!$center) {
    throw new Exception("unknown_center", EQ_ERROR_UNKNOWN_OBJECT);
}

$rental_units = RentalUnit::search([
    ['center_id', '=', $center['id']],
    ['is_accomodation', '=', true]
])
    ->read(['name', 'rental_unit_category_id', 'capacity', 'extra', 'has_parent', 'parent_id', 'has_children', 'children_ids'])
    ->get();

$marabout_category = RentalUnitCategory::search(['code', '=', 'MB'])->read(['id'])->first();
$marabout_parent_rental_units_ids = RentalUnit::search([
    ['rental_unit_category_id', '=', $marabout_category['id']],
    ['has_parent', '=', false]
])
    ->ids();

$camping_category = RentalUnitCategory::search(['code', '=', 'CP'])->read(['id'])->first();
$camping_parent_rental_units_ids = RentalUnit::search([
    ['rental_unit_category_id', '=', $camping_category['id']],
    ['has_parent', '=', false]
])
    ->ids();

$bookings = Booking::search(
    [
        ['status', 'in', ['confirmed', 'validated', 'checkedin', 'checkedout']],
        ['date_from', '<=', $params['date_to']],
        ['date_to', '>=', $params['date_from']],
    ],
    ['sort' => ['date_from' => 'asc']]
)
    ->read([
        'name',
        'date_from',
        'date_to',
        'customer_id' => [
            'name'
        ],
        'booking_lines_groups_ids' => [
            'date_from',
            'date_to',
            'group_type',
            'nb_pers',
            'nb_children',
            'age_range_assignments_ids'     => [
                'age_from',
                'age_to'
            ],
            'rental_unit_assignments_ids'   => [
                'rental_unit_id' => [
                    'is_accomodation',
                    'parent_id'
                ]
            ]
        ]
    ])
    ->get(true);

$map_occupations = [
    'day'       => [],
    'marabout'  => [],
    'camping'   => [],
    'permanent' => []
];
foreach($bookings as $booking) {
    $has_sojourn_group = false;
    foreach($booking['booking_lines_groups_ids'] as $group) {
        if($group['group_type'] === 'sojourn') {
            $has_sojourn_group = true;
        }
    }
    $main_group_type = $has_sojourn_group ? 'sojourn' : 'event';

    $main_group = null;
    foreach($booking['booking_lines_groups_ids'] as $group) {
        if($group['group_type'] === $main_group_type) {
            $main_group = $group;
        }
    }
    if(!$main_group) {
        $main_group = $booking['booking_lines_groups_ids'][0];
    }

    $type = 'permanent';
    if($booking['date_from'] === $booking['date_to']) {
        $type = 'day';
    }
    else {
        $accomodation_rental_unit = null;
        foreach($main_group['rental_unit_assignments_ids'] as $rental_unit_assignment) {
            if($rental_unit_assignment['rental_unit_id']['is_accomodation']) {
                $accomodation_rental_unit = $rental_unit_assignment['rental_unit_id'];
            }
        }

        $parent_rental_unit = $getTopParentRentalUnit($rental_units, $accomodation_rental_unit['parent_id']);

        if(in_array($parent_rental_unit['id'], $marabout_parent_rental_units_ids)) {
            $type = 'marabout';
        }
        elseif(in_array($parent_rental_unit['id'], $camping_parent_rental_units_ids)) {
            $type = 'camping';
        }
    }

    $nb_adults = $main_group['nb_pers'] - $main_group['nb_children'];

    $age_range = [];
    foreach($main_group['age_range_assignments_ids'] as $age_range_assignment) {
        if(!isset($age_range['from']) || $age_range_assignment['age_from'] < $age_range['from']) {
            $age_range = [
                'from'  => $age_range_assignment['age_from'],
                'to'    => $age_range_assignment['age_to']
            ];
        }
    }

    $map_occupations[$type][] = [
        date($date_format, $main_group['date_from']),
        date($date_format, $main_group['date_to']),
        $booking['customer_id']['name'],
        $main_group['nb_pers'],
        $main_group['nb_children'] > 0 ? "{$main_group['nb_children']}+$nb_adults" : '',
        implode('-', $age_range)
    ];
}


$data = [
    ['Du', 'Au', 'Client', 'Participants', '', '']
];

$map_types_titles = [
    'day'       => 'À la journée',
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
        $data[] = $occupation_data;
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
