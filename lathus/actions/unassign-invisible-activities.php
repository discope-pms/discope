<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use hr\employee\Employee;
use sale\booking\BookingActivity;

[$params, $provider] = eQual::announce([
    'description'   => "Unassign future activities that are assigned to deleted employees.",
    'params'        => [
        'handle_out_of_contract' => [
            'type'              => 'boolean',
            'description'       => "Unassign future activities that are assigned out of the contract of employees.",
            'default'           => true
        ],
    ],
    'access'        => [
        'visibility'        => 'protected'
    ],
    'response'      => [
        'content-type'      => 'application/json',
        'charset'           => 'utf-8',
        'accept-origin'     => '*'
    ],
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context  $context
 */
['context' => $context] = $provider;

$employees_ids = Employee::search(['relationship', '=', 'employee'])->ids();

$activities_of_deleted_employees = BookingActivity::search([
    ['date_from', '>=', time()],
    ['employee_id', 'not in', $employees_ids],
    ['employee_id', '<>', null]
])
    ->ids();

// BookingActivity::ids($activities_of_deleted_employees)->update(['employee_id' => null]);

if($params['handle_out_of_contract']) {
    $employees = Employee::search(['relationship', '=', 'employee'])
        ->read(['date_start', 'date_end'])
        ->get();

    foreach($employees as $employee) {
        if($employee['date_start'] > time()) {
            $before_contract_activities_ids = BookingActivity::search([
                ['employee_id', '=', $employee['id']],
                ['activity_date', '>=', time()],
                ['activity_date', '<', $employee['date_start']]
            ])
                ->ids();

            if(!empty($before_contract_activities_ids)) {
                BookingActivity::ids($before_contract_activities_ids)->update(['employee_id' => null]);
            }
        }

        if($employee['date_end'] && $employee['date_end'] > time()) {
            $after_contract_activities_ids = BookingActivity::search([
                ['employee_id', '=', $employee['id']],
                ['activity_date', '>=', time()],
                ['activity_date', '>', $employee['date_end']]
            ])
                ->ids();

            if(!empty($after_contract_activities_ids)) {
                BookingActivity::ids($after_contract_activities_ids)->update(['employee_id' => null]);
            }
        }
    }
}

$context
    ->httpResponse()
    ->status(200)
    ->send();
