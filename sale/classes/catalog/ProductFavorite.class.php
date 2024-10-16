<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\catalog;

use equal\orm\Model;

class ProductFavorite extends Model {

    public static function getName() {
        return "Product favorite";
    }

    public static function getDescription() {
        return "Product favorites allow to highlight some specific products (most used or most relevant) in the lists presented to user.";
    }

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => "The full name of the product (label + sku).",
                'store'             => true,
                'function'          => 'calcName'
            ],

            'order' => [
                'type'              => 'integer',
                'description'       => "Arbitrary value for ordering the favorites.",
                'default'           => 1
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => "Targeted product.",
                'required'          => true,
                'dependents'        => ['name']
            ],

            'center_office_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\CenterOffice',
                'description'       => "Center Office the favorite belongs to."
            ]

        ];
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['product_id' => ['name']]);
        foreach($self as $id => $product_favorite) {
            $result[$id] = $product_favorite['product_id']['name'];
        }

        return $result;
    }
}
