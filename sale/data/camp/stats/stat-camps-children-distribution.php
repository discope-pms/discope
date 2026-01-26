<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use identity\User;
use sale\camp\Camp;
use sale\camp\Enrollment;

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
        'by_age' => [
            'type'              => 'boolean',
            'description'       => "Split the children quantities by age.",
            'default'           => false
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
            'description'       => "Name of the center for the children quantities."
        ],
        'code' => [
            'type'              => 'string',
            'description'       => "Code of the camp."
        ],
        'dates' => [
            'type'              => 'string',
            'description'       => "Start and end dates of the camp.",
        ],
        'location' => [
            'type'              => 'string',
            'description'       => "The location of the accommodation of camp.",
            'help'              => "Depends on the camp age range.",
            'selection'         => [
                'cricket',
                'ladybug',
                'dragonfly'
            ]
        ],
        'employees' => [
            'type'              => 'string',
            'description'       => "Names of the employees responsible for the camp groups."
        ],
        'age' => [
            'type'              => 'string',
            'description'       => "The age(s) concerned by the quantities."
        ],
        'qty_male' => [
            'type'              => 'integer',
            'description'       => "Quantity of male children attending the camp."
        ],
        'qty_female' => [
            'type'              => 'integer',
            'description'       => "Quantity of female children attending the camp."
        ],
        'qty_old' => [
            'type'              => 'integer',
            'description'       => "Quantity of children attending a camp for the first time."
        ],
        'qty_new' => [
            'type'              => 'integer',
            'description'       => "Quantity of children attending who already have participated to a camp."
        ],
        'qty_ase' => [
            'type'              => 'integer',
            'description'       => "Quantity of ASE children attending the camp."
        ],
        'qty' => [
            'type'              => 'integer',
            'description'       => "Quantity of children attending the camp."
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
        'date_from',
        'date_to',
        'location',
        'center_id' => [
            'name'
        ],
        'enrollments_ids' => [
            'status',
            'is_ase',
            'child_age',
            'child_id' => [
                'gender'
            ]
        ],
        'camp_groups_ids' => [
            'employee_id' => [
                'name'
            ]
        ]
    ])
    ->get(true);

$map_children_ids = [];
foreach($camps as $camp) {
    foreach($camp['enrollments_ids'] as $enrollment) {
        if(!isset($map_children_ids[$enrollment['child_id']['id']])) {
            $map_children_ids[$enrollment['child_id']['id']] = true;
        }
    }
}
$children_ids = array_keys($map_children_ids);

$children_enrollments = Enrollment::search(['child_id', 'in', $children_ids])
    ->read([
        'child_id',
        'camp_id' => ['date_from']
    ])
    ->get();

$map_children_first_camp_date = [];
foreach($children_enrollments as $enrollment) {
    if(isset($map_children_first_camp_date[$enrollment['child_id']]) && $map_children_first_camp_date[$enrollment['child_id']] < $enrollment['camp_id']['date_from']) {
        continue;
    }

    $map_children_first_camp_date[$enrollment['child_id']] = $enrollment['camp_id']['date_from'];
}

foreach($camps as $camp) {
    $map_age_data = [];

    $employees = [];
    foreach($camp['camp_groups_ids'] as $group) {
        if(isset($group['employee_id'])) {
            $employees[] = $group['employee_id']['name'];
        }
    }

    foreach($camp['enrollments_ids'] as $enrollment) {
        if($enrollment['status'] !== 'validated') {
            continue;
        }
        if(!isset($map_age_data[$enrollment['child_age']])) {
            $map_age_data[$enrollment['child_age']] = [
                'center'        => $camp['center_id']['name'],
                'code'          => str_pad($camp['sojourn_number'], 3, '0', STR_PAD_LEFT),
                'dates'         => date('d/m/y', $camp['date_from']).' -> '.date('d/m/y', $camp['date_to']),
                'location'      => $camp['location'],
                'employees'     => implode(', ', $employees),
                'age'           => $enrollment['child_age'],
                'qty_male'      => 0,
                'qty_female'    => 0,
                'qty_old'       => 0,
                'qty_new'       => 0,
                'qty_ase'       => 0,
                'qty'           => 0
            ];
        }

        $map_age_data[$enrollment['child_age']]['qty']++;
        switch($enrollment['child_id']['gender']) {
            case 'M':
                $map_age_data[$enrollment['child_age']]['qty_male']++;
                break;
            case 'F':
                $map_age_data[$enrollment['child_age']]['qty_female']++;
                break;
        }

        if($map_children_first_camp_date[$enrollment['child_id']['id']] < $camp['date_from']) {
            $map_age_data[$enrollment['child_age']]['qty_old']++;
        }
        else {
            $map_age_data[$enrollment['child_age']]['qty_new']++;
        }

        if($enrollment['is_ase']) {
            $map_age_data[$enrollment['child_age']]['qty_ase']++;
        }
    }

    if(empty($map_age_data)) {
        continue;
    }

    if($params['by_age']) {
        $result = array_merge($result, $map_age_data);
    }
    else {
        $ages = array_keys($map_age_data);
        sort($ages);

        $result[] = [
            'center'        => $camp['center_id']['name'],
            'code'          => str_pad($camp['sojourn_number'], 3, '0', STR_PAD_LEFT),
            'dates'         => date('d/m/y', $camp['date_from']).' -> '.date('d/m/y', $camp['date_to']),
            'location'      => $camp['location'],
            'employees'     => implode(', ', $employees),
            'age'           => implode(', ', $ages),
            'qty_male'      => array_sum(array_column($map_age_data, 'qty_male')),
            'qty_female'    => array_sum(array_column($map_age_data, 'qty_female')),
            'qty_old'       => array_sum(array_column($map_age_data, 'qty_old')),
            'qty_new'       => array_sum(array_column($map_age_data, 'qty_new')),
            'qty_ase'       => array_sum(array_column($map_age_data, 'qty_ase')),
            'qty'           => array_sum(array_column($map_age_data, 'qty'))
        ];
    }
}

usort($result, function($a, $b) {
    $result = strcmp($a['center'], $b['center']);
    if($result === 0) {
        $result = $a['code'] <=> $b['code'];
        if($result === 0) {
            return strcmp($a['age'], $b['age']);
        }
    }
    return $result;
});

$context->httpResponse()
        ->body($result)
        ->send();
