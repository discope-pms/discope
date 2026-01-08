<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use identity\Center;
use sale\booking\Booking;
use sale\booking\Consumption;
use sale\customer\AgeRange;

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
            'default'           => strtotime("-1 Week")
        ],
        'date_to' => [
            'type'              => 'date',
            'description'       => "First date of the time interval.",
            'default'           => strtotime("+1 Week")
        ],
        'booking_options' => [
            'type'              => 'string',
            'selection'         => [
                'is_option',
                'is_not_option'
            ],
            'default'           => 'is_not_option'
        ],

        /* parameters used as properties of virtual entity */
        'date' => [
            'type'              => 'date',
            'description'       => 'Date of the consumption.'
        ],
        'age_range_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\customer\AgeRange',
            'description'       => 'Customers age range the product is intended for.'
        ],
        'total' => [
            'type'              => 'integer',
            'description'       => "Total of the consumption.",
        ],
        'total_snack' => [
            'type'              => 'integer',
            'description'       => "Total of the consumption for the snack.",
        ],
        'total_breakfast' => [
            'type'              => 'integer',
            'description'       => "Total of the consumption for the breakfast.",
        ],
        'total_lunch' => [
            'type'              => 'integer',
            'description'       => "Total of the consumption for the lunch.",
        ],
        'total_diner' => [
            'type'              => 'integer',
            'description'       => "Total of the consumption for the diners.",
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
 * @var \equal\php\Context  $context
 */
['context' => $context] = $providers;

$center = Center::id($params['center_id'])
    ->read(['id', 'name'])
    ->first();

if(!$center) {
    throw new Exception("unknown_center", EQ_ERROR_UNKNOWN_OBJECT);
}

$consumptions_domain = [
    [
        ['center_id', '=', $center['id']],
        ['is_meal', '=', true],
        ['disclaimed', '=', false],
        ['date', '>=', $params['date_from']],
        ['date', '<=', $params['date_to']]
    ],
    [
        ['center_id', '=', $center['id']],
        ['is_snack', '=', true],
        ['disclaimed', '=', false],
        ['date', '>=', $params['date_from']],
        ['date', '<=', $params['date_to']]
    ]
];

$consumptions = Consumption::search($consumptions_domain)
    ->read([
        'date',
        'qty',
        'schedule_from',
        'schedule_to',
        'age_range_id',
        'is_meal',
        'is_snack',
        'booking_id',
        'time_slot_id'      => ['code'],
        'product_model_id'  => ['name']
    ])
    ->get();

$map_bookings_ids = [];
foreach($consumptions as $consumption) {
    $map_bookings_ids[$consumption['booking_id']] = true;
}

$bookings = Booking::ids(array_keys($map_bookings_ids))
    ->read(['status', 'is_cancelled'])
    ->get();

$age_ranges_ids = AgeRange::search()->ids();
$map_consumptions_totals = [];
foreach($consumptions as $id => $consumption) {
    $booking = $bookings[$consumption['booking_id']];
    if($params['booking_options'] === 'is_not_option' && in_array($booking['status'], ['quote', 'option'])) {
        continue;
    }
    if($booking['is_cancelled']) {
        continue;
    }

    $date_index = date('Y-m-d', $consumption['date']);
    if(!isset($map_consumptions_totals[$date_index])) {
        $map_consumptions_totals[$date_index] = [];
    }

    $age_range_id = $consumption['age_range_id'] ?? $age_ranges_ids[0];
    if(!isset($map_consumptions_totals[$date_index][$age_range_id])) {
        $map_consumptions_totals[$date_index][$age_range_id] = [];
    }

    $total_snack = 0;
    $total_breakfast = 0;
    $total_lunch = 0;
    $total_diner = 0;
    if($consumption['is_meal']) {
        if(isset($consumption['time_slot_id']['code'])) {
            switch($consumption['time_slot_id']['code']) {
                case 'B':
                    $total_breakfast += $consumption['qty'];
                    break;
                case 'L':
                    $total_lunch += $consumption['qty'];
                    break;
                case 'D':
                    $total_diner += $consumption['qty'];
                    break;
            }
        }
        else {
            if(stripos($consumption['product_model_id']['name'], 'matin') !== false) {
                $total_breakfast += $consumption['qty'];
            }
            elseif(stripos($consumption['product_model_id']['name'], 'midi') !== false) {
                $total_lunch += $consumption['qty'];
            }
            elseif(stripos($consumption['product_model_id']['name'], 'soir') !== false) {
                $total_diner += $consumption['qty'];
            }
        }
    }
    elseif($consumption['is_snack']) {
        $total_snack += $consumption['qty'];
    }

    if(!isset($map_consumptions_totals[$date_index][$age_range_id])) {
        $map_consumptions_totals[$date_index][$age_range_id] = [
            'date'                       => $consumption['date'],
            'age_range_id'               => $age_range_id,
            'total_snack'                => 0,
            'total_breakfast'            => 0,
            'total_lunch'                => 0,
            'total_diner'                => 0
        ];
    }

    $map_consumptions_totals[$date_index][$age_range_id]['total_snack'] += $total_snack;
    $map_consumptions_totals[$date_index][$age_range_id]['total_breakfast'] += $total_breakfast;
    $map_consumptions_totals[$date_index][$age_range_id]['total_lunch'] += $total_lunch;
    $map_consumptions_totals[$date_index][$age_range_id]['total_diner'] += $total_diner;
}

$result = [];

$ages_ranges = AgeRange::search()
    ->read(['id','name'])
    ->get();

foreach($map_consumptions_totals as $date => $ages) {
    foreach($ages as $age_range_id => $consumptions_totals) {
        $total = $consumptions_totals['total_snack'] + $consumptions_totals['total_breakfast'] + $consumptions_totals['total_lunch'] + $consumptions_totals['total_diner'];

        $result[] = [
            'date'                   => $date,
            'age_range_id'           => $ages_ranges[$age_range_id],
            'total_snack'            => $consumptions_totals['total_snack'],
            'total_breakfast'        => $consumptions_totals['total_breakfast'],
            'total_lunch'            => $consumptions_totals['total_lunch'],
            'total_diner'            => $consumptions_totals['total_diner'],
            'total'                  => $total
        ];
    }
}

usort($result, function ($a, $b) {
    return strcmp($a['date'], $b['date']) ?: strcmp($a['age_range_id']['id'], $b['age_range_id']['id']);
});

$context->httpResponse()
        ->header('X-Total-Count', count($result))
        ->body($result)
        ->send();
