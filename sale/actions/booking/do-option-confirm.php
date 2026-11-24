<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

[$params, $providers] = eQual::announce([
    'description'   => "Direct update of the booking status from quote to confirmed.",
    'params'        => [
        'id' => [
            'type'          => 'integer',
            'description'   => 'Identifier of the targeted booking.',
            'min'           => 1,
            'required'      => true
        ],
        'instant_payment' => [
            'type'          => 'boolean',
            'description'   => 'If set, the \'instant payment\' plan will be generated.',
            'default'       => false
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

eQual::run('do', 'sale_booking_do-confirm', ['id' => $params['id'], 'instant_payment' => $params['instant_payment']]);


$context
    ->httpResponse()
    ->status(204)
    ->send();
