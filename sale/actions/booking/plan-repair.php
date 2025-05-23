<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use core\setting\Setting;
use realestate\RentalUnit;
use sale\booking\Repairing;

list($params, $providers) = eQual::announce([
    'description'   => "Create a repairing from the planning, by providing date range, customer and rental unit.",
    'params'        => [

        'date_from' =>  [
            'description'   => 'Identifier of the targeted booking.',
            'type'          => 'date',
            'required'      => true
        ],

        'date_to' =>  [
            'description'   => 'Identifier of the targeted booking.',
            'type'          => 'date',
            'required'      => true
        ],

        'rental_unit_id' =>  [
            'description'   => 'Identifier of the targeted booking.',
            'type'          => 'integer',
            'required'      => true
        ],

        'name' =>  [
            'description'   => 'Name to set to the repairing, if any.',
            'type'          => 'string',
            'default'       => ''
        ],

        'description' =>  [
            'description'   => 'Short description about the reason of the maintenance.',
            'type'          => 'string',
            'default'       => ''
        ]

    ],
    'access' => [
        'visibility'        => 'protected',
        'groups'            => ['booking.default.user']
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'cron', 'dispatch']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\cron\Scheduler               $cron
 * @var \equal\dispatch\Dispatcher          $dispatch
 */
list($context, $cron, $dispatch) = [$providers['context'], $providers['cron'], $providers['dispatch']];


/*
    Check consistency of parameters
*/

// #todo - if a consumption already exists for the given dates : abort
// #memo - not sure how to handle this situation : teams handle this on a per case basis

// retrieve rental unit and related center
$rental_unit = RentalUnit::id($params['rental_unit_id'])
    ->read(['id', 'name', 'capacity', 'center_id', 'has_parent', 'has_children', 'parent_id', 'children_ids'])
    ->first(true);

if(!$rental_unit) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

/*
    Create a repairing group for given period and add rental unit to it
*/

$data = eQual::run('do', 'realestate_check-unit-available', ['id' => $rental_unit['id'], 'date_from' => $params['date_from'], 'date_to' => $params['date_to'] ]);
if(is_array($data) && count($data)) {
    throw new Exception('invalid_rental_unit_booking', QN_ERROR_INVALID_PARAM);
}

$collection = Repairing::create([
    'center_id'         => $rental_unit['center_id'],
    'description'       => $params['description']
])
    ->update([
        'rental_units_ids'  => [ $params['rental_unit_id'] ],
        'date_from'         => $params['date_from'],
        'date_to'           => $params['date_to']
    ]);

// mark parent unit as partially occupied
if($rental_unit['has_parent']) {
    // #todo
}

// mark all children as 'ooo' as well
if($rental_unit['has_children']) {
    $collection->update(['rental_units_ids' => $rental_unit['children_ids']]);
}

$channelmanager_enabled = Setting::get_value('sale', 'features', 'booking.channel_manager', false);
if($channelmanager_enabled) {
    /*
        Check if consistency must be maintained with channel manager (if repairing impacts a rental unit that is linked to a channelmanager room type)
    */
    $repairing = $collection->first(true);

    $cron->schedule(
        "channelmanager.check-contingencies.{$repairing['id']}",
        time(),
        'sale_booking_check-contingencies',
        [
            'date_from'        => date('c', $params['date_from']),
            // repairings completely cover the last day of the date range
            'date_to'          => date('c', strtotime('+1 day', $params['date_to'])),
            'rental_units_ids' => array_merge([$params['rental_unit_id']], $rental_unit['children_ids'])
        ]
    );
}

$context->httpResponse()
        ->status(204)
        ->send();
