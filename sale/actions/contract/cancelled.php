<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\contract\Contract;

[$params, $providers] = eQual::announce([
    'description'   => "Sets contract as cancelled.",
    'params'        => [
        'id' => [
            'type'          => 'integer',
            'description'   => 'Identifier of the contract to cancel.',
            'min'           => 1,
            'required'      => true
        ]
    ],
    'access'        => [
        'groups'        => ['admins']
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



Contract::id($params['id'])->update(['status' => 'cancelled']);


$context
    ->httpResponse()
    ->status(204)
    ->send();