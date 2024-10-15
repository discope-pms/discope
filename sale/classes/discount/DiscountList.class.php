<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2024
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\discount;
use equal\orm\Model;

class DiscountList extends Model {

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Short memo for the list (ex. discounts 2025).",
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => "Short description of the list.",
            ],

            'valid_from' => [
                'type'              => 'date',
                'description'       => "Date from which the list is valid (included).",
                'default'           => time()
            ],

            'valid_until' => [
                'type'              => 'date',
                'description'       => "Moment until when the list is valid (included).",
                'default'           => time()
            ],

            'discounts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\discount\Discount',
                'foreign_field'     => 'discount_list_id',
                'description'       => 'The discounts that are part of the list.'
            ],

            'discount_list_category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\discount\DiscountListCategory',
                'description'       => 'The category the list belongs to.',
                'required'          => true
            ],

            'rate_min' => [
                'type'              => 'float',
                'usage'             => 'amount/percent',
                'description'       => "Guaranteed minimal discount, if any.",
                'default'           => 0.0
            ],

            'rate_max' => [
                'type'              => 'float',
                'usage'             => 'amount/percent',
                'description'       => "Maximal applicable discount, if any.",
                'default'           => 1.0
            ],

            'rate_class_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\RateClass',
                'description'       => "The rate class that applies to this class of discount.",
                'required'          => true
            ]

        ];
    }

    public static function calcRateClassId($self) {
        $result = [];
        $self->read(['discount_class_id' =>'rate_class_id']);
        foreach($self as $oid => $list) {
            $result[$oid] = $list['discount_class_id']['rate_class_id'];
        }
        return $result;
    }

}