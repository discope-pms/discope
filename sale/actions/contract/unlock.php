<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use sale\contract\Contract;

list($params, $providers) = announce([
    'description'   => "Unlocks a contract (can be cancelled if booking is reverted to quote).",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the targeted contract.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ]
    ],
    'access' => [
        'visibility'        => 'public',
        'groups'            => ['booking.default.administrator', 'sale.default.administrator'],
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
$context = $providers['context'];


$contract = Contract::id($params['id'])
                    ->read(['id', 'name', 'status', 'valid_until'])
                    ->first(true);

if(!$contract) {
    throw new Exception("unknown_contract", QN_ERROR_UNKNOWN_OBJECT);
}

Contract::id($params['id'])->update(['is_locked' => false]);


$context->httpResponse()
        ->status(204)
        ->send();
