<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use finance\accounting\Invoice;

list($params, $providers) = announce([
    'description'   => "Sets invoice as invoiced.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the invoice.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ],
    ],
    'access' => [
        'visibility'        => 'protected',
        'groups'            => ['finance.default.user'],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context']
]);


$context = $providers['context'];

Invoice::id($params['id'])->update(['status' => 'invoice']);

$context->httpResponse()
        ->status(204)
        ->send();