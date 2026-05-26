<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace sale\camp;

use equal\orm\Model;

class CampModel extends Model {

    public static function getDescription(): string {
        return "Model that acts as a creation base for new camps.";
    }

    public static function getColumns(): array {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => "Name of the camp model.",
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => "Description of the camp model.",
                'usage'             => 'text/plain'
            ],

            'employee_ratio' => [
                'type'              => 'integer',
                'usage'             => 'number/integer{1,50}',
                'description'       => "The quantity of children one employee can handle alone, max_children for one camp group.",
                'default'           => 12
            ],

            'camps_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\camp\Camp',
                'foreign_field'     => 'camp_model_id',
                'description'       => "The camps based on the model."
            ]

        ];
    }
}
