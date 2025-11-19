<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

[$params, $providers] = eQual::announce([
    'description'   => "Render booking activities planning for a specific date and time slot.",
    'params'        => [
        'date_from' => [
            'type'              => 'date',
            'description'       => "Start date of the interval (included).",
            'default'           => strtotime('Monday this week'),
            'required'          => true
        ],
        'date_to' => [
            'type'              => 'date',
            'description'       => "End date of the interval (included).",
            'default'           => strtotime('Sunday this week'),
            'required'          => true
        ],
        'time_slot_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\booking\TimeSlot',
            'description'       => "Time slot of the planning.",
            'required'          => true,
            'domain'            => ['code', 'in', ['AM', 'PM', 'EV']]
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

$output = eQual::run('get', 'sale_booking_print-booking-activities', $params);

$context->httpResponse()
        ->header('Content-Disposition', 'inline; filename="planning.pdf"')
        ->body($output)
        ->send();
