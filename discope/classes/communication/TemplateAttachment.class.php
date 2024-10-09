<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace discope\communication;

class TemplateAttachment extends \communication\TemplateAttachment {

    public static function getColumns() {
        return [

            'template_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'discope\communication\Template',
                'description'       => "The template the attachment belongs to.",
                'required'          => true
            ],

            // #todo - use 2 fields : has_booking_type (bool) + booking_type_id (many2one)
            'attachment_type' => [
                'type'              => 'string',
                'selection'         => [
                    'all',
                    'schools',
                    'individuals',
                    'groups'
                ]
            ]

        ];
    }
}
