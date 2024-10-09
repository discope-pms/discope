<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2024
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace discope\communication;

class Template extends \communication\Template {

    public static function getColumns() {
        return [

            'category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'discope\communication\TemplateCategory',
                'description'       => "The category the template belongs to.",
                'onupdate'          => 'onupdateCategoryId',
                'required'          => true
            ],

            'parts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'discope\communication\TemplatePart',
                'foreign_field'     => 'template_id',
                'description'       => "List of templates parts related to the template."
            ],

            'attachments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'discope\communication\TemplateAttachment',
                'foreign_field'     => 'template_id',
                'description'       => "List of attachments related to the template, if any."
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => [ 'quote', 'option', 'contract', 'funding', 'invoice', 'guest' ],
                'description'       => "The context in which the template is meant to be used.",
                'onupdate'          => 'onupdateType'
            ]

        ];
    }
}
