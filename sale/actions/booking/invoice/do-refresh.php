<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\booking\Invoice;

[$params, $providers] = eQual::announce([
    'description'   => "Force refresh of the paid status of an invoice.",
    'params'        => [
        'id' => [
            'type'          => 'integer',
            'description'   => 'Identifier of the invoice to reverse.',
            'min'           => 1,
            'required'      => true
        ]
    ],
    'access'        => [
        'visibility'    => 'protected',
        'groups'        => ['finance.default.user', 'booking.default.user']
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

Invoice::id($params['id'])->update(['is_paid' => null]);


$context
    ->httpResponse()
    ->status(204)
    ->send();
