<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use identity\Center;
use identity\User;
use sale\camp\Camp;

[$params, $providers] = eQual::announce([
    'description'   => "Data about children's participation to camps.",
    'params'        => [
        'all_centers' => [
            'type'              => 'boolean',
            'description'       => "Mark all the centers of the children quantities.",
            'default'           => false
        ],
        'center_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'identity\Center',
            'description'       => "Center for the children quantities.",
            'default'           => 1
        ],
        'year' => [
            'type'              => 'string',
            'description'       => "Year for which we want the summer CLSH information.",
            'selection'         => range('2025', date('Y', time())),
            'default'           => fn() => date('Y', time())
        ],

        /* parameters used as properties of virtual entity */
        'center' => [
            'type'              => 'string',
            'description'       => "Name of the center for the children quantities."
        ],
        'child_name' => [
            'type'              => 'string',
            'description'       => "Name of the child."
        ],
        'age_range_6_9' => [
            'type'              => 'boolean',
            'description'       => "Was the child age range 6 to 9 years old?"
        ],
        'age_range_10_14' => [
            'type'              => 'boolean',
            'description'       => "Was the child age range 10 to 14 years old?"
        ],
        'qty_july' => [
            'type'              => 'integer',
            'description'       => "Quantity of attending days during the month of July."
        ],
        'qty_august' => [
            'type'              => 'integer',
            'description'       => "Quantity of attending days during the month of August."
        ],
        'qty' => [
            'type'              => 'integer',
            'description'       => "Total quantity of attending days."
        ]
    ],
    'access'        => [
        'visibility'    => 'protected',
        'groups'        => ['camp.default.user'],
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

/** @var \equal\data\adapt\DataAdapterJson $json_adapter */
$json_adapter = $adapter_provider->get('json');

$date_from = strtotime($params['year'].'-07-01');
$date_to = strtotime($params['year'].'-08-31');

$domain = [
    ['is_clsh', '=', true],
    ['date_from', '>=', $date_from],
    ['date_from', '<=', $date_to],
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

$camps = Camp::search($domain)
    ->read([
        'center_id',
        'date_from',
        'clsh_type',
        'enrollments_ids' => [
            'status',
            'child_age',
            'presence_day_1',
            'presence_day_2',
            'presence_day_3',
            'presence_day_4',
            'presence_day_5',
            'child_id' => [
                'name'
            ],
        ]
    ])
    ->get(true);

$map_centers_children = [];

foreach($camps as $camp) {
    foreach($camp['enrollments_ids'] as $enrollment) {
        if($enrollment['status'] !== 'validated') {
            continue;
        }

        $center_id = $camp['center_id'];
        $child_id = $enrollment['child_id']['id'];

        if(!isset($map_centers_children[$center_id][$child_id])) {
            $map_centers_children[$center_id][$child_id] = [
                'child_name'        => $enrollment['child_id']['name'],
                'age_range_6_9'     => false,
                'age_range_10_14'   => false,
                'qty_july'          => 0,
                'qty_august'        => 0,
                'qty'               => 0
            ];
        }

        $present_days = [
            $enrollment['presence_day_1'],
            $enrollment['presence_day_2'],
            $enrollment['presence_day_3'],
            $enrollment['presence_day_4']
        ];
        if($camp['clsh_type'] === '5-days') {
            $present_days[] = $enrollment['presence_day_5'];
        }

        $qty = 0;
        foreach($present_days as $present_day) {
            if($present_day) {
                $qty++;
            }
        }

        if(date('n', $camp['date_from']) === '7') {
            $map_centers_children[$center_id][$child_id]['qty_july'] += $qty;
        }
        else {
            $map_centers_children[$center_id][$child_id]['qty_august'] += $qty;
        }

        if($enrollment['child_age'] <= 9) {
            $map_centers_children[$center_id][$child_id]['age_range_6_9'] = true;
        }
        else {
            $map_centers_children[$center_id][$child_id]['age_range_10_14'] = true;
        }

        $map_centers_children[$center_id][$child_id]['qty'] += $qty;
    }
}

$result = [];

$center_ids = array_keys($map_centers_children);

$centers = Center::search(['id', 'in', $center_ids])
    ->read(['name'])
    ->get();

foreach($map_centers_children as $center_id => $map_children) {
    $center = null;
    foreach ($centers as $c) {
        if ($c['id'] === $center_id) {
            $center = $c['name'];
            break;
        }
    }

    foreach($map_children as $week => $child_clsh_summer_info) {
        $result[] = array_merge(
            ['center' => $center],
            $child_clsh_summer_info
        );
    }
}

usort($result, function($a, $b) {
    $result = strcmp($a['center'], $b['center']);
    if($result === 0) {
        return strcmp($a['child_name'], $b['child_name']);
    }
    return $result;
});

$context->httpResponse()
        ->header('X-Total-Count', count($result))
        ->body($result)
        ->send();
