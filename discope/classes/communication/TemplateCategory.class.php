<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace discope\communication;

class TemplateCategory extends \communication\TemplateCategory {

    public static function getColumns() {
        return [

            'templates_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'discope\communication\Template',
                'foreign_field'     => 'category_id',
                'description'       => "Templates that are related to this category, if any."
            ]

        ];
    }
}
