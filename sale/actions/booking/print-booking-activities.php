<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\booking\TimeSlot;

[$params, $providers] = eQual::announce([
    'description'   => "Render booking activities planning for a specific date and time slot.",
    'params'        => [
        'params' => [
            'type'          => 'array',
            'description'   => "Additional params to relay to the data controller.",
            'default'       => []
        ],
        'date_from' => [
            'type'              => 'date',
            'description'       => "Start date of the interval (included).",
            'required'          => true,
            'default'           => function($params) {
                if(!isset($params['date_from'])) {
                    return strtotime('Monday this week');
                }

                return strtotime($params['date_from']);
            }
        ],
        'time_slot_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\booking\TimeSlot',
            'description'       => "Time slot of the planning.",
            'required'          => true,
            'domain'            => ['code', 'in', ['AM', 'PM', 'EV']],
            'default'           => function($params) {
                if(!isset($params['time_slot_id'])) {
                    return null;
                }

                $time_slot = TimeSlot::id($params['time_slot_id'])
                    ->read(['name'])
                    ->first();

                if(is_null($time_slot)) {
                    return null;
                }

                return [
                    'id'    => $time_slot['id'],
                    'name'  => $time_slot['name']
                ];
            }
        ]
    ],
    'access' => [
        'visibility'        => 'protected',
        'groups'            => ['camp.default.user'],
    ],
    'response'      => [
        'content-type'      => 'application/pdf',
        'accept-origin'     => '*'
    ],
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context  $context
 */
['context' => $context] = $providers;

$output = eQual::run('get', 'sale_booking_print-booking-activities', [
    'date_from'     => $params['date_from'],
    'time_slot_id'  => $params['time_slot_id']
]);

$context->httpResponse()
        ->header('Content-Disposition', 'inline; filename="planning.pdf"')
        ->body($output)
        ->send();
