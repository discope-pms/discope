<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace sale\contract;

use equal\orm\Model;

class Contract extends Model {

    public static function getName() {
        return "Contract";
    }

    public static function getDescription() {
        return "Contracts are formal agreement regarding the delivery of products or services concluded between two parties.";
    }


    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => "The display name of the contract.",
                'store'             => true,
                'function'          => 'calcName'
            ],

            'description' => [
                'type'              => 'string',
                'description'       => "Short description about the reason of the contract (i.e. the object of the agreement)."
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'pending',
                    'sent',                 // sent to customer for signature
                    'signed',               // signed by customer (valid)
                    'cancelled'             // outdated or rejected
                ],
                'description'       => "Status of the contract.",
                'default'           => 'pending',
                'onupdate'          => 'onupdateStatus'
            ],

            'is_signed' => [
                'type'              => 'boolean',
                'description'       => "Was contract was signed by the customer?",
                'default'           => false
            ],

            'date' => [
                'type'              => 'date',
                'description'       => "Date at which the contract has been officially released."
            ],

            'valid_until' => [
                'type'              => 'date',
                'description'       => "Date after which the contract lapses if it has not been approved.",
                'visible'           => [ 'status', 'in', ['pending', 'sent'] ]
            ],

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => "The customer the contract relates to.",
                'domain'            => ['relationship', '=', 'customer']
            ],

            'contract_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\contract\ContractLine',
                'foreign_field'     => 'contract_id',
                'description'       => "Contract lines that belong to the contract.",
                'ondetach'          => 'delete'
            ],

            'total' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => "Total tax-excluded price of the contract (computed).",
                'store'             => true,
                'function'          => 'calcTotal'
            ],

            'subtotals' => [
                'type'              => 'computed',
                'result_type'       => 'array',
                'description'       => "Sub totals, by vat rates, tax-excluded prices of the contract.",
                'help'              => "Must sum lines prices totals keeping 4 decimals and rounded to 2 decimals at the end. e.g. '0.0', '6.0', '12.0', '21.0'.",
                'store'             => false,
                'function'          => 'calcSubTotals'
            ],

            'subtotals_vat' => [
                'type'              => 'computed',
                'result_type'       => 'array',
                'description'       => "Sub totals, by vat rates, tax prices of the contract.",
                'help'              => "Must sum lines prices totals keeping 4 decimals and rounded to 2 decimals at the end. e.g. '0.0', '6.0', '12.0', '21.0'.",
                'store'             => false,
                'function'          => 'calcSubTotalsVat'
            ],

            'total_vat' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => "Total tax price of the contract.",
                'store'             => false,
                'function'          => 'calcTotalVat'
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => "Final tax-included contract amount (computed).",
                'store'             => true,
                'function'          => 'calcPrice'
            ]

        ];
    }

    public static function calcName($self): array {
        $result = [];
        $self->read(['id', 'customer_id' => ['name']]);
        foreach($self as $id => $contract) {
            $result[$id] = "{$contract['customer_id']['name']} - {$contract[$id]}";
        }

        return $result;
    }

    public static function calcTotal($self): array {
        $result = [];
        $self->read(['contract_lines_ids' => ['total']]);
        foreach($self as $id => $contract) {
            $total = 0.0;
            foreach($contract['contract_lines_ids'] as $line) {
                $total = round($total + $line['total'], 2);
            }

            $result[$id] = $total;
        }

        return $result;
    }

    public static function calcSubTotals($self): array {
        $result = [];
        $self->read(['contract_lines_ids' => ['vat_rate', 'total']]);
        foreach($self as $id => $contract) {
            $subtotals = [];
            foreach($contract['contract_lines_ids'] as $line) {
                $vat_rate_index = number_format($line['vat_rate'] * 100, 2, '.', '');
                if(!isset($subtotals[$vat_rate_index])) {
                    $subtotals[$vat_rate_index] = 0.0;
                }

                // #memo - total is rounded to 2 decimals for compatibility with older data that were computed with 4 decimals
                $subtotals[$vat_rate_index] = round($subtotals[$vat_rate_index] + round($line['total'], 2), 2);
            }

            // #memo - as to be rounded on 2 decimals here and not on each line
            $result[$id] = array_map(fn($subtotal) => round($subtotal, 2), $subtotals);
        }

        return $result;
    }

    public static function calcSubTotalsVat($self): array {
        $result = [];
        $self->read(['subtotals']);
        foreach($self as $id => $contract) {
            $subtotals_vat = [];
            foreach($contract['subtotals'] as $vat_rate_index => $subtotal) {
                $vat_rate = ((float) $vat_rate_index) / 100;
                $subtotals_vat[$vat_rate_index] = round($subtotal * $vat_rate, 2);
            }

            $result[$id] = $subtotals_vat;
        }

        return $result;
    }

    public static function calcTotalVat($self): array {
        $result = [];
        $self->read(['subtotals_vat']);
        foreach($self as $id => $contract) {
            $total_vat = 0.0;
            foreach($contract['subtotals_vat'] as $subtotal) {
                $total_vat = round($total_vat + $subtotal, 2);
            }

            $result[$id] = $total_vat;
        }

        return $result;
    }

    public static function calcPrice($self): array {
        $result = [];
        $self->read(['total', 'total_vat']);
        foreach($self as $id => $contract) {
            $result[$id] = round($contract['total'] + $contract['total_vat'], 2);
        }

        return $result;
    }

    public static function onupdateStatus($self) {
        $self->read(['status']);
        foreach($self as $id => $contract) {
            if($contract['status'] === 'signed') {
                Contract::id($id)->update(['is_signed' => true]);
            }
        }
    }
}