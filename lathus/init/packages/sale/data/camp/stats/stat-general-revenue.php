<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use identity\Center;
use identity\User;
use sale\camp\Camp;

[$params, $providers] = eQual::announce([
    'description'   => 'List general revenues per month.',
    'params'        => [
        /* mixed-usage parameters: required both for fetching data (input) and property of virtual entity (output) */
        'center_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'identity\Center',
            'description'       => "Output: Center of the sojourn / Input: The center for which the stats are required.",
            'visible'           => ['all_centers', '=', false],
            'default'           => function() {
                return ($centers = Center::search())->count() === 1 ? current($centers->ids()) : null;
            }
        ],
        'all_centers' => [
            'type'              => 'boolean',
            'default'           =>  false,
            'description'       => "Mark the all Center of the sojourn."
        ],
        'date_from' => [
            'type'              => 'date',
            'description'       => "Output: Day of arrival / Input: Date interval lower limit (defaults to first day of year).",
            'default'           => strtotime('first day of january this year')
        ],
        'date_to' => [
            'type'              => 'date',
            'description'       => 'Output: Day of departure / Input: Date interval upper limit (defaults to last day of year).',
            'default'           => strtotime('last day of december this year')
        ],

        /* parameters used as properties of virtual entity */

        'center' => [
            'type'              => 'string',
            'description'       => 'Name of the center.'
        ],
        'aamm' => [
            'type'              => 'string',
            'description'       => 'Year and month.'
        ],
        'nb_children' => [
            'type'              => 'integer',
            'description'       => 'Number of children participating to camps.'
        ],
        'camps' => [
            'type'              => 'float',
            'description'       => 'Total revenue for camps without weekend and saturday morning.'
        ],
        'saturday_mornings' => [
            'type'              => 'float',
            'description'       => 'Total revenue for stay until saturday mornings.'
        ],
        'weekends' => [
            'type'              => 'float',
            'description'       => 'Total revenue for stay between two consecutive camps.'
        ],
        'discounts' => [
            'type'              => 'float',
            'description'       => 'Total discounts that have been applied.'
        ],
        'financial_helps' => [
            'type'              => 'float',
            'description'       => 'Total revenue that have to be collected.'
        ],
        'enrollments' => [
            'type'              => 'float',
            'description'       => 'Total revenue from nights.'
        ]
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'auth']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\auth\AuthenticationManager   $auth
 */
['context' => $context, 'auth' => $auth] = $providers;

// #memo - we consider all camps for which at least one sojourn starts during the given period
if($params['center_id']) {
    $domain = [
        ['date_from', '>=', $params['date_from']],
        ['date_from', '<=', $params['date_to']],
        ['state', 'in', ['instance', 'archive']],
        ['status', 'not in', ['draft', 'cancelled']]
    ];
}

if($params['all_centers']) {
    $user_id = $auth->userId();
    if($user_id <= 0) {
        throw new Exception("user_unknown", QN_ERROR_NOT_ALLOWED);
    }
    $user = User::id($user_id)
        ->read(['centers_ids'])
        ->first(true);

    if(!$user) {
        throw new Exception("unexpected_error", QN_ERROR_INVALID_USER);
    }

    $domain[] = ['center_id', 'in', $user['centers_ids']];
}
else if($params['center_id'] && $params['center_id'] > 0) {
    $domain[] = ['center_id', '=', $params['center_id']];
}

$camps = [];

if(!empty($domain)) {
    $camps = Camp::search($domain)
        ->read([
            'date_from',
            'date_to',
            'center_id' => [
                'name',
                'center_office_id'
            ],
            'enrollments_ids' => [
                'status',
                'price',
                'enrollment_lines_ids' => [
                    'price',
                    'product_id' => [
                        'camp_product_type'
                    ]
                ],
                'price_adapters_ids' => [
                    'price_adapter_type',   // percent and amount
                    'origin_type',          // other and loyalty-discount
                    'value'
                ],
                'fundings_ids' => [
                    'payments_ids' => [
                        'payment_method',
                        'amount'
                    ]
                ]
            ]
        ])
        ->get(true);
}

// associative array mapping centers with each date index
$map_center_values = [];

foreach($camps as $camp) {
    $date_index = date('Ym', $camp['date_from']);
    if(!isset($map_center_values[$camp['center_id']['name']])) {
        $map_center_values[$camp['center_id']['name']] = [];
    }

    foreach($camp['enrollments_ids'] as $enrollment) {
        if(in_array($enrollment['status'], ['pending', 'waitlisted', 'cancelled'])) {
            continue;
        }

        if(!isset($map_center_values[$camp['center_id']['name']][$date_index])) {
            $map_center_values[$camp['center_id']['name']][$date_index] = [
                'center'            => $camp['center_id']['name'],
                'aamm'              => date('Y/m', $camp['date_from']),
                'nb_children'       => 0,
                'camps'             => 0,
                'saturday_mornings' => 0,
                'weekends'          => 0,
                'discounts'         => 0,
                'financial_helps'   => 0,
                'enrollments'       => 0
            ];
        }

        $map_center_values[$camp['center_id']['name']][$date_index]['nb_children']++;
        $map_center_values[$camp['center_id']['name']][$date_index]['enrollments'] += $enrollment['price'];

        $camp_product_line_price = 0.0;
        foreach($enrollment['enrollment_lines_ids'] as $line) {
            if($line['product_id']['camp_product_type'] === 'saturday-morning') {
                $map_center_values[$camp['center_id']['name']][$date_index]['saturday_mornings'] += $line['price'];
            }
            elseif($line['product_id']['camp_product_type'] === 'weekend') {
                $map_center_values[$camp['center_id']['name']][$date_index]['weekends'] += $line['price'];
            }
            elseif(in_array($line['product_id']['camp_product_type'], ['full', 'clsh-full-5-days', 'clsh-full-4-days', 'clsh-day'])) {
                $map_center_values[$camp['center_id']['name']][$date_index]['camps'] += $line['price'];
                $camp_product_line_price += $line['price'];
            }
        }

        foreach($enrollment['price_adapters_ids'] as $price_adapter) {
            if(!in_array($price_adapter['origin_type'], ['other', 'loyalty-discount'])) {
                continue;
            }

            switch($price_adapter['price_adapter_type']) {
                case 'percent':
                    $map_center_values[$camp['center_id']['name']][$date_index]['discounts'] += round($camp_product_line_price / $price_adapter['value'], 2);
                    break;
                case 'amount':
                    $map_center_values[$camp['center_id']['name']][$date_index]['discounts'] += $price_adapter['value'];
                    break;
            }
        }

        foreach($enrollment['fundings_ids'] as $funding) {
            foreach($funding['payments_ids'] as $payment) {
                if($payment['payment_method'] === 'camp_financial_help') {
                    $map_center_values[$camp['center_id']['name']][$date_index]['financial_helps'] += $payment['amount'];
                }
            }
        }
    }
}

// build final result
$result = [];
foreach($map_center_values as $center => $dates) {
    foreach($dates as $date_index => $item) {
        $result[] = $item;
    }
}

$context->httpResponse()
        ->header('X-Total-Count', count($result))
        ->body($result)
        ->send();
