<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use identity\Center;
use identity\User;
use sale\camp\Camp;
use sale\camp\catalog\Product;
use sale\camp\EnrollmentLine;

[$params, $providers] = eQual::announce([
    'description'   => "Data about children's participation to camps.",
    'params'        => [
        'all_centers' => [
            'type'              => 'boolean',
            'description'       => "Mark all the centers of the children quantities.",
            'default'           => false
        ],
        'center_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'identity\Center',
            'description'       => "Center for the children quantities.",
            'default'           => 1
        ],
        'date_from' => [
            'type'              => 'date',
            'description'       => "Date interval lower limit (defaults to first day of the current month).",
            'default'           => fn() => strtotime('first day of this month')
        ],
        'date_to' => [
            'type'              => 'date',
            'description'       => "Date interval upper limit (defaults to last day of the current month).",
            'default'           => fn() => strtotime('last day of this month')
        ],
        'status' => [
            'type'              => 'string',
            'description'       => "The status of the enrollments.",
            'selection'         => [
                'all',
                'validated',
                'pending',
                'waitlisted',
                'cancelled'
            ],
            'default'           => 'validated'
        ],

        /* parameters used as properties of virtual entity */
        'center' => [
            'type'              => 'string',
            'description'       => "Name of the center for the enrollments quantities."
        ],
        'product' => [
            'type'              => 'string',
            'description'       => "Name of the tariff."
        ],
        'qty_other' => [
            'type'              => 'integer',
            'description'       => "Quantity of enrollments of the tariff for the camp class 'other'."
        ],
        'qty_member' => [
            'type'              => 'integer',
            'description'       => "Quantity of enrollments of the tariff for the camp class 'member'."
        ],
        'qty_close_member' => [
            'type'              => 'integer',
            'description'       => "Quantity of enrollments of the tariff for the camp class 'close-member'."
        ],
        'qty' => [
            'type'              => 'integer',
            'description'       => "Quantity of enrollments of the tariff."
        ],
        'price' => [
            'type'              => 'float',
            'usage'             => 'amount/money',
            'description'       => "Quantity of enrollments of the tariff."
        ]
    ],
    'access'        => [
        'visibility'    => 'protected',
        'groups'        => ['camp.default.user'],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'adapt' , 'auth']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\data\adapt\AdapterProvider   $adapter_provider
 * @var \equal\auth\AuthenticationManager   $auth
 */
['context' => $context, 'adapt' => $adapter_provider, 'auth' => $auth] = $providers;

$domain = [
    ['date_from', '>=', $params['date_from']],
    ['date_from', '<=', $params['date_to']],
    ['status', '=', 'published']
];

if($params['all_centers']) {
    $user_id = $auth->userId();
    if($user_id <= 0) {
        throw new Exception("unknown_user", EQ_ERROR_NOT_ALLOWED);
    }
    $user = User::id($user_id)->read(['centers_ids'])->first();
    if(is_null($user)) {
        throw new Exception("unexpected_error", EQ_ERROR_INVALID_USER);
    }
    $domain[] = ['center_id', 'in', $user['centers_ids']];
}
elseif(isset($params['center_id']) && $params['center_id'] > 0) {
    $domain[] = ['center_id', '=', $params['center_id']];
}

$result = [];

$camps = Camp::search($domain)
    ->read([
        'is_clsh',
        'product_id',
        'day_product_id',
        'center_id',
        'enrollments_ids' => [
            'status',
            'camp_class'
        ]
    ])
    ->get(true);

$map_center_tariffs_enrollments_quantities = [];
$map_products = [];

foreach($camps as $camp) {
    foreach($camp['enrollments_ids'] as $enrollment) {
        if($params['status'] !== 'all' && $enrollment['status'] !== $params['status']) {
            continue;
        }

        $camp_product_id = null;
        $qty = 0;
        $price = 0.0;
        if($camp['is_clsh']) {
            $clsh_camp_products_ids = Product::search(['camp_product_type', 'in', ['clsh-full-5-days', 'clsh-full-4-days', 'clsh-day']])->ids();

            $camp_product_line = EnrollmentLine::search([
                ['product_id', 'in', $clsh_camp_products_ids],
                ['enrollment_id', '=', $enrollment['id']]
            ])
                ->read(['qty', 'price', 'product_id' => ['camp_product_type']])
                ->first();

            $camp_product_id = $camp_product_line['product_id']['id'];
            if(is_null($camp_product_id)) {
                continue;
            }

            if($camp_product_line['product_id']['camp_product_type'] === 'clsh-day') {
                // should be 1, 2, 3 or 4 if camp_product_type is 'clsh-day' (participation to some days of camp)
                $qty = $camp_product_line['qty'];
            }
            else {
                // should be 1 if camp_product_type is 'clsh-full-5-days' or 'clsh-full-4-days' (participation to entire camp)
                $qty = 1;
            }

            $price = $camp_product_line['price'];
        }
        else {
            $products_ids = Product::search(['camp_product_type', '=', 'full'])->ids();

            $camp_product_line = EnrollmentLine::search([
                ['product_id', 'in', $products_ids],
                ['enrollment_id', '=', $enrollment['id']]
            ])
                ->read(['price', 'product_id'])
                ->first();

            $camp_product_id = $camp_product_line['product_id'];
            if(is_null($camp_product_id)) {
                continue;
            }

            // should be 1 because camp_product_type is 'full' (participation to entire camp)
            $qty = 1;

            $price = $camp_product_line['price'];
        }

        if(!isset($map_center_tariffs_enrollments_quantities[$camp['center_id']][$camp_product_id])) {
            $map_center_tariffs_enrollments_quantities[$camp['center_id']][$camp_product_id] = [
                'qty_other'         => 0,
                'qty_member'        => 0,
                'qty_close_member'  => 0,
                'qty'               => 0,
                'price'             => 0.0
            ];
        }

        switch($enrollment['camp_class']) {
            case 'other':
                $map_center_tariffs_enrollments_quantities[$camp['center_id']][$camp_product_id]['qty_other'] += $qty;
                break;
            case 'member':
                $map_center_tariffs_enrollments_quantities[$camp['center_id']][$camp_product_id]['qty_member'] += $qty;
                break;
            case 'close-member':
                $map_center_tariffs_enrollments_quantities[$camp['center_id']][$camp_product_id]['qty_close_member'] += $qty;
                break;
        }

        $map_center_tariffs_enrollments_quantities[$camp['center_id']][$camp_product_id]['qty'] += $qty;
        $map_center_tariffs_enrollments_quantities[$camp['center_id']][$camp_product_id]['price'] += $price;

        $map_products[$camp_product_id] = true;
    }
}

$center_ids = array_keys($map_center_tariffs_enrollments_quantities);

$centers = Center::search(['id', 'in', $center_ids])
    ->read(['name'])
    ->get();

$products_ids = array_keys($map_products);

$products = Product::search(['id', 'in', $products_ids])
    ->read(['name'])
    ->get();

foreach($map_center_tariffs_enrollments_quantities as $center_id => $map_products_quantities) {
    $center = null;
    foreach($centers as $c) {
        if($c['id'] === $center_id) {
            $center = $c['name'];
            break;
        }
    }

    foreach($map_products_quantities as $product_id => $quantities) {
        $product = null;
        foreach($products as $p) {
            if($p['id'] === $product_id) {
                $product = $p['name'];
                break;
            }
        }

        $result[] = array_merge(
            ['center' => $center, 'product' => $product],
            $quantities
        );
    }
}

usort($result, function($a, $b) {
    $result = strcmp($a['center'], $b['center']);
    if($result === 0) {
        return strcmp($a['product'], $b['product']);
    }
    return $result;
});

$context->httpResponse()
        ->header('X-Total-Count', count($result))
        ->body($result)
        ->send();
