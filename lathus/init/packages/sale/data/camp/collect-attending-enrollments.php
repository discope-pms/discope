<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\orm\Domain;
use equal\orm\DomainCondition;
use sale\camp\Camp;

[$params, $providers] = eQual::announce([
    'description'   => "Manage children that are attending the current camps.",
    'params'        => [

        'date_from' => [
            'type'              => 'date',
            'description'       => "Date interval lower limit (defaults to first day of the current week).",
            'default'           => function() {
                $date_from = strtotime('last Sunday');

                $first_camp = Camp::search(
                    [
                        ['date_from', '>=', strtotime('last Sunday')],
                        ['date_from', '<', strtotime('last day of December this year')],
                        ['status', '=', 'published']
                    ],
                    ['sort' => ['date_from' => 'asc']]
                )
                    ->read(['date_from'])
                    ->first();

                if($first_camp) {
                    $date_from = $first_camp['date_from'];
                    if(date("l", $date_from) === 'Sunday') {
                        $date_from += 86400;
                    }
                }

                return $date_from;
            }
        ],

        'sojourn_number' => [
            'type'              => 'string',
            'description'       => "Sojourn number."
        ],

        'only_weekend' => [
            'type'              => 'boolean',
            'description'       => "Show only the children present during the weekend.",
            'default'           => false
        ],

        'only_saturday' => [
            'type'              => 'boolean',
            'description'       => "Show only the children present during the Saturday morning.",
            'default'           => false
        ],

        'only_birthday' => [
            'type'              => 'boolean',
            'description'       => "Show only the children with birthday during camp.",
            'default'           => false
        ],

        'confirmed' => [
            'type'              => 'boolean',
            'description'       => "Display enrollments with confirmed status.",
            'default'           => true
        ],

        'validated' => [
            'type'              => 'boolean',
            'description'       => "Display enrollments with validated status.",
            'default'           => true
        ],

        'params' => [
            'description'   => 'Additional params to relay to the data controller.',
            'type'          => 'array',
            'default'       => []
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

/**
 * Functions
 */

$sortResult = function($a, $b, $order, $sort) {
    if(!isset($a[$order], $b[$order])) {
        return 0;
    }

    $val_a = $a[$order]['name'] ?? $a[$order];
    $val_b = $b[$order]['name'] ?? $b[$order];

    if($sort === 'asc') {
        return $val_a <=> $val_b;
    }

    return $val_b <=> $val_a;
};

/**
 * Data controller
 */

$domain = new Domain($params['domain']);

$domain->addCondition(new DomainCondition('status', '=', 'published'));

if(!empty($params['sojourn_number'])) {
    $domain->addCondition(new DomainCondition('sojourn_number', 'like', "%{$params['sojourn_number']}%"));
}
elseif(isset($params['date_from'])) {
    $day_of_week = date('w', $params['date_from']);

    // find previous Sunday
    $sunday = $params['date_from'] - ($day_of_week * 86400);

    // next Friday (+5 days)
    $friday = $sunday + (5 * 86400);

    $domain->addCondition(new DomainCondition('date_from', '>=', $sunday));
    $domain->addCondition(new DomainCondition('date_from', '<=', $friday));
}

$enrollment_weekend_extras = ['none', 'saturday-morning', 'full'];
if($params['only_saturday'] || $params['only_weekend']) {
    $enrollment_weekend_extras = [];
    if($params['only_saturday']) {
        $enrollment_weekend_extras[] = 'saturday-morning';
    }
    if($params['only_weekend']) {
        $enrollment_weekend_extras[] = 'full';
    }
}

$enrollment_statuses = [];
if($params['confirmed']) {
    $enrollment_statuses[] = 'confirmed';
}
if($params['validated']) {
    $enrollment_statuses[] = 'validated';
}

$camps = Camp::search($domain->toArray())
    ->read([
        'short_name',
        'date_from',
        'date_to',
        'enrollments_ids' => [
            '@domain' => [
                ['status', 'in', $enrollment_statuses],
                ['weekend_extra', 'in', $enrollment_weekend_extras]
            ],
            'child_firstname',
            'child_lastname',
            'child_gender',
            'child_birthdate',
            'is_foster',
            'weekend_extra',
            'is_ase',
            'child_remarks',
            'main_guardian_mobile',
            'main_guardian_phone',
            'camp_id'           => ['name'],
            'main_guardian_id'  => ['name'],
            'institution_id'    => ['name']
        ]
    ])
    ->adapt('json')
    ->get();

$result = [];

foreach($camps as $camp) {
    foreach($camp['enrollments_ids'] as $enrollment) {
        $result[] = $enrollment;
    }
}

if($params['only_birthday']) {
    $result = array_filter(
        $result,
        fn($item) => $item['has_camp_birthday']
    );
}

if(isset($params['params']['order'])) {
    $order = $params['params']['order'];
    $sort = $params['params']['sort'] ?? 'asc';

    usort($result, fn($a, $b) => $sortResult($a, $b, $order, $sort));
}

$context->httpResponse()
        ->header('X-Total-Count', count($result))
        ->body(array_values($result))
        ->send();
