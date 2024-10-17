<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use sale\contract\Contract;

list($params, $providers) = announce([
    'description'   => "Mark a contract as signed (signed version has been received).",
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
        'groups'            => ['booking.default.user'],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);

$context = $providers['context'];

$contract = Contract::id($params['id'])
                  ->read(['id', 'name', 'status', 'valid_until'])
                  ->first();

if(!$contract) {
    throw new Exception("unknown_contract", QN_ERROR_UNKNOWN_OBJECT);
}

if($contract['status'] != 'sent') {
    throw new Exception("invalid_status", QN_ERROR_NOT_ALLOWED);
}

Contract::id($params['id'])->update(['status' => 'signed']);

$context->httpResponse()
        ->status(204)
        ->send();