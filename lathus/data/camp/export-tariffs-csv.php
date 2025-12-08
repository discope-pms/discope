<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\camp\catalog\Product;
use sale\camp\price\Price;
use sale\camp\price\PriceList;

[$params, $provider] = eQual::announce([
    'description'   => "Returns tariffs list in CSV format, for the export to Lathus website.",
    'params'        => [],
    'access'        => [
        'visibility'            => 'protected'
    ],
    'response'      => [
        'content-type'          => 'text/csv',
        'content-disposition'   => 'inline; filename="site_tarifs.csv"',
        'charset'               => 'utf-8',
        'accept-origin'         => '*'
    ],
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context  $context
 */
['context' => $context] = $provider;

$year = intval(date('Y'));
if(intval(date('m')) >= 9) {
    $year++;
}

$current_price_list = PriceList::search([
    ['name', 'ilike', '%camp%'],
    ['date_from', '>=', strtotime('first day of January '.$year)],
    ['date_to', '<=', strtotime('last day of December '.$year)]
])
    ->read(['id'])
    ->first();

if(is_null($current_price_list)) {
    throw new Exception("camp_price_list_not_found", EQ_ERROR_UNKNOWN_OBJECT);
}

$products_ids = Product::search([
    ['is_camp', '=', true],
    ['camp_product_type', '=', 'full']
])
    ->ids();

$prices = Price::search([
    ['price_list_id', '=', $current_price_list['id']],
    ['product_id', 'in', $products_ids]
])
    ->read(['price', 'camp_class', 'product_id' => ['name']])
    ->get(true);

$map_tariffs_prices = [
    'A' => [],
    'B' => [],
    'C' => []
];
foreach($prices as $price) {
    if(strpos($price['product_id']['name'], ' A ') !== false) {
        $map_tariffs_prices['A'][] = $price;
    }
    elseif(strpos($price['product_id']['name'], ' B ') !== false) {
        $map_tariffs_prices['B'][] = $price;
    }
    elseif(strpos($price['product_id']['name'], ' C ') !== false) {
        $map_tariffs_prices['C'][] = $price;
    }
}

$map_camp_classes_labels = [
    'close-member'  => 'Adhérents/Partenaires Vienne/Habitants des cantons',
    'member'        => 'Habitants Vienne/Partenaires hors Vienne',
    'other'         => 'Autres'
];

$data = [];
foreach($map_tariffs_prices as $key => $tariff_prices) {
    usort($tariff_prices, fn($a, $b) => $a['price'] <=> $b['price']);

    $i = 0;
    foreach($tariff_prices as $price) {
        $data[] = [
            $key.++$i,
            intval($price['price']),
            $map_camp_classes_labels[$price['camp_class']]
        ];
    }
}

$tmp_file = tempnam(sys_get_temp_dir(), 'csv');

$fp = fopen($tmp_file, 'w');
foreach($data as $row) {
    fputcsv($fp, $row, ';');
}
fclose($fp);

$output = file_get_contents($tmp_file);

$context->httpResponse()
    ->body($output)
    ->send();
