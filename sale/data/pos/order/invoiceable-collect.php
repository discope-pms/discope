<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\orm\Domain;

[$params, $providers] = eQual::announce([
    'description'   => 'Advanced search for Order: returns a collection of Order according to extra parameters.',
    'extends'       => 'core_model_collect',
    'params'        => [
        'entity' => [
            'type'              => 'string',
            'description'       => 'Full name (including namespace) of the class to look into.',
            'default'           => 'sale\pos\Order'
        ],
        'center_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'identity\Center',
            'description'       => "The center to which the booking relates to."
        ],
        'all_months' => [
            'type'              => 'boolean',
            'description'       => "The Orders every month.",
            'default'           => true
        ],
        'date' => [
            'type'              => 'date',
            'description'       => "Date of the creation.",
            'default'           => function() { return strtotime('Today'); }
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
 * @var \equal\php\Context $context
 */
['context' => $context] = $providers;

$domain = $params['domain'];

if(isset($params['center_id']) && $params['center_id'] > 0) {
    $domain = Domain::conditionAdd($domain, ['center_id', '=', $params['center_id']]);
}

if(!$params['all_months'] && isset($params['date']) && $params['date'] > 0) {
    $first_date = strtotime(date('Y-m-01 00:00:00', $params['date']));
    $last_date = strtotime('first day of next month', $first_date);
    $domain = Domain::conditionAdd($domain, ['created', '>=', $first_date]);
    $domain = Domain::conditionAdd($domain, ['created', '<', $last_date]);
}

$params['domain'] = $domain;

$result = eQual::run('get', 'model_collect', $params, true);

$context
    ->httpResponse()
    ->body($result)
    ->send();
