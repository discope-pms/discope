<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2024
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace finance\accounting;
use equal\orm\Model;

class InvoiceLine extends Model {

    public static function getName() {
        return "Invoice line";
    }

    public static function getDescription() {
        return "Invoice lines describe the products and quantities that are part of an invoice.";
    }

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Default label of the line, based on product (computed).',
                'function'          => 'calcName',
                'store'             => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Complementary description of the line (independant from product).'
            ],

            'invoice_line_group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\InvoiceLineGroup',
                'description'       => 'Group the line relates to (in turn, groups relate to their invoice).',
                'ondelete'          => 'cascade',
                'domain'            => ['invoice_id', '=', 'object.invoice_id']
            ],

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\Invoice',
                'description'       => 'Invoice the line is related to.',
                'required'          => true,
                'onupdate'          => 'onupdateInvoiceId',
                'ondelete'          => 'cascade'
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => 'The product (SKU) the line relates to.',
                'required'          => true
            ],

            'price_id' => [
                'type'              => 'many2one',
                'foreign_object'    => '\sale\price\Price',
                'description'       => 'The price the line relates to (assigned at line creation).',
                'onupdate'          => 'onupdatePriceId'
            ],

            'unit_price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Unit price of the product related to the line.',
                'function'          => 'finance\accounting\InvoiceLine::calcUnitPrice',
                'store'             => true
            ],

            'vat_rate' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/rate',
                'description'       => 'VAT rate to be applied.',
                'function'          => 'calcVatRate',
                'store'             => true,
                'default'           => 0.0,
                'onupdate'          => 'onupdateVatRate'
            ],

            'qty' => [
                'type'              => 'float',
                'description'       => 'Quantity of product.',
                'default'           => 0,
                'onupdate'          => 'onupdateQty'
            ],

            'free_qty' => [
                'type'              => 'integer',
                'description'       => 'Free quantity.',
                'default'           => 0,
                'onupdate'          => 'onupdateFreeQty'
            ],

            'discount' => [
                'type'              => 'float',
                'usage'             => 'amount/rate',
                'description'       => 'Total amount of discount to apply, if any.',
                'default'           => 0.0,
                'onupdate'          => 'onupdateDiscount'
            ],

            'total' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Total tax-excluded price of the line (computed).',
                'function'          => 'calcTotal',
                'store'             => true
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Final tax-included price of the line (computed).',
                'function'          => 'calcPrice',
                'store'             => true
            ],

            'downpayment_invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\Invoice',
                'description'       => 'Downpayment invoice (set when the line refers to an invoiced downpayment.)'
            ]
        ];
    }


    public static function calcName($self) {
        $result = [];
        $self->read(['product_id' => ['name']]);
        foreach($self as $id => $line) {
            $result[$id] = $line['product_id']['name'];
        }
        return $result;
    }

    public static function calcUnitPrice($self) {
        $result = [];
        $self->read(['price_id' => ['price']]);
        foreach($self as $id => $line) {
            $result[$id] = $line['price_id']['price'];
        }
        return $result;
    }

    public static function calcVatRate($self): array {
        $result = [];
        $self->read(['price_id' => ['vat_rate']]);
        foreach($self as $id => $line) {
            $result[$id] = 0.0;
            if(isset($line['price_id']['vat_rate'])) {
                $result[$id] = floatval($line['price_id']['vat_rate']);
            }
        }

        return $result;
    }

    public static function calcTotal($self) {
        $result = [];
        $self->read(['qty','unit_price','free_qty','discount']);
        foreach($self as $id => $line) {
            $result[$id] = $line['unit_price'] * (1.0 - $line['discount']) * ($line['qty'] - $line['free_qty']);
        }
        return $result;
    }

    public static function calcPrice($self) {
        $result = [];
        $self->read(['total','vat_rate']);
        foreach($self as $id => $line) {
            $total = (float) $line['total'];
            $vat = (float) $line['vat_rate'];
            $result[$id] = round($total * (1.0 + $vat), 2);
        }
        return $result;
    }



    public static function onupdatePriceId($self) {
        $self->do('reset_prices');
    }

    public static function onupdateInvoiceId($self) {
        $self->do('reset_invoice_prices');
    }

    public static function onupdateVatRate($self) {
        $self->do('reset_prices');
    }

    public static function onupdateQty($self) {
        $self->do('reset_prices');
    }

    public static function onupdateFreeQty($self) {
        $self->do('reset_prices');
    }

    public static function onupdateDiscount($self) {
        $self->do('reset_prices');
    }

    public static function getActions() {
        return [
            'reset_prices' => [
                'description'   => 'Resets price and total computed fields of the invoice line and the invoice.',
                'policies'      => [],
                'function'      => 'doResetPrices'
            ],
            'reset_invoice_prices' => [
                'description'   => 'Resets price and total computed fields of the invoice.',
                'policies'      => [],
                'function'      => 'doResetInvoicePrices'
            ]
        ];
    }

    public static function doResetPrices($self) {
        $self->update([
            'price' => null,
            'total' => null
        ]);

        $self->do('reset_invoice_prices');
    }

    public static function doResetInvoicePrices($self) {
        $self->read(['invoice_id']);

        Invoice::ids(array_column($self->get(true), 'invoice_id'))
            ->update([
                'price' => null,
                'total' => null
            ]);
    }

}