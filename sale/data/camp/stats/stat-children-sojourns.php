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
            'description'       => "Year for which we want the sojourns information.",
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
        'qty' => [
            'type'              => 'integer',
            'description'       => "Total quantity of sojourns."
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

$date_from = strtotime($params['year'].'-01-01');
$date_to = strtotime($params['year'].'-12-31');

$domain = [
    ['is_clsh', '=', false],
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
            'child_id' => [
                'name'
            ]
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
                'child_name'    => $enrollment['child_id']['name'],
                'qty'           => 0
            ];
        }

        $map_centers_children[$center_id][$child_id]['qty']++;
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

    foreach($map_children as $week => $child_sojourns_info) {
        $result[] = array_merge(
            ['center' => $center],
            $child_sojourns_info
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
