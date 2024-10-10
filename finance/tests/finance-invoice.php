<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2024
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\orm\ModelFactory;
use finance\accounting\InvoiceLine;
use finance\accounting\InvoiceLineGroup;
use identity\Identity;
use identity\Partner;
use finance\accounting\Invoice;
use sale\catalog\Product;
use sale\price\Price;

$tests = [
    '0001' => [
        'description'       => 'Validate the invoice price  including the TVA.',
        'help'              => "
            Creates a invoice with configuration below and test the consistency between  invoice price, invoice line price, invoice total and invoice line total. \n
            Identity : Test Identity 1
            Partner: code 4
            Product: sku S0001
            ",
        'arrange'           =>  function () {
            $product = Product::search(['sku', '=', 'S0001'])->read(['id', 'name'])->first(true);

            $identity_data = ModelFactory::create(Identity::class, [
                "values" => [
                    "type_id"        => 4,
                    "legal_name"     => "Test Identity 1"
                ]
            ]);

            $identity = Identity::create(
                    $identity_data
                )
                ->read(['id', 'name', 'type_id'])
                ->first(true);

            $partner = Partner::create([
                    'partner_identity_id'       => $identity['id'],
                    'relationship'              => 'customer'
                ])
                ->read(['id','name'])
                ->first(true);

            return [$product['id'], $partner['id']];
        },
        'act'               =>  function ($data) {
            list($product_id, $partner_id) = $data;

            $product = Product::id($product_id)->read(['id','name'])->first(true);

            $price = Price::search(['product_id','=', $product['id']])->read(['id','name'])->first(true);

            $invoice = Invoice::create([
                    'partner_id'    => $partner_id
                ])
                ->read('id')
                ->first(true);

            $invoice_group = InvoiceLineGroup::create([
                    'name'          => "Group"  . $product['name'],
                    'invoice_id'    => $invoice['id']
                ])
                ->read(['id'])
                ->first(true);

            $invoice_line = InvoiceLine::create([
                    'name'                      => "Group"  . $product['name'],
                    'invoice_id'                => $invoice['id'],
                    'invoice_line_group_id'     => $invoice_group['id'],
                    'product_id'                => $product['id'],
                    'price_id'                  => $price['id'],
                    'qty'                       => 1
                ])
                ->read(['id','price', 'total'])
                ->first(true);

            $invoice = Invoice::id($invoice['id'])
                ->read(['price', 'total'])
                ->first(true);

            return [$invoice, $invoice_line];

        },
        'assert'            =>  function ($data) {

            list($invoice, $invoice_line) = $data;

            return (
                $invoice['total'] == 100 &&
                $invoice['total'] == $invoice_line['total'] &&
                $invoice['price'] == $invoice_line['price']
            );

        },
        'rollback'          =>  function () {

            $identity = Identity::search(['name', '=', 'Test Identity 1'])->read('id')->first(true);
            $partner = Partner::search(['partner_identity_id', '=', $identity['id']])->read('id')->first(true);

            Invoice::search(['partner_id', '=', $partner['id']])->delete(true);
            Partner::id($partner['id'])->delete(true);
            Identity::id($identity['id'])->delete(true);
        }
    ]
];
