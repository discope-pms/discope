<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace realestate;

use equal\orm\Model;

class RentalUnitCategory extends Model {

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => "Short code of the rental unit category.",
                'function'          => 'calcName',
                'store'             => true
            ],

            'code' => [
                'type'              => 'string',
                'description'       => "Short code of the rental unit category.",
                'dependents'        => ['name']
            ],

            'description' => [
                'type'              => 'string',
                'description'       => "Reason of the categorization of rental units.",
                'multilang'         => true,
                'dependents'        => ['name']
            ],

            'rental_units_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'realestate\RentalUnit',
                'foreign_field'     => 'rental_unit_category_id',
                'description'       => 'The rental units that are assigned to the category.'
            ]

        ];
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['code', 'description']);
        foreach($self as $id => $category) {
            $result[$id] = "{$category['code']} - {$category['description']}";
        }

        return $result;
    }
}
