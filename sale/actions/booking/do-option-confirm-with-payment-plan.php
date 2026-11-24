<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

[$params, $providers] = eQual::announce([
    'description'   => "Direct update of the booking status from quote to confirmed and apply selected payment plan.",
    'params'        => [
        'id' => [
            'type'              => 'integer',
            'description'       => 'Identifier of the targeted booking.',
            'min'               => 1,
            'required'          => true
        ],
        'payment_plan_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\pay\PaymentPlan',
            'description'       => 'The payment plan to use to create the fundings.',
            'required'          => true
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
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context $context
 */
['context' => $context] = $providers;



eQual::run('do', 'sale_booking_do-option', ['id' => $params['id']]);

eQual::run('do', 'sale_booking_do-confirm-with-payment-plan', ['id' => $params['id'], 'payment_plan_id' => $params['payment_plan_id']]);


$context
    ->httpResponse()
    ->status(204)
    ->send();
