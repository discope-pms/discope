<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use sale\booking\Funding;
use sale\booking\Booking;

list($params, $providers) = announce([
    'description'   => "Arbitrary mark a funding as paid (has no effect on actual payments).",
    'deprecated'    => true,
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the targeted funding.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ]
    ],
    'access' => [
        'groups'            => ['booking.default.user', 'sale.default.administrator']
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'cron', 'dispatch']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\orm\ObjectManager            $om
 * @var \equal\cron\Scheduler               $cron
 * @var \equal\dispatch\Dispatcher          $dispatch
 */
list($context, $om, $cron, $dispatch) = [$providers['context'], $providers['orm'], $providers['cron'], $providers['dispatch']];

$funding = Funding::id($params['id'])
    ->read(['booking_id'])
    ->update(['is_paid' => null, 'paid_amount' => null])
    ->first(true);

Booking::id($funding['booking_id'])->update(['paid_amount' => null]);

$context->httpResponse()
        ->status(204)
        ->send();
