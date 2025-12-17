<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use core\setting\Setting;
use documents\export\ExportingTask;
use documents\export\ExportingTaskLine;
use sale\booking\Booking;

[$params, $providers] = eQual::announce([
    'description'   => "Creates an export task to generate XLS guest lists for all bookings from last week.",
    'params'        => [

        'exporting_task_name' => [
            'type'          => 'string',
            'description'   => "Name of the generated exporting task.",
            'help'          => "Use {week} for week number and {year} for year.",
            'default'       => "%4d{year}-W%2d{week}_guests_lists"
        ],

        'exporting_task_line_name' => [
            'type'          => 'string',
            'description'   => "Name of the generated exporting task lines.",
            'help'          => "Use {booking} for booking name.",
            'default'       => "%6d{booking}_guests_list"
        ]

    ],
    'access'        => [
        'visibility'    => 'protected',
        'groups'        => ['booking.default.user']
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'constants'     => ['DEFAULT_LANG'],
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context  $context
 */
['context' => $context] = $providers;

$first_day_week = strtotime('monday last week');
$last_day_week  = strtotime('sunday last week');

$exporting_task_name = Setting::parse_format($params['exporting_task_name'], [
    'year'  => date('o', $first_day_week),
    'week'  => date('W', $first_day_week)
]);

$exporting_tasks_ids = ExportingTask::search(['name', '=', $exporting_task_name])->ids();
if(!empty($exporting_tasks_ids)) {
    throw new Exception("exporting_task_already_exists", EQ_ERROR_INVALID_PARAM);
}

$bookings = Booking::search([
    ['date_from', '>=', $first_day_week],
    ['date_to', '<=', $last_day_week],
    ['is_cancelled', '=', false],
    ['status', '<>', 'quote'],
    ['guest_list_id', '<>', null]
])
    ->read(['name'])
    ->get();

$exporting_task = ExportingTask::create([
    'name'    => $exporting_task_name,
    'is_temp' => true
])
    ->read(['id'])
    ->first();

foreach($bookings as $id => $booking) {
    $exporting_task_line_name = Setting::parse_format($params['exporting_task_line_name'], [
        'booking' => $booking['name']
    ]);

    ExportingTaskLine::create([
        'name'              => $exporting_task_line_name,
        'exporting_task_id' => $exporting_task['id'],
        'controller'        => 'sale_booking_create-guestlist-xls',
        'params'            => json_encode([
            'id'                => $id,
            'document_name'     => $exporting_task_line_name
        ])
    ]);
}

$context->httpResponse()
        ->body($exporting_task['id'])
        ->status(201)
        ->send();
