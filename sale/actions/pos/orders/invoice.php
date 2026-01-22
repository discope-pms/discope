<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use core\setting\Setting;
use identity\Center;
use finance\accounting\Invoice;
use finance\accounting\InvoiceLine;
use finance\accounting\InvoiceLineGroup;
use sale\catalog\Product;
use sale\pos\Order;

list($params, $providers) = eQual::announce([
    'description'   => "Generates an invoice with all cashdesk orders made by the given Center for a given month.",
    'params'        => [
        'domain' =>  [
            'description'   => 'Domain to limit the result set (specifying a month is mandatory).',
            'type'          => 'array',
            'default'       => []
        ],
        'params' =>  [
            'description'   => 'Additional params, if any',
            'type'          => 'array',
            'default'       => []
        ]
    ],
    'access' => [
        'groups'            => ['pos.default.user', 'pos.default.administrator', 'admins'],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'adapt']
]);

list($context, $orm, $dap) = [$providers['context'], $providers['orm'], $providers['adapt']];

/** @var \equal\data\adapt\DataAdapter */
$adapter = $dap->get('json');

if(isset($params['params']['all_months'])) {
    $all_months = $adapter->adaptIn($params['params']['all_months'], 'number/boolean');
    if($all_months) {
        throw new Exception('missing_month', EQ_ERROR_INVALID_PARAM);
    }
}

if(!isset($params['params']['center_id'])) {
    throw new Exception('missing_center', EQ_ERROR_INVALID_PARAM);
}

$center_id = $adapter->adaptIn($params['params']['center_id'], 'number/integer');
if($center_id <= 0) {
    throw new Exception('missing_center', EQ_ERROR_INVALID_PARAM);
}

$center = Center::id($center_id)
    ->read([
        'center_office_id',
        'pos_default_customer_id',
        'organisation_id' => [
            'has_vat'
        ]
    ])
    ->first(true);

if(!$center) {
    throw new Exception('missing_center', EQ_ERROR_UNKNOWN_OBJECT);
}

if(!$center['pos_default_customer_id']) {
    throw new Exception('unsupported_center', EQ_ERROR_INVALID_PARAM);
}

$vat_rounding_product = null;
$vat_rounding_product_price = null;
if($center['organisation_id']['has_vat']) {
    $sku_vat_rounding_product = Setting::get_value('sale', 'organization', 'sku.vat_rounding_product');

    if(is_null($sku_vat_rounding_product)) {
        throw new Exception('missing_sku_vat_rounding_product');
    }

    $vat_rounding_product = Product::search(['sku', '=', $sku_vat_rounding_product])
        ->read(['id', 'name', 'prices_ids' => ['price_list_id' => ['date_from', 'date_to', 'status']]])
        ->first();

    if(is_null($vat_rounding_product)) {
        throw new Exception('vat_rounding_product_not_found');
    }

    foreach($vat_rounding_product['prices_ids'] as $price) {
        if(
            in_array($price['price_list_id']['status'], ['pending', 'published'])
            && $price['price_list_id']['date_from'] <= strtotime('midnight')
            && $price['price_list_id']['date_to'] >= strtotime('midnight')
        ) {
            $vat_rounding_product_price = $price;
            break;
        }
    }

    if(is_null($vat_rounding_product_price)) {
        throw new Exception('vat_rounding_product_price_not_found');
    }
}

if(!isset($params['params']['date'])) {
    throw new Exception('missing_month', EQ_ERROR_INVALID_PARAM);
}

$date = $adapter->adaptIn($params['params']['date'], 'date/plain');
if(is_null($date) || $date <= 0) {
    throw new Exception('missing_month', EQ_ERROR_INVALID_PARAM);
}

$first_date = strtotime(date('Y-m-01 00:00:00', $date));
$last_date = strtotime('first day of next month', $first_date);

// search cashdesk orders ("vente comptoir") - not related to a booking
$orders = Order::search([
        ['status', '=', 'paid'],
        ['price', '>', 0],
        ['funding_id', '=', null],
        ['booking_id', '=', null],
        ['invoice_id', '=', null],
        ['center_id', '=', $center_id],
        // #memo - we do not use start date to make sure that any passed order not yet invoiced is included
        ['created', '<', $last_date],
        ['created', '>=', $first_date]
    ])
    ->read([
        'id',
        'name',
        'status',
        'created',
        'price',
        'customer_id',
        'order_lines_ids' => [
            'product_id' => ['id', 'name'],
            'price_id',
            'vat_rate',
            'unit_price',
            'qty',
            'free_qty',
            'discount',
            'price',
            'total'
        ]
    ])
    ->get(true);

// retrieve customer id
$customer_id = $center['pos_default_customer_id'];

// create invoice and invoice lines
$invoice = Invoice::create([
        'date'              => time(),
        'organisation_id'   => $center['organisation_id']['id'],
        'center_office_id'  => $center['center_office_id'],
        'status'            => 'proforma',
        'partner_id'        => $customer_id,
        'has_orders'        => true
    ])
    ->read(['id'])
    ->first(true);

$invoice_line_group = InvoiceLineGroup::create([
        'name'              => 'Ventes comptoir',
        'invoice_id'        => $invoice['id']
    ])
    ->read(['id'])
    ->first(true);

$orders_ids = [];

$paid_amount = 0.0;

foreach($orders as $order) {
    // check order consistency
    if($order['status'] != 'paid') {
        continue;
    }
    try {
        $orders_ids[] = $order['id'];
        // create invoice lines
        foreach($order['order_lines_ids'] as $line) {
            // create line in several steps (not to overwrite final values from the line - that might have been manually adapted)
            InvoiceLine::create([
                    'invoice_id'                => $invoice['id'],
                    'invoice_line_group_id'     => $invoice_line_group['id'],
                    'product_id'                => $line['product_id']['id'],
                    'description'               => $line['product_id']['name'],
                    'price_id'                  => $line['price_id']
                ])
                ->update([
                    'vat_rate'                  => $line['vat_rate'],
                    'unit_price'                => $line['unit_price'],
                    'qty'                       => $line['qty'],
                    'free_qty'                  => $line['free_qty'],
                    'discount'                  => $line['discount']
                ])
                ->update([
                    'total'                     => $line['total']
                ])
                ->update([
                    'price'                     => $line['price']
                ]);
        }
        // attach the invoice to the Order, and mark it as having an invoice
        Order::id($order['id'])->update(['invoice_id' => $invoice['id']]);

        $paid_amount = round($paid_amount + $order['price'], 2);
    }
    catch(Exception $e) {
        // ignore errors (must be resolved manually)
    }
}

if($center['organisation_id']['has_vat']) {
    $invoice = Invoice::id($invoice['id'])
        ->read(['price'])
        ->first();

    if($invoice['price'] !== $paid_amount) {
        $rounding_vat_amount = round($paid_amount - $invoice['price'], 2);

        InvoiceLine::create([
            'invoice_id'            => $invoice['id'],
            'invoice_line_group_id' => $invoice_line_group['id'],
            'product_id'            => $vat_rounding_product['id'],
            'description'           => $vat_rounding_product['name'],
            'price_id'              => $vat_rounding_product_price['id']
        ])
            ->update([
                'unit_price'        => $rounding_vat_amount,
                'qty'               => 1
            ]);

        Invoice::id($invoice['id'])->update(['price' => $paid_amount]);
    }
}

// create (exportable) payments for involved orders
/**
 *
 * Only the following teams are using this feature:
 *   - Ovifat : 27
 *   - Wanne  : 30
 *   - LLN : 28
 *   - Eupen: 24
 *   - HSL : 26
 *   - HVG : 32
 *
 * Not using this feature:
 *   - VSG : 25
 *   - GG
*/
if(in_array($center_id, [27, 30, 28, 24, 26, 32]) && $date >= strtotime('2024-04-01 00:00:00')) {
    eQual::run('do', 'sale_pos_orders_payments', [
            'ids' => $orders_ids
        ]);
}

$context->httpResponse()
        ->status(204)
        ->send();
