<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\orm\Field;
use sale\booking\BookingActivity;
use sale\booking\TimeSlot;

[$params, $providers] = eQual::announce([
    'description'   => "Activities planning for bookings and camps.",
    'params'        => [
        /**
         * Filters
         */
        'date_from' => [
            'type'              => 'date',
            'description'       => "Start of the time interval of the desired plannings.",
            'default'           => fn() => strtotime('Monday this week')
        ],
        'date_to' => [
            'type'              => 'date',
            'description'       => "End of the time interval of the desired plannings.",
            'default'           => fn() => strtotime('Sunday this week')
        ],
        'time_slot_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\booking\TimeSlot',
            'description'       => "Time slot of the planning.",
            'domain'            => ['code', 'in', ['AM', 'PM', 'EV']]
        ],

        /**
         * Virtual model columns
         */
        'id' => [
            'type'              => 'integer',
            'description'       => "Identifier of the booking line."
        ],
        'product' => [
            'type'              => 'string',
            'description'       => "The transport product concerned."
        ],
        'date' => [
            'type'              => 'date',
            'description'       => "The date of the transport has to be taken care of.",
        ],
        'time_slot' => [
            'type'              => 'string',
            'description'       => "The date of the transport has to be taken care of.",
        ],
        'customer' => [
            'type'              => 'string',
            'description'       => "The booking customer the transport product is needed for."
        ]
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'adapt']
]);

/**
 * @var \equal\php\Context $context
 * @var \equal\data\adapt\DataAdapterProvider $dap
 */
['context' => $context, 'adapt' => $dap] = $providers;

$json_adapter = $dap->get('json');

$time_slots = TimeSlot::search(['code', 'in', ['AM', 'PM', 'EV']])
    ->read(['name', 'order'])
    ->get();

$map_timeslots = [];
foreach($time_slots as $id => $time_slot) {
    $map_timeslots[$id] = $time_slot;
}

$domain = [
    ['activity_date', '>=', $params['date_from']],
    ['activity_date', '<=', $params['date_to']],
    ['product_id', '<>', null]
];

if(isset($params['time_slot_id'])) {
    $domain[] = ['time_slot_id', '=', $params['time_slot_id']];
}

$booking_activities = BookingActivity::search($domain)
    ->read([
        'activity_date',
        'time_slot_id',
        'product_id'            => ['name'],
        'booking_id'            => ['status', 'customer_identity_id' => ['address_city']],
        'booking_line_group_id' => ['activity_group_num'],
        'camp_id'               => ['status', 'sojourn_number', 'short_name'],
        'camp_group_id'         => ['activity_group_num']
    ])
    ->get();

$result = [];
foreach($booking_activities as $id => $activity) {
    $status = null;
    $customer_name = '';
    if(!is_null($activity['booking_id'])) {
        // booking activity
        $status = $activity['booking_id']['status'];
        $customer_name = sprintf('%d. %s',
            $activity['booking_line_group_id']['activity_group_num'],
            strtoupper($activity['booking_id']['customer_identity_id']['address_city'])
        );
    }
    elseif(!is_null($activity['camp_id'])) {
        // camp activity
        $status = $activity['camp_id']['status'];
        $customer_name = sprintf('%d. %s %s',
            $activity['camp_group_id']['activity_group_num'],
            str_pad($activity['camp_id']['sojourn_number'], 3, '0', STR_PAD_LEFT),
            $activity['camp_id']['short_name']
        );
    }

    // skip: cancelled
    if($status === 'cancelled') {
        continue;
    }

    $product_name = preg_replace('/\s*\(\d+\)$/', '', $activity['product_id']['name']);

    $time_slot = $map_timeslots[$activity['time_slot_id']];

    $result[] = [
        'id'                => $id,
        'product'           => $product_name,
        'date'              => $json_adapter->adaptOut($activity['activity_date'], Field::MAP_TYPE_USAGE['date']),
        'time_slot'         => $time_slot['name'],
        'time_slot_order'   => $time_slot['order'] ?? -1,
        'customer'          => $customer_name
    ];
}

usort($result, function ($a, $b) {
    // first sort on date
    if($a['date'] !== $b['date']) {
        return strcmp($a['date'], $b['date']);
    }

    // second sort on time slot
    if($a['time_slot_order'] !== $b['time_slot_order']) {
        return $a['time_slot_order'] <=> $b['time_slot_order'];
    }

    // last sort on booking activity name, if any
    return strcmp($a['product'], $b['product']);
});

$context->httpResponse()
        ->header('X-Total-Count', count($result))
        ->body($result)
        ->send();
