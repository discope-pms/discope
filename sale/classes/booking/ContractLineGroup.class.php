<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;

class ContractLineGroup extends \sale\contract\ContractLineGroup {

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'string',
                'description'       => 'The display name of the contract.'
            ],

            'is_pack' => [
                'type'              => 'boolean',
                'description'       => 'Does the line relates to a pack?',
                'default'           => false
            ],

            /* if group relates to a fixed pack, there is an additional line for holding the price details */
            'contract_line_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\ContractLine',
                'description'       => 'Contract line that describes the pack.',
                'visible'           => ['is_pack', '=', true]
            ],

            'contract_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Contract',
                'description'       => 'The contract the line relates to.',
            ],

            'contract_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\ContractLine',
                'foreign_field'     => 'contract_line_group_id',
                'description'       => 'Contract lines that belong to the contract.',
                'ondetach'          => 'delete'
            ],

            /* #memo - a booking can be split into several groups on which distinct rate classes apply, by default the rate_class of the customer is used */
            'rate_class_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\RateClass',
                'description'       => "The rate class that applies to the group (from booking).",
                'required'          => true
            ],

            'fare_benefit' => [
                'type'              => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Total amount of the fare benefit VAT incl.',
                'default'           => 0.0
            ],

            'total' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Total tax-excluded price for all lines (computed).',
                'function'          => 'calcTotal',
                'store'             => true
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'description'       => 'Final tax-included price for all lines (computed).',
                'function'          => 'calcPrice',
                'store'             => true
            ]

        ];
    }

    /**
     * Compute the VAT incl. total price of the group (pack), with manual and automated discounts applied.
     */
    public static function calcPrice($om, $oids, $lang) {
        $result = [];

        $groups = $om->read(__CLASS__, $oids, ['contract_lines_ids', 'total', 'is_pack', 'contract_line_id.vat_rate']);

        if($groups > 0 && count($groups)) {
            foreach($groups as $gid => $group) {
                $result[$gid] = 0.0;

                // if the group relates to a pack and the product_model targeted by the pack has its own Price, then this is the one to return
                if($group['is_pack'] ) {
                    $result[$gid] = round($group['total'] * (1 + $group['contract_line_id.vat_rate']), 2);
                }
                // otherwise, price is the sum of bookingLines prices
                else {
                    $lines = $om->read(\sale\booking\ContractLine::getType(), $group['contract_lines_ids'], ['price']);
                    if($lines > 0 && count($lines)) {
                        foreach($lines as $line) {
                            $result[$gid] += $line['price'];
                        }
                        $result[$gid] = round($result[$gid], 2);
                    }
                }
            }
        }
        return $result;
    }

    public static function calcTotal($om, $oids, $lang) {
        $result = [];
        $groups = $om->read(__CLASS__, $oids, ['contract_id', 'contract_lines_ids', 'is_pack', 'contract_line_id.unit_price', 'contract_line_id.qty']);

        if($groups > 0 && count($groups)) {
            foreach($groups as $gid => $group) {
                $result[$gid] = 0.0;

                // if the group relates to a pack and the product_model targeted by the pack has its own Price, then this is the one to return
                if($group['is_pack']) {
                    $result[$gid] = $group['contract_line_id.unit_price'] * $group['contract_line_id.qty'];
                }
                // otherwise, price is the sum of contractLines totals
                else {
                    $lines = $om->read(\sale\booking\ContractLine::getType(), $group['contract_lines_ids'], ['total']);
                    if($lines > 0 && count($lines)) {
                        foreach($lines as $line) {
                            $result[$gid] += $line['total'];
                        }
                        $result[$gid] = $result[$gid];
                    }
                }
            }
        }

        return $result;
    }
}
