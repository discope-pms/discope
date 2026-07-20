<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use discope\setting\Setting;
use hr\employee\Employee;
use sale\booking\BookingActivity;

[$params, $providers] = eQual::announce([
    'description'   => "Modifies employee contracts dates.",
    'params'        => [

        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'hr\employee\Employee',
            'description'       => 'Identifier of the employee.',
            'required'          => true
        ],

        'date_start' => [
            'type'              => 'date',
            'description'       => 'Date of the first day of work.',
            'required'          => true,
            'default'           => function($id) {
                $employee = Employee::id($id)
                    ->read(['date_start'])
                    ->first();

                return $employee['date_start'];
            }
        ],

        'has_date_end' => [
            'type'              => 'boolean',
            'description'       => 'Has an end date.',
            'required'          => true,
            'default'           => function($id) {
                $employee = Employee::id($id)
                    ->read(['date_end'])
                    ->first();

                return (bool) $employee['date_end'];
            }
        ],

        'date_end' => [
            'type'              => 'date',
            'description'       => 'Date of the last day of work.',
            'help'              => 'Date at which the contract ends (known in advance for fixed-term or unknown for permanent).',
            'required'          => true,
            'default'           => function($id) {
                $employee = Employee::id($id)
                    ->read(['date_end'])
                    ->first();

                return $employee['date_end'];
            },
            'visible'           => ['has_date_end', '=', true]
        ],

        'unassign_activities' => [
            'type'              => 'boolean',
            'description'       => 'Unassign the future activities outside of the dates interval.',
            'default'           => false,
            'required'          => true,
            'visible'           => Setting::get_value('sale','features', 'booking.activity', false)
        ]

    ],
    'access'        => [
        'visibility'    => 'protected',
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

$employee = Employee::id($params['id'])
    ->read(['date_start', 'date_end'])
    ->first();

if(!$employee) {
    throw new Exception("unknown_employee", EQ_ERROR_UNKNOWN_OBJECT);
}

if(isset($params['date_end'])) {
    if($params['date_end'] < $params['date_from']) {
        throw new Exception("date_to_invalid", EQ_ERROR_INVALID_PARAM);
    }
}

if($params['has_date_end']) {
    if(!$params['date_end']) {
        throw new Exception("date_end_invalid", EQ_ERROR_INVALID_PARAM);
    }
}
else {
    $params['date_end'] = null;
}

Employee::id($employee['id'])
    ->update([
        'date_start'    => $params['date_start'],
        'date_end'      => $params['date_end']
    ]);

if($params['unassign_activities']) {
    Employee::id($employee['id'])->do('unassign_activities');
}

$context->httpResponse()
        ->status(200)
        ->send();
