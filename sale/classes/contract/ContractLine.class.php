<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace sale\contract;

use equal\orm\Model;

class ContractLine extends Model {

    public static function getName() {
        return "Contract line";
    }

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => "The display name of the line.",
                'store'             => true,
                'function'          => 'calcName'
            ],

            'description' => [
                'type'              => 'string',
                'description'       => "Complementary description of the line. If set, replaces the product name.",
                'default'           => ''
            ],

            'contract_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\contract\Contract',
                'description'       => "The contract the line relates to."
            ],

            'contract_line_group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\contract\ContractLineGroup',
                'description'       => "The contract the line relates to."
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => "The product (SKU) the line relates to.",
                'required'          => true
            ],

            'price_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\price\Price',
                'description'       => "The price the line relates to, if any."
            ],

            'unit_price' => [
                'type'              => 'float',
                'usage'             => 'amount/money:4',
                'description'       => "Tax-excluded price of the product related to the line.",
                'required'          => true
            ],

            'vat_rate' => [
                'type'              => 'float',
                'description'       => "VAT rate to be applied.",
                'required'          => true
            ],

            'qty' => [
                'type'              => 'float',
                'description'       => "Quantity of product.",
                'required'          => true
            ],

            'free_qty' => [
                'type'              => 'integer',
                'description'       => "Free quantity.",
                'default'           => 0
            ],

            'total_no_discount' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => "Total tax-excluded price of the line without the discount applied.",
                'store'             => false,
                'function'          => 'calcTotalNoDiscount'
            ],

            // #memo - important: to allow the maximum flexibility, percent values can hold 4 decimal digits (must not be rounded, except for display)
            'discount' => [
                'type'              => 'float',
                'usage'             => 'amount/rate',
                'description'       => 'Total amount of discount to apply, if any.',
                'default'           => 0.0
            ],

            'total' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => "Total tax-excluded price of the line.",
                'store'             => true,
                'function'          => 'calcTotal'
            ],

            'total_vat' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => "Total tax price of the line.",
                'help'              => "Must have 4 decimals allowed because it is used to compute subtotals_vat of Contract.",
                'store'             => false,
                'function'          => 'calcTotalVat'
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Final tax-included price of the line.',
                'store'             => true,
                'function'          => 'calcPrice'
            ]

        ];
    }

    public static function calcName($self): array {
        $result = [];
        $self->read(['product_id' => ['label']]);
        foreach($self as $id => $line) {
            $result[$id] = $line['product_id']['label'];
        }

        return $result;
    }

    /**
     * Get total tax-excluded price of the line before the discount is applied.
     */
    public static function calcTotalNoDiscount($self): array {
        $result = [];
        $self->read(['qty', 'free_qty', 'unit_price']);
        foreach($self as $id => $line) {
            // #memo - total_no_discount of a line must be rounded to 2 decimals
            $result[$id] = round(($line['qty'] - $line['free_qty']) * $line['unit_price'], 2);
        }

        return $result;
    }

    /**
     * Get total tax-excluded price of the line.
     */
    public static function calcTotal($self): array {
        $result = [];
        $self->read(['total_no_discount', 'discount']);
        foreach($self as $id => $line) {
            // #memo - total of a line must be rounded to 2 decimals
            $result[$id] = round($line['total_no_discount'] * (1.0 - $line['discount']), 2);
        }

        return $result;
    }

    /**
     * Get tax amount of the line.
     */
    public static function calcTotalVat($self): array {
        $result = [];
        $self->read(['total', 'vat_rate']);
        foreach($self as $id => $line) {
            if($line['vat_rate'] === 0.0) {
                $result[$id] = 0.0;
            }
            else {
                // #memo - total_vat must be computed using a precision of 4 decimals, it is rounded to 2 decimals at Invoice level for subtotals_vat
                $result[$id] = round(round($line['total'], 2) * $line['vat_rate'], 4);
            }
        }

        return $result;
    }

    /**
     * Get final tax-included price of the line.
     */
    public static function calcPrice($self): array {
        $result = [];
        $self->read(['total', 'total_vat']);
        foreach($self as $id => $line) {
            $result[$id] = round($line['total'] + $line['total_vat'], 4);
        }

        return $result;
    }
}
