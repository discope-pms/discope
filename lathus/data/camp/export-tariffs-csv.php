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

$output = str_replace('"', '', $output);

// #memo - add historic tariffs because don't know if they are used
$output .= "D1;610;Adhérents/Partenaires Vienne/Habitants des cantons
D2;695;Habitants Vienne/Partenaires hors Vienne
D3;765;Autres
L01;42.5;ALSH CCVG S
L02;45;ALSH CCVG S
L03;47;ALSH CCVG S
L04;49.5;ALSH CCVG S
L05;10.5;ALSH CCVG jour
L06;11;ALSH CCVG jour
L07;11.5;ALSH CCVG jour
L08;12;ALSH CCVG jour
L09;54;ALSH non CCVG S
L10;56.5;ALSH non CCVG S
L11;59;ALSH non CCVG S
L12;61.5;ALSH non CCVG S
L13;13;ALSH non CCVG jour
L14;13.5;ALSH non CCVG jour
L15;14;ALSH non CCVG jour
L16;14.5;ALSH non CCVG jour
L17;34.5;ALSH CCVG S 4j
L18;36;ALSH CCVG S 4j
L19;38;ALSH CCVG S 4j
L20;40;ALSH CCVG S 4j
L21;43;ALSH Non CCVG S 4j
L22;45;ALSH Non CCVG S 4j
L23;47;ALSH Non CCVG S 4j
L24;49;ALSH Non CCVG S 4j
P1;257;Habitants de Montmorillon, St Savin, Lussac, L'Isle Jourdain, La Trimouille, Availles-Limousines
P2;294;Partenaires
P3;337;Autres
T1;257;Habitants de Montmorillon,St Savin
T2;294;Partenaires
T3;337;Autres
W;92;Week end";

$context->httpResponse()
    ->body($output)
    ->send();
