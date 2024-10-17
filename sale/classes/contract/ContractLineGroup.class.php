<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\contract;

use equal\orm\Model;

class ContractLineGroup extends Model {

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'string',
                'description'       => "The display name of the group."
            ],

            'contract_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\contract\Contract',
                'description'       => "The contract the line relates to.",
            ],

            'contract_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\contract\ContractLine',
                'foreign_field'     => 'contract_line_group_id',
                'description'       => "Contract lines that belong to the contract.",
                'ondetach'          => 'delete'
            ],

            'rate_class_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\RateClass',
                'description'       => "The rate class that applies to the group (from booking).",
                'required'          => true
            ],

            'fare_benefit' => [
                'type'              => 'float',
                'usage'             => 'amount/money:2',
                'description'       => "Total amount of the fare banefit VAT incl.",
                'default'           => 0.0
            ],

            'is_pack' => [
                'type'              => 'boolean',
                'description'       => "Does the line relates to a pack?",
                'default'           => false
            ],

            'total' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => "Total tax-excluded price for all lines (computed).",
                'function'          => 'calcTotal',
                'store'             => true
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'description'       => "Final tax-included price for all lines (computed).",
                'function'          => 'calcPrice',
                'store'             => true
            ],

            'contract_line_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\contract\ContractLine',
                'description'       => "Contract line that describes the pack.",
                'visible'           => ['is_pack', '=', true]
            ]

        ];
    }


    public static function calcPrice($self) {
        $result = [];

        $self->read(['contract_lines_ids' => ['price'], 'contract_line_id' => ['vat_rate'], 'total', 'is_pack']);
        foreach($self as $gid => $group) {
            if ($group['is_pack']) {
                $vatRate = $group['contract_line_id']['vat_rate'] ?? 0;
                $result[$gid] = round(($group['total'] ?? 0) * (1 + $vatRate), 2);
            } else {
                $prices = array_column($group['contract_lines_ids'] ?? [], 'price');
                $result[$gid] = round(array_sum($prices), 2);
            }
        }
        return $result;
    }

    public static function calcTotal($self) {
        $result = [];
        $self->read(['contract_id', 'contract_lines_ids' => ['id', 'total'], 'is_pack', 'contract_line_id' => ['unit_price', 'qty']]);
        foreach($self as $gid => $group) {
            foreach ($self as $gid => $group) {
                $result[$gid] = $group['is_pack']
                    ? ($group['contract_line_id']['unit_price'] ?? 0) * ($group['contract_line_id']['qty'] ?? 0)
                    : array_sum(array_column($group['contract_lines_ids'] ?? [], 'total'));
            }

        }

        return $result;
    }

}