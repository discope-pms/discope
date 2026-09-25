<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\camp\price\PriceAdapter;
use sale\camp\Sponsor;

[$params, $providers] = eQual::announce([
    'description'   => 'Modifies the price adapter\'s sponsor.',
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\camp\price\PriceAdapter',
            'description'       => 'Identifier of the price adapter to modify.',
            'required'          => true
        ],
        'sponsor_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\camp\Sponsor',
            'description'       => 'Sponsor to attribute to the price adapter.',
            'required'          => true
        ]
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm']
]);

/**
 * @var \equal\php\Context          $context
 * @var \equal\orm\ObjectManager    $orm
 */
['context' => $context, 'orm' => $orm] = $providers;

$price_adapter = PriceAdapter::id($params['id'])
    ->read(['origin_type'])
    ->first();

if(!$price_adapter) {
    throw new Exception('unknown_price_adapter', EQ_ERROR_UNKNOWN_OBJECT);
}

if(!in_array($price_adapter['origin_type'], ['commune', 'community-of-communes', 'department-ase', 'department-caf', 'department-msa'])) {
    throw new Exception('invalid_price_adapter_type');
}

$sponsor = Sponsor::id($params['sponsor_id'])
    ->read(['sponsor_type'])
    ->first();

if(!$sponsor) {
    throw new Exception('unknown_sponsor', EQ_ERROR_UNKNOWN_OBJECT);
}

$orm->update(PriceAdapter::getType(), $price_adapter['id'], [
    'sponsor_id'        => $sponsor['id'],
    'origin_type'       => $sponsor['sponsor_type'],
    'is_manual_disount' => false
]);

$context
    ->httpResponse()
    ->send();

