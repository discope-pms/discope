<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use identity\Center;
use sale\booking\Booking;
use sale\booking\Consumption;
use sale\customer\AgeRange;

list($params, $providers) = announce([
    'description'   => 'Provides data about current Centers capacities (according to configuration).',
    'params'        => [
        'center_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'identity\Center',
            'description'       => "Output: Center of the sojourn / Input: The center for which the stats are required."
        ],
        'date_from' => [
            'type'          => 'date',
            'description'   => "Last date of the time interval.",
            'default'       => strtotime("-1 Week")
        ],
        'date_to' => [
            'type'          => 'date',
            'description'   => "First date of the time interval.",
            'default'       => strtotime("+1 Week")
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
    'providers'     => [ 'context', 'orm', 'adapt' ]
]);

/**
 * @var \equal\php\Context          $context
 * @var \equal\orm\ObjectManager    $orm
 * @var \equal\data\DataAdapter     $adapter
 */
list($context, $orm, $adapter) = [ $providers['context'], $providers['orm'], $providers['adapt'] ];


$center = Center::id($params['center_id'])->read(['id', 'name'])->first(true);
if($center){
    $consumptions = Consumption::search([[
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
        ]])
        ->read([
            'id',
            'date',
            'time_slot_id' => ['id', 'code'],
            'schedule_from',
            'schedule_to',
            'booking_id',
            'age_range_id',
            'is_meal',
            'is_snack',
            'product_model_id' => ['id','name'],
            'qty'
        ])
        ->get();


    $bookings_ids = Booking::search()->ids();
    $bookings = Booking::ids($bookings_ids)->read(['id','status','is_cancelled'])->get();

    $map_bookings  = [];
    foreach($bookings as $booking_id => $booking) {
        if($params['booking_options'] == 'is_not_option' && in_array($booking['status'], ['quote', 'option'])) {
            continue;
        }
        if($booking['is_cancelled']) {
            continue;
        }
        $map_bookings[$booking_id] = true;
    }

    $ages_ranges = AgeRange::search()->ids();
    $map_consumptions = [];
    foreach($consumptions as $id => $consumption) {

        if(!isset($map_bookings[$consumption['booking_id']])){
            continue;
        }

        $date_index = date('Y-m-d', $consumption['date']);

        if(!isset($map_consumptions[$date_index])) {
            $map_consumptions[$date_index] = [];
        }
        $age_index = isset($consumption['age_range_id']) ? $consumption['age_range_id'] : $ages_ranges[0];
        if(!isset($map_consumptions[$date_index][$age_index])) {
            $map_consumptions[$date_index][$age_index] = [];
        }

        $total_snack = 0;
        $total_breakfast = 0;
        $total_lunch = 0;
        $total_diner = 0;
        if ($consumption['is_meal']){
            if(isset($consumption['time_slot_id']['code'])) {
                switch($consumption['time_slot_id']['code']) {
                    case "B":
                        $total_breakfast += $consumption['qty'];
                        break;
                    case "L":
                        $total_lunch += $consumption['qty'];
                        break;
                    case "D":
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
        } else if($consumption['is_snack']) {
            $total_snack += $consumption['qty'];
        }


        $map_consumptions[$date_index][$age_index][] = [
            'date'                       => $consumption['date'],
            'age_range_id'               => $age_index,
            'total_snack'                => $total_snack,
            'total_breakfast'            => $total_breakfast,
            'total_lunch'                => $total_lunch,
            'total_diner'                => $total_diner
        ];

    }
}
$result = [];

$ages_ranges = AgeRange::search()->read(['id','name'])->get(true);
foreach($map_consumptions as $date => $ages) {
    foreach($ages as $age_index => $items) {
        $total_breakfast  = 0 ;
        $total_snack  = 0 ;
        $total_lunch  = 0 ;
        $total_diner  = 0 ;
        $total = 0;
        foreach($items as $item) {
            $total_snack += $item['total_snack'];
            $total_breakfast += $item['total_breakfast'];
            $total_lunch += $item['total_lunch'];
            $total_diner += $item['total_diner'];
            $total = $total_snack + $total_breakfast + $total_lunch + $total_diner;
        }
        $result[] = [
            'date'                   => $date,
            'age_range_id'           => $ages_ranges[$age_index],
            'total_snack'            => $total_snack,
            'total_breakfast'        => $total_breakfast,
            'total_lunch'            => $total_lunch,
            'total_diner'            => $total_diner,
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
