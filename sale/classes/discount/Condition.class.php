<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2024
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\discount;
use equal\orm\Model;

class Condition extends Model {

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'sale\discount\Condition::getDisplayName',
                'store'             => true,
                'description'       => 'Resulting display name of the condition.'
            ],

            'operand' => [
                'type'              => 'string',
                'selection'         => [
                    'season',
                    'nb_pers',
                    'nb_children',
                    'nb_adults',
                    'duration',
                    'count_booking_24'
                ],
                'required'          => true
            ],

            'operator' => [
                'type'              => 'string',
                'selection'         => ['=', '>', '>=', '<', '<='],
                'required'          => true
            ],

            'value' => [
                'type'              => 'string',
                'required'          => true
            ],

            'discount_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\discount\Discount',
                'description'       => 'The discount list the discount belongs to.',
                'required'          => true
            ],


        ];
    }

    public static function getDisplayName($self) {
        $result = [];
        $self->read(['operand', 'operator', 'value']);
        foreach($self as $id => $condition) {
            $result[$id] = "{$condition['operand']} {$condition['operator']} {$condition['value']}";
        }
        return $result;
    }


}