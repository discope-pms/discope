<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\orm\Domain;
use equal\orm\DomainCondition;
use sale\camp\Camp;

[$params, $providers] = eQual::announce([
    'extends'       => 'core_model_collect',
    'params'        => [
        'entity' =>  [
            'type'              => 'string',
            'description'       => "Full name (including namespace) of the class to look into (e.g. 'core\\User').",
            'default'           => 'sale\camp\price\PriceAdapter'
        ],
        'origin_type' => [
            'type'              => 'string',
            'description'       => "Type of price adapter.",
            'selection'         => [
                'all',
                'other',
                'loyalty-discount'
            ],
            'default' => 'all'
        ],
        'date_from' => [
            'type'              => 'date',
            'description'       => "Date interval lower limit.",
            'default'           => fn() => strtotime('first day of January this year')
        ],
        'date_to' => [
            'type'              => 'date',
            'description'       => 'Date interval upper limit.',
            'default'           => fn() => strtotime('last day of December this year')
        ],
        'price_adapter_type' => [
            'type'              => 'string',
            'selection'         => [
                'all',
                'percent',
                'amount'
            ],
            'description'       => "Type of manual discount (fixed amount or percentage of the price).",
            'default'           => 'all'
        ],
        'min_amount' => [
            'type'              => 'float',
            'description'       => 'Min amount/percentage removed to the enrollment price.',
            'default'           => 0
        ],
        'max_amount' => [
            'type'              => 'float',
            'description'       => 'Max amount/percentage removed to the enrollment price.',
            'default'           => 1000
        ],
        'name' => [
            'type'              => 'string',
            'description'       => 'The name of the price adapter'
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

$result = [];

$domain = new Domain($params['domain']);

if($params['origin_type'] !== 'all') {
    $domain->addCondition(
        new DomainCondition('origin_type', '=', $params['origin_type'])
    );
}
else {
    $domain->addCondition(
        new DomainCondition('origin_type', 'in', ['other', 'loyalty-discount'])
    );
}

if($params['price_adapter_type'] !== 'all') {
    $domain->addCondition(
        new DomainCondition('price_adapter_type', '=', $params['price_adapter_type'])
    );
}

if(!empty($params['name'])) {
    $name_escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $params['name']);

    $domain->addCondition(
        new DomainCondition('name', 'like', '%'.$name_escaped.'%')
    );
}

if(isset($params['date_from']) || isset($params['date_to'])) {
    $camp_dom = [];
    if(isset($params['date_from'])) {
        $camp_dom[] = ['date_from', '>=', $params['date_from']];
    }
    if(isset($params['date_to'])) {
        $camp_dom[] = ['date_to', '<=', $params['date_to']];
    }

    $camps = Camp::search($camp_dom)
        ->read(['enrollments_ids'])
        ->get(true);

    $enrollments_ids = [];
    foreach($camps as $camp) {
        $enrollments_ids = array_merge($enrollments_ids, $camp['enrollments_ids']);
    }

    $domain->addCondition(
        new DomainCondition('enrollment_id', 'in', $enrollments_ids)
    );
}

$params['domain'] = $domain->toArray();

$result = eQual::run('get', 'model_collect', $params, true);

$result = array_filter($result, function($price_adapter) use($params) {
    return $price_adapter['amount'] >= $params['min_amount'] && $price_adapter['amount'] <= $params['max_amount'];
});

$result = array_values($result);

$context
    ->httpResponse()
    ->header('X-Total-Count', count($result))
    ->body($result)
    ->send();
