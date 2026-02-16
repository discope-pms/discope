<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace sale\booking;

use equal\orm\Model;

class BookingLineGroupAttribute extends Model {
    public static function getName() {
        return "Group attribute";
    }

    public static function getDescription() {
        return "An attribute to set on a group to add details.";
    }

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => "Name of the attribute.",
                'required'          => true
            ],

            'code' => [
                'type'              => 'string',
                'description'       => "Code to identify the attribute.",
                'required'          => true,
                'unique'            => true
            ],

            'booking_lines_groups_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'sale\booking\BookingLineGroup',
                'foreign_field'     => 'booking_line_group_attributes_ids',
                'rel_table'         => 'sale_booking_line_group_rel_sale_booking_line_group_attribute',
                'rel_local_key'     => 'booking_line_group_attribute_id',
                'rel_foreign_key'   => 'booking_line_group_id',
                'description'       => "Booking groups that are flagged with the attribute."
            ]

        ];
    }
}
