<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use identity\Center;
use realestate\RentalUnit;
use realestate\RentalUnitCategory;
use sale\booking\Consumption;

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
        ],

        /* parameters used as properties of virtual entity */
        'date' => [
            'type'          => 'date',
            'description'   => 'Date of the consumption, night of the date.'
        ],
        'rental_unit_name' => [
            'type'              => 'string',
            'description'       => "Name of rental unit."
        ],
        'rental_unit_category' => [
            'type'              => 'string',
            'description'       => "The category of rental unit."
        ],
        'capacity' => [
            'type'              => 'integer',
            'description'       => "Total capacity of the building rental unit between given date from and date to interval.",
        ],
        'extra' => [
            'type'              => 'integer',
            'description'       => "Total capacity of the building rental unit between given date from and date to interval.",
        ],
        'nb_total' => [
            'type'              => 'integer',
            'description'       => "Total quantity of people.",
        ],
        'nb_remaining' => [
            'type'              => 'integer',
            'description'       => "Total of places that are remaining.",
        ],
        'nb_remaining_with_extra' => [
            'type'              => 'integer',
            'description'       => "Total of places that are remaining with extra.",
        ]
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
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

$adapter = $dap->get('json');

$center = Center::id($params['center_id'])
    ->read(['name'])
    ->first();

if(!$center) {
    throw new Exception("unknown_center", EQ_ERROR_UNKNOWN_OBJECT);
}

$rental_units_categories = RentalUnitCategory::search()
    ->read(['code', 'name'])
    ->get();

$rental_units = RentalUnit::search([
    ['center_id', '=', $center['id']],
    ['is_accomodation', '=', true]
])
    ->read(['name', 'rental_unit_category_id', 'capacity', 'extra', 'has_parent', 'parent_id', 'has_children', 'children_ids'])
    ->get();

if($params['date_to'] <= $params['date_from']) {
    throw new Exception("invalid_date_to", EQ_ERROR_INVALID_PARAM);
}

$parent_rental_units = [];
$map_top_parent_capacities = [];
$map_parent_rental_units_children = [];
foreach($rental_units as $rental_unit) {
    if(!$rental_unit['has_parent']) {
        $parent_rental_units[] = $rental_unit;

        $map_top_parent_capacities[$rental_unit['id']] = [
            'capacity'  => $rental_unit['capacity'],
            'extra'     => $rental_unit['extra']
        ];
    }
    elseif($rental_unit['parent_id'] && empty($rental_unit['children_ids'])) {
        $top_parent = $getTopParentRentalUnit($rental_units, $rental_unit['parent_id']);
        if(!isset($map_parent_rental_units_children[$top_parent['id']])) {
            $map_parent_rental_units_children[$top_parent['id']] = [];
        }

        $map_parent_rental_units_children[$top_parent['id']][] = $rental_unit;
    }
}
foreach($map_parent_rental_units_children as $parent_rental_unit_id => $children_rental_units) {
    $capacities = [
        'capacity'  => 0,
        'extra'     => 0
    ];
    foreach($children_rental_units as $child_rental_unit) {
        $capacities['capacity'] += $child_rental_unit['capacity'];
        $capacities['extra'] += $child_rental_unit['extra'];
    }

    $map_top_parent_capacities[$parent_rental_unit_id] = $capacities;
}

$consumptions = Consumption::search(
    [
        ['date', '>=', $params['date_from']],
        ['date', '<=', $params['date_to']],
        ['center_id', 'in',  [$center['id']]],
        ['is_rental_unit', '=', true]
    ],
    ['date' => 'asc']
)
    ->read(['name', 'type', 'qty', 'date', 'schedule_to', 'rental_unit_id', 'booking_id'])
    ->get(true);

$map_consumptions_top_parents = [];
foreach($consumptions as $consumption) {
    if($consumption['schedule_to'] !== 86400 || $consumption['date'] === $params['date_to']) {
        continue;
    }

    $top_parent = null;
    $rental_unit = $rental_units[$consumption['rental_unit_id']];
    if($rental_unit['has_parent'] && $rental_unit['parent_id']) {
        $top_parent = $getTopParentRentalUnit($rental_units, $rental_unit['parent_id']);
    }
    else {
        $top_parent = $rental_unit;
    }

    if($top_parent) {
        $map_consumptions_top_parents[$consumption['id']] = $top_parent['id'];
    }
}

$map_dates_consumptions = [];
for($date = $params['date_from']; $date < $params['date_to']; $date += 86400) {
    $date_index = date('Y-m-d', $date);
    foreach($consumptions as $consumption) {
        if(date('Y-m-d', $consumption['date']) !== $date_index) {
            continue;
        }

        if(!isset($map_dates_consumptions[$date_index])) {
            $map_dates_consumptions[$date_index] = [];
        }

        $map_dates_consumptions[$date_index][] = $consumption;
    }
}

$result = [];
foreach($parent_rental_units as $parent_rental_unit) {
    for($date = $params['date_from']; $date < $params['date_to']; $date += 86400) {
        $date_index = date('Y-m-d', $date);

        $parent_capacities = $map_top_parent_capacities[$parent_rental_unit['id']];

        $occupation = [
            'capacity'                  => $parent_capacities['capacity'],
            'extra'                     => $parent_capacities['extra'],
            'nb_total'                  => 0,
            'nb_remaining'              => $parent_capacities['capacity'],
            'nb_remaining_with_extra'   => $parent_capacities['capacity'] + $parent_capacities['extra']
        ];

        foreach($map_dates_consumptions[$date_index] ?? [] as $consumption) {
            $top_parent_rental_unit_id = $map_consumptions_top_parents[$consumption['id']];
            if($parent_rental_unit['id'] !== $top_parent_rental_unit_id) {
                continue;
            }

            $rental_unit = $rental_units[$consumption['rental_unit_id']];
            if(in_array($consumption['type'], ['part', 'ooo'])) {
                if($consumption['type'] === 'ooo') {
                    $occupation['capacity'] -= $rental_unit['capacity'];
                    $occupation['extra'] -= $rental_unit['extra'];
                    $occupation['nb_remaining'] -= $rental_unit['capacity'];
                    $occupation['nb_remaining_with_extra'] -= $rental_unit['capacity'] - $rental_unit['extra'];
                }

                continue;
            }

            if($rental_unit['has_children'] && !empty($rental_unit['children_ids'])) {
                $has_child_in_conso = false;
                foreach($map_dates_consumptions[$date_index] as $cons) {
                    if($cons['id'] === $consumption['id']) {
                        continue;
                    }

                    $cons_rental_unit = $rental_units[$cons['rental_unit_id']];
                    if(in_array($cons_rental_unit['id'], $rental_unit['children_ids'])) {
                        $has_child_in_conso = true;
                    }
                }

                if($has_child_in_conso) {
                    continue;
                }
            }

            $occupation['nb_total'] += $consumption['qty'];
            $occupation['nb_remaining'] -= $consumption['qty'];
            $occupation['nb_remaining_with_extra'] -= $consumption['qty'];
        }

        $result[] = array_merge(
            [
                'date'                  => $adapter->adaptOut($date, 'date'),
                'rental_unit_name'      => $parent_rental_unit['name'],
                'rental_unit_category'  => $rental_units_categories[$parent_rental_unit['rental_unit_category_id']]['name']
            ],
            $occupation
        );
    }
}

$context
    ->httpResponse()
    ->header('X-Total-Count', count($result))
    ->body($result)
    ->send();
