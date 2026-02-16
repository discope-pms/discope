<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use identity\User;
use sale\camp\Camp;

[$params, $providers] = eQual::announce([
    'description'   => "Data about ages of children's participating to camps.",
    'params'        => [
        'all_centers' => [
            'type'              => 'boolean',
            'description'       => "Mark all the centers of the children quantities.",
            'default'           => false
        ],
        'center_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'identity\Center',
            'description'       => "Center for the children ages distribution.",
            'default'           => 1
        ],
        'date_from' => [
            'type'              => 'date',
            'description'       => "Date interval lower limit (defaults to first day of the current week).",
            'default'           => fn() => strtotime('last Sunday')
        ],
        'date_to' => [
            'type'              => 'date',
            'description'       => "Date interval upper limit (defaults to last day of the current week).",
            'default'           => fn() => strtotime('Sunday this week')
        ],

        /* parameters used as properties of virtual entity */
        'center' => [
            'type'              => 'string',
            'description'       => "Name of the center."
        ],
        'code' => [
            'type'              => 'string',
            'description'       => "Code of the camp."
        ],
        'camp' => [
            'type'              => 'string',
            'description'       => "Name of the camp."
        ],
        'age_range' => [
            'type'              => 'string',
            'description'       => "Age range of the camp."
        ],
        'qty' => [
            'type'              => 'integer',
            'description'       => "Quantity of children attending the camp."
        ],
        '4_year_old_qty' => [
            'type'              => 'integer',
            'description'       => "Quantity of 4 year old children attending the camp."
        ],
        '5_6_year_old_qty' => [
            'type'              => 'integer',
            'description'       => "Quantity of 5 and 6 year old children attending the camp."
        ],
        '7_8_year_old_qty' => [
            'type'              => 'integer',
            'description'       => "Quantity of 7 and 8 year old children attending the camp."
        ],
        '9_10_year_old_qty' => [
            'type'              => 'integer',
            'description'       => "Quantity of 9 and 10 year old children attending the camp."
        ],
        '11_12_year_old_qty' => [
            'type'              => 'integer',
            'description'       => "Quantity of 11 and 12 year old children attending the camp."
        ],
        '13_14_year_old_qty' => [
            'type'              => 'integer',
            'description'       => "Quantity of 13 and 14 year old children attending the camp."
        ],
        '15_year_old_qty' => [
            'type'              => 'integer',
            'description'       => "Quantity of 15 year old children attending the camp."
        ],
        '16_17_year_old_qty' => [
            'type'              => 'integer',
            'description'       => "Quantity of 16 and 17 year old children attending the camp."
        ],
        'no_year_old_qty' => [
            'type'              => 'integer',
            'description'       => "Quantity of children without age attending the camp."
        ]
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'adapt' , 'auth']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\data\adapt\AdapterProvider   $adapter_provider
 * @var \equal\auth\AuthenticationManager   $auth
 */
['context' => $context, 'adapt' => $adapter_provider, 'auth' => $auth] = $providers;

$domain = [
    ['date_from', '>=', $params['date_from']],
    ['date_from', '<=', $params['date_to']],
    ['status', '=', 'published']
];

if($params['all_centers']) {
    $user_id = $auth->userId();
    if($user_id <= 0) {
        throw new Exception("unknown_user", EQ_ERROR_NOT_ALLOWED);
    }
    $user = User::id($user_id)->read(['centers_ids'])->first();
    if(is_null($user)) {
        throw new Exception("unexpected_error", EQ_ERROR_INVALID_USER);
    }
    $domain[] = ['center_id', 'in', $user['centers_ids']];
}
elseif(isset($params['center_id']) && $params['center_id'] > 0) {
    $domain[] = ['center_id', '=', $params['center_id']];
}

$result = [];

$camps = Camp::search($domain, ['sort' => ['date_from' => 'asc']])
    ->read([
        'sojourn_number',
        'short_name',
        'date_from',
        'age_range',
        'center_id' => [
            'name'
        ],
        'enrollments_ids' => [
            'status',
            'child_age'
        ]
    ])
    ->get();

$map_age_range_name = [
    '6-to-9'    => "6 - 9 ans",
    '10-to-12'  => "10 - 12 ans",
    '13-to-16'  => "13 - 16 ans",
    '6-to-14'   => "6 - 14 ans"
];

$result = [];
foreach($camps as $camp) {
    $row = [
        'center'                => $camp['center_id']['name'],
        'code'                  => str_pad($camp['sojourn_number'], 3, '0', STR_PAD_LEFT),
        'camp'                  => $camp['short_name'],
        'age_range'             => $map_age_range_name[$camp['age_range']],
        'qty'                   => 0,
        '4_year_old_qty'        => 0,
        '5_6_year_old_qty'      => 0,
        '7_8_year_old_qty'      => 0,
        '9_10_year_old_qty'     => 0,
        '11_12_year_old_qty'    => 0,
        '13_14_year_old_qty'    => 0,
        '15_year_old_qty'       => 0,
        '16_17_year_old_qty'    => 0,
        'no_year_old_qty'       => 0
    ];

    foreach($camp['enrollments_ids'] as $enrollment) {
        if($enrollment['status'] !== 'validated') {
            continue;
        }

        $row['qty']++;

        switch($enrollment['child_age']) {
            case 4:
                $row['4_year_old_qty']++;
                break;
            case 5:
            case 6:
                $row['5_6_year_old_qty']++;
                break;
            case 7:
            case 8:
                $row['7_8_year_old_qty']++;
                break;
            case 9:
            case 10:
                $row['9_10_year_old_qty']++;
                break;
            case 11:
            case 12:
                $row['11_12_year_old_qty']++;
                break;
            case 13:
            case 14:
                $row['13_14_year_old_qty']++;
                break;
            case 15:
                $row['15_year_old_qty']++;
                break;
            case 16:
            case 17:
                $row['16_17_year_old_qty']++;
                break;
            default:
                $row['no_year_old_qty']++;
                break;
        }
    }

    $result[] = $row;
}

usort($result, function($a, $b) {
    $result = strcmp($a['center'], $b['center']);
    if($result === 0) {
        $result = strcmp($a['camp'], $b['camp']);
        if($result === 0) {
            return strcmp($a['age'], $b['age']);
        }
    }
    return $result;
});

$context->httpResponse()
        ->body($result)
        ->send();
