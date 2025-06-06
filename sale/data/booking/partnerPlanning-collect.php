<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\booking\BookingActivity;

[$params, $providers] = eQual::announce([
    'description'   => "List activities of partners, used for reminding activities to external employees and providers.",
    'params'        => [
        /**
         * Filters
         */
        'date_from' => [
            'type'              => 'date',
            'description'       => "Start of the time interval of the desired plannings."
        ],
        'date_to' => [
            'type'              => 'date',
            'description'       => "End of the time interval of the desired plannings."
        ],
        'only_not_sent' => [
            'type'              => 'boolean',
            'description'       => "If true show only activities that were not reminded yet to partner.",
            'default'           => true
        ],
        'partners_ids' => [
            'type'              => 'array',
            'description'       => "Partnerships that relate to the identity.",
            'default'           => []
        ],
        'domain' => [
            'type'              => 'array',
            'description'       => "Criteria that results have to match.",
            'default'           => []
        ],

        /**
         * Virtual model columns
         */
        'id' => [
            'type'              => 'integer',
            'description'       => "Combination of partner_id and booking_activity_id.",
            'help'              => "Format: \"{partner_id}{booking_activity_id}{partner_id_length}\"."
        ],
        'partner_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'identity\Partner',
            'description'       => "The counter party organization the invoice relates to.",
            'domain'            => ['relationship', 'in', ['provider', 'employee']]
        ],
        'employee_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'hr\employee\Employee',
            'description'       => "The counter party organization the invoice relates to.",
            'default'           => null
        ],
        'provider_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\provider\Provider',
            'description'       => "The counter party organization the invoice relates to.",
            'default'           => null
        ],
        'booking_activity_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\booking\BookingActivity',
            'description'       => "The activity to organize."
        ],
        'activity_date' => [
            'type'              => 'date',
            'description'       => "Date of the activity."
        ],
        'time_slot_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\booking\TimeSlot',
            'description'       => "Specification of the activity moment."
        ],
        'customer_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\customer\Customer',
            'description'       => "Customer the activity is for."
        ],
        'booking_line_group_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\booking\BookingLineGroup',
            'description'       => "Booking Group the activity is for."
        ],
        'group_num' => [
            'type'              => 'integer',
            'description'       => "Number of the activity booking group."
        ],
        'nb_pers' => [
            'type'              => 'integer',
            'description'       => "Quantity of people in the group."
        ],
        'nb_children' => [
            'type'              => 'integer',
            'description'       => "Quantity of children in the group."
        ],
        'booking_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\booking\Booking',
            'description'       => "Booking the activity is for."
        ],
        'booking_status' => [
            'type'              => 'string',
            'selection'         => [
                'quote',
                'option',
                'confirmed',
                'validated',
                'checkedin',
                'checkedout',
                'invoiced',
                'debit_balance',
                'credit_balance',
                'balanced'
            ],
            'description'       => "Status of the booking.",
        ],
        'relationship' => [
            'type'              => 'string',
            'selection'         => [
                'employee',
                'provider'
            ]
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

$result = [];

if(!isset($params['relationship']) && !empty($params['domain'])) {
    if(is_array($params['domain'][0]) && !empty($params['domain'][0]) && is_string($params['domain'][0][0])) {
        foreach($params['domain'] as $condition) {
            if($condition[0] === 'relationship' && $condition[1] === '=') {
                $params['relationship'] = $condition[2];
                break;
            }
        }
    }
    elseif(is_array($params['domain'][0]) && !empty($params['domain'][0]) && is_array($params['domain'][0][0])) {
        foreach($params['domain'] as $conditions) {
            foreach($conditions as $condition) {
                if($condition[0] === 'relationship' && $condition[1] === '=') {
                    $params['relationship'] = $condition[2];
                    break 2;
                }
            }
        }
    }
}

$domain = [];
if(isset($params['date_from'])) {
    $domain[] = ['activity_date', '>=', $params['date_from']];
}
if(isset($params['date_to'])) {
    $domain[] = ['activity_date', '<=', $params['date_to']];
}

$activities = BookingActivity::search($domain)
    ->read([
        'name',
        'activity_date',
        'group_num',
        'time_slot_id'                  => ['name'],
        'employee_id'                   => ['name'],
        'providers_ids'                 => ['name'],
        'booking_line_group_id'         => ['name', 'nb_pers', 'nb_children'],
        'booking_id'                    => ['name', 'status', 'customer_id' => ['name']],
        'partner_planning_mails_ids'    => ['object_id']
    ])
    ->adapt('json')
    ->get();

if(isset($params['partner_id'])) {
    $params['partners_ids'] = array_merge(
        $params['partners_ids'] ?? [],
        [$params['partner_id']]
    );
}

if(!isset($params['relationship']) || $params['relationship'] === 'employee') {
    foreach($activities as $activity) {
        $reminded_partners_ids = array_column($activity['partner_planning_mails_ids'], 'object_id');

        if(
            is_null($activity['employee_id'])
            || (!empty($params['partners_ids']) && !in_array($activity['employee_id']['id'], $params['partners_ids']))
            || ($params['only_not_sent'] && in_array($activity['employee_id']['id'], $reminded_partners_ids))
        ) {
            continue;
        }

        $partner_id_len = str_pad(strlen(strval($activity['employee_id']['id'])), 2, '0', STR_PAD_LEFT);
        $partner_activity_combined_id = intval($activity['employee_id']['id'].$activity['id'].$partner_id_len);

        $result[] = [
            'id'                    => $partner_activity_combined_id,
            'partner_id'            => $activity['employee_id'],
            'employee_id'           => $activity['employee_id'],
            'booking_activity_id'   => ['id' => $activity['id'], 'name' => $activity['name']],
            'activity_date'         => $activity['activity_date'],
            'time_slot_id'          => $activity['time_slot_id'],
            'customer_id'           => $activity['booking_id']['customer_id'],
            'booking_line_group_id' => $activity['booking_line_group_id'],
            'group_num'             => $activity['group_num'],
            'nb_pers'               => $activity['booking_line_group_id']['nb_pers'],
            'nb_children'           => $activity['booking_line_group_id']['nb_children'],
            'booking_id'            => $activity['booking_id'],
            'booking_status'        => $activity['booking_id']['status'],
            'relationship'          => 'employee'
        ];
    }
}

if(!isset($params['relationship']) || $params['relationship'] === 'provider') {
    foreach($activities as $activity) {
        $reminded_partners_ids = array_column($activity['partner_planning_mails_ids'], 'object_id');

        foreach($activity['providers_ids'] as $provider) {
            if(
                (!empty($params['partners_ids']) && !in_array($provider['id'], $params['partners_ids']))
                || ($params['only_not_sent'] && in_array($provider['id'], $reminded_partners_ids))
            ) {
                continue;
            }

            $partner_id_len = str_pad(strlen(strval($provider['id'])), 2, '0', STR_PAD_LEFT);
            $partner_activity_combined_id = intval($provider['id'].$activity['id'].$partner_id_len);

            $result[] = [
                'id'                    => $partner_activity_combined_id,
                'partner_id'            => $provider,
                'provider_id'           => $provider,
                'booking_activity_id'   => ['id' => $activity['id'], 'name' => $activity['name']],
                'activity_date'         => $activity['activity_date'],
                'time_slot_id'          => $activity['time_slot_id'],
                'customer_id'           => $activity['booking_id']['customer_id'],
                'booking_line_group_id' => $activity['booking_line_group_id'],
                'group_num'             => $activity['group_num'],
                'nb_pers'               => $activity['booking_line_group_id']['nb_pers'],
                'nb_children'           => $activity['booking_line_group_id']['nb_children'],
                'booking_id'            => $activity['booking_id'],
                'booking_status'        => $activity['booking_id']['status'],
                'relationship'          => 'provider'
            ];
        }
    }
}

$context->httpResponse()
        ->header('X-Total-Count', count($result))
        ->body($result)
        ->send();
