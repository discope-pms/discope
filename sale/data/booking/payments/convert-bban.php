<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\booking\BankStatement;

[$params, $providers] = eQual::announce([
    'description'   => "Returns the IBAN format of a bank account as provided by belgian Bank statements (CODA).",
    'params'        => [
        'bban' => [
            'type'          => 'string',
            'description'   => 'BBAN account number.',
            'required'      => true
        ]
    ],
    'access'        => [
        'visibility'    => 'private'
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


$iban = BankStatement::convertBbanToIban($params['bban']);


$context
    ->httpResponse()
    ->status(200)
    ->body(['result' => $iban])
    ->send();
