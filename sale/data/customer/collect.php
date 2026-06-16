<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\orm\Domain;
use identity\Identity;

[$params, $providers] = eQual::announce([
    'description'   => 'Advanced search for Customers: returns a collection of Customer according to extra parameters.',
    'extends'       => 'core_model_collect',
    'params'        => [
        'entity' =>  [
            'description'   => 'Full name (including namespace) of the class to look into (e.g. \'core\\User\').',
            'type'          => 'string',
            'default'       => 'sale\customer\Customer'
        ],
        'name' => [
            'type'              => 'string',
            'description'       => 'Name of the customer.'
        ],
        'accounting_account' => [
            'type'              => 'string',
            'description'       => 'Accounting code of the customer identity.'
        ],
        'email' => [
            'type'              => 'string',
            'description'       => 'Email of the customer identity.'
        ]
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
['context' => $context] = $providers;

$domain = $params['domain'];

if(!empty($params['name'])) {
    $domain = Domain::conditionAdd($domain, ['name', 'like', "%{$params['name']}%"]);
}

$identity_domain = false;
$identity_fields = ['accounting_account', 'email'];
foreach($identity_fields as $field) {
    if(!empty($params[$field])) {
        $value = trim($params[$field]);
        $identity_domain[] = [$field, 'like', "%$value%"];
    }
}
if(!empty($identity_domain)) {
    $identities_ids = Identity::search($identity_domain)->ids();
    $domain = Domain::conditionAdd($domain, ['partner_identity_id', 'in', $identities_ids]);
}

$params['domain'] = $domain;

$result = eQual::run('get', 'model_collect', $params, true);

$context
    ->httpResponse()
    ->body($result)
    ->send();
