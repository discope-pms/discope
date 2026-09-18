<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\booking\Booking;
use sale\customer\AgeRange;
use sale\customer\CustomerNature;
use sale\customer\RateClass;

list($params, $providers) = announce([
    'description'   => 'Provides participant and person-night counts grouped by center, rate class, customer nature and age range.',
    'params'        => [
        /* mixed-usage parameters: required both for fetching data (input) and property of virtual entity (output) */
        'center_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'identity\Center',
            'description'       => "Output: Center of the sojourn / Input: The center for which the stats are required."
        ],

        'center_office_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'identity\CenterOffice',
            'description'       => 'Office the invoice relates to (for center management).'
        ],

        'date_from' => [
            'type'              => 'date',
            'description'       => "Output: Day of arrival / Input: Date interval lower limit (defaults to first day of previous month).",
            'default'           => mktime(0, 0, 0, date("m")-1, 1)
        ],
        'date_to' => [
            'type'              => 'date',
            'description'       => 'Output: Day of departure / Input: Inclusive date interval upper limit (defaults to last day of previous month).',
            'default'           => mktime(0, 0, 0, date("m"), 0)
        ],
        'age_range_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\customer\AgeRange',
            'description'       => 'Specific age range to limit the result to.'
        ],

        'rate_class_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\customer\RateClass',
            'description'       => 'Rate class that applies to the customer.'
        ],


        /* parameters used as properties of virtual entity */
        'center' => [
            'type'              => 'string',
            'description'       => 'Name of the center.'
        ],

        'rate_class' => [
            'type'              => 'string',
            'description'       => 'Name of the rate class.'
        ],

        'customer_nature' => [
            'type'              => 'string',
            'description'       => 'Name of the customer nature.'
        ],

        'age_range' => [
            'type'              => 'string',
            'description'       => 'Code of the age range.'
        ],
        'nb_pers' => [
            'type'              => 'integer',
            'description'       => 'Number of hosted persons.'
        ],
        'nb_nights' => [
            'type'              => 'integer',
            'description'       => 'Duration of the sojourn (number of nights).'
        ]
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context  $context
 */
['context' => $context] = $providers;

$map_age_ranges = AgeRange::search()
    ->read(['name'])
    ->get();

$map_rate_class = RateClass::search()
    ->read(['name', 'description'])
    ->get();

$map_customer_nature = CustomerNature::search()
    ->read(['code', 'description'])
    ->get();

$domain = [];

// #memo - we consider all bookings for which at least one sojourn intersects the given period
if($params['center_id'] || $params['center_office_id']) {
    $domain = [
        ['state', 'in', ['instance', 'archive']],
        ['date_from', '<=', $params['date_to']], // #memo - if date_to is 05/08 we want the night from 05/08 -> 06/08
        ['date_to', '>=', $params['date_from']],
        ['is_cancelled', '=', false], // #memo - needed to handle booking cancelled but invoiced to customer
        ['status', 'not in', ['quote', 'option']]
    ];

    if(isset($params['center_id'])) {
        $domain[] = ['center_id', '=', $params['center_id']];
    }

    if(isset($params['center_office_id'])) {
        $domain[] = ['center_office_id', '=', $params['center_office_id']];
    }
}

$booking_lines_fields = [
    '@domain' => ['is_accomodation', '=', true],
    'qty',
    'price'
];

$booking_lines_groups_fields = [
    '@domain' => ['is_sojourn', '=', true],
    'date_from',
    'date_to',
    'nb_pers',
    'age_range_assignments_ids' => ['qty', 'age_range_id'],
    'booking_lines_ids'         => $booking_lines_fields
];

$bookings = [];
if(!empty($domain)) {
    $bookings = Booking::search($domain)
        ->read([
            'name',
            'center_id'                 => ['name'],
            'customer_id'               => ['customer_nature_id', 'rate_class_id'],
            'booking_lines_groups_ids'  => $booking_lines_groups_fields
        ])
        ->get(true);
}

if($params['rate_class_id'] && $params['rate_class_id'] > 0) {
    $bookings = array_filter($bookings, function ($booking) use ($params) {
        $rate_class_id = $booking['customer_id']['rate_class_id'];
        return isset($rate_class_id) && $rate_class_id == $params['rate_class_id'];
    });
}

$map_centers = [];
foreach($bookings as $booking) {
    $center_id = $booking['center_id']['id'];
    $rate_class_id = $booking['customer_id']['rate_class_id'];
    $customer_nature_id =  $booking['customer_id']['customer_nature_id'];

    if(!isset($map_centers[$center_id])) {
        $map_centers[$center_id] = [];
    }
    if(!isset($map_centers[$center_id][$rate_class_id])) {
        $map_centers[$center_id][$rate_class_id] = [];
    }
    if(!isset($map_centers[$center_id][$rate_class_id][$customer_nature_id])) {
        $map_centers[$center_id][$rate_class_id][$customer_nature_id] = [];
    }

    foreach($booking['booking_lines_groups_ids'] as $group) {
        $has_valid_accommodation = false;
        foreach($group['booking_lines_ids'] as $line) {
            if($line['price'] >= 0 && $line['qty'] >= 0) {
                $has_valid_accommodation = true;
                break;
            }
        }

        if(!$has_valid_accommodation) {
            continue;
        }

        // #memo - if date_to is 05/08 we want the night from 05/08 -> 06/08
        $date_to_exclusive = strtotime('+1 day', $params['date_to']);

        // Sojourn departure dates are exclusive; the inclusive report end date is normalized above.
        $nights_from = max($params['date_from'], $group['date_from']);
        $nights_to = min($date_to_exclusive, $group['date_to']);
        if($nights_from >= $nights_to) {
            continue;
        }
        $nb_nights = (int) round(($nights_to - $nights_from) / 86400);

        $participants_by_age_range = [];
        if(count($group['age_range_assignments_ids'])) {
            foreach($group['age_range_assignments_ids'] as $age_range_assignment) {
                $age_range_id = $age_range_assignment['age_range_id'];
                if(isset($params['age_range_id']) && $age_range_id != $params['age_range_id']) {
                    continue;
                }

                if(!isset($participants_by_age_range[$age_range_id])) {
                    $participants_by_age_range[$age_range_id] = 0;
                }
                $participants_by_age_range[$age_range_id] += $age_range_assignment['qty'];
            }
        }
        // Without age assignments, the whole sojourn belongs to the "all ages" bucket.
        elseif(!isset($params['age_range_id'])) {
            $participants_by_age_range[0] = $group['nb_pers'];
        }

        $rate_class = $map_rate_class[$rate_class_id];
        $customer_nature = $map_customer_nature[$customer_nature_id];

        foreach($participants_by_age_range as $age_range_id => $nb_pers) {
            if(!isset($map_centers[$center_id][$rate_class_id][$customer_nature_id][$age_range_id])) {
                $map_centers[$center_id][$rate_class_id][$customer_nature_id][$age_range_id] = [
                    'center'            => $booking['center_id']['name'],
                    'rate_class'        => $rate_class['name'].' - '.$rate_class['description'],
                    'customer_nature'   => $customer_nature['description'],
                    'nb_pers'           => $nb_pers,
                    'nb_nights'         => $nb_nights * $nb_pers,
                    'age_range'         => $map_age_ranges[$age_range_id]['name'] ?? 'tous les ages'
                ];
            }
            else {
                $map_centers[$center_id][$rate_class_id][$customer_nature_id][$age_range_id]['nb_pers'] += $nb_pers;
                $map_centers[$center_id][$rate_class_id][$customer_nature_id][$age_range_id]['nb_nights'] += ($nb_nights * $nb_pers);
            }
        }
    }
}

// linearize the result (there might be several lines for a same center)
$result = [];
foreach($map_centers as $map_rate_classes) {
    foreach($map_rate_classes as $map_customer_natures) {
        foreach($map_customer_natures as $age_range_stats) {
            foreach($age_range_stats as $age_range_stat) {
                $result[] = $age_range_stat;
            }
        }
    }
}

$context
    ->httpResponse()
    ->header('X-Total-Count', count($bookings))
    ->body($result)
    ->send();
