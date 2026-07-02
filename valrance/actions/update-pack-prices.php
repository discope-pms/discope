<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\catalog\Product;
use sale\customer\AgeRange;
use sale\customer\RateClass;
use sale\price\Price;
use sale\price\PriceList;

[$params, $provider] = eQual::announce([
    'description'   => "Update prices 2026-2027 for packs.",
    'params'        => [
        'price_list_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\price\PriceList',
            'description'       => "Identifier of the targeted price list.",
            'required'          => true
        ],
        'csv_filename' => [
            'type'              => 'string',
            'description'       => "Name of the csv file to use.",
            'help'              => "File should be placed at base directory of project.",
            'required'          => true
        ]
    ],
    'access'        => [
        'visibility'        => 'protected'
    ],
    'response'      => [
        'content-type'      => 'application/json',
        'charset'           => 'utf-8',
        'accept-origin'     => '*'
    ],
    'providers'     => ['context', 'orm']
]);

/**
 * @var \equal\php\Context  $context
 */
['context' => $context, 'orm' => $orm] = $provider;

$price_list = PriceList::id($params['price_list_id'])
    ->read(['id', 'name'])
    ->first();

if(!$price_list) {
    throw new Exception('unknown_price_list', EQ_ERROR_UNKNOWN_OBJECT);
}

if(!file_exists(EQ_BASEDIR.'/'.$params['csv_filename'])) {
    throw new Exception("csv_file_not_found", EQ_ERROR_INVALID_PARAM);
}

if(($handle = fopen(EQ_BASEDIR.'/'.$params['csv_filename'], 'r')) === false) {
    throw new Exception("cannot_open_file", EQ_ERROR_INVALID_PARAM);
}

$rate_classes = RateClass::search()
    ->read(['code'])
    ->get();

$map_rate_classes_code_ids = [];
foreach($rate_classes as $rate_class) {
    $map_rate_classes_code_ids[$rate_class['code']] = $rate_class['id'];
}

$age_ranges = AgeRange::search()
    ->read(['name'])
    ->get();

$map_age_ranges_names_ids = [
    'enfant'        => null,
    'enseignant'    => null,
    'chauffeur'     => null,
    'adulte'        => null,
];
foreach($age_ranges as $age_range) {
    if(str_contains(strtolower($age_range['name']), 'enfant')) {
        $map_age_ranges_names_ids['enfant'] = $age_range['id'];
    }
    elseif(str_contains(strtolower($age_range['name']), 'enseignant')) {
        $map_age_ranges_names_ids['enseignant'] = $age_range['id'];
    }
    elseif(str_contains(strtolower($age_range['name']), 'chauffeur')) {
        $map_age_ranges_names_ids['chauffeur'] = $age_range['id'];
    }
    elseif(str_contains(strtolower($age_range['name']), 'adulte')) {
        $map_age_ranges_names_ids['adulte'] = $age_range['id'];
    }
}

$pack_ids = [
    3009, // Pack Séjour Adapté
    2938, // Pack Scolaire PC Séjour court (Classes, CLSH , Colonies)
    3010, // Pack Scolaire PC Séjour court ( Groupes, SA)
    3016, // Pack PC séjour court sans goûter
    2980, // Pack Scolaire PC Séjour long (Classes, CLSH , Colonies)
    3011, // Pack Scolaire PC Séjour long ( Groupes, SA)
    3021, // Pack PC séjour long sans goûter
    3008  // Pack 1er repas séjour long
];

$packs = Product::ids($pack_ids)
    ->read(['pack_lines_ids' => ['child_product_model_id' => ['products_ids']]])
    ->get();

$map_products_ids = [];
foreach($packs as $pack) {
    foreach($pack['pack_lines_ids'] as $pack_line) {
        foreach($pack_line['child_product_model_id']['products_ids'] as $product_id) {
            $map_products_ids[$product_id] = true;
        }
    }
}

$products = Product::ids(array_keys($map_products_ids))
    ->read(['product_model_id' => ['name']])
    ->get(true);

$map_duration_products_ids = [
    'court' => [],
    'long'  => []
];
foreach($products as $product) {
    if(str_contains($product['product_model_id']['name'], 'court')) {
        $map_duration_products_ids['court'][] = $product['id'];
    }
    elseif(str_contains($product['product_model_id']['name'], 'long')) {
        $map_duration_products_ids['long'][] = $product['id'];
    }
}

$new_prices_data = [];

$header = fgetcsv($handle);
while(($row = fgetcsv($handle)) !== false) {
    $new_prices_data[] = [
        'product_ids'   => $map_duration_products_ids[$row[0]],
        'rate_class_id' => $map_rate_classes_code_ids[$row[1]],
        'age_range_id'  => $map_age_ranges_names_ids[$row[2]],
        'prices' => [
            'breakfast'     => strlen($row[3]) ? floatval($row[3]) : null,
            'lunch'         => strlen($row[4]) ? floatval($row[4]) : null,
            'snack'         => strlen($row[5]) ? floatval($row[5]) : null,
            'diner'         => strlen($row[6]) ? floatval($row[6]) : null,
            'night'         => strlen($row[7]) ? floatval($row[7]) : null
        ]
    ];
}

$prices = Price::search([
    ['price_list_id', '=', $price_list['id']],
    ['product_id', 'in', array_keys($map_products_ids)]
])
    ->read([
        'rate_class_id',
        'product_id' => [
            'age_range_id',
            'product_model_id' => [
                'is_accomodation',
                'is_meal',
                'is_snack',
                'time_slot_id' => [
                    'code'
                ]
            ]
        ]
    ])
    ->get();

foreach($new_prices_data as $prices_data) {
    foreach($prices as $id => $price) {
        if(!in_array($price['product_id']['id'], $prices_data['product_ids'])) {
            continue;
        }
        if($prices_data['rate_class_id'] !== $price['rate_class_id']) {
            continue;
        }
        if($prices_data['age_range_id'] !== $price['product_id']['age_range_id']) {
            continue;
        }

        $price_product_type = null;
        if($price['product_id']['product_model_id']['is_meal']) {
            switch($price['product_id']['product_model_id']['time_slot_id']['code']) {
                case 'B':
                    $price_product_type = 'breakfast';
                    break;
                case 'L':
                    $price_product_type = 'lunch';
                    break;
                case 'D':
                    $price_product_type = 'diner';
                    break;
            }
        }
        elseif($price['product_id']['product_model_id']['is_snack']) {
            $price_product_type = 'snack';
        }
        elseif($price['product_id']['product_model_id']['is_accomodation']) {
            $price_product_type = 'night';
        }

        if(is_null($price_product_type) || is_null($prices_data['prices'][$price_product_type])) {
            continue;
        }

        Price::id($id)->update(['price' => $prices_data['prices'][$price_product_type]]);
    }
}

$context->httpResponse()
        ->status(200)
        ->send();
