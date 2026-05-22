<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace sale\camp;

use equal\orm\Model;

class CampGroup extends Model {

    public static function getDescription(): string {
        return "Group of a camp that one employee will need to manage.";
    }

    public static function getColumns(): array {
        return [

            'camp_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'Camp',
                'description'       => "The camp this group is part of.",
                'required'          => true,
                'readonly'          => true,
                'ondelete'          => 'cascade'
            ],

            'employee_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'hr\employee\Employee',
                'description'       => "Employee responsible of the group during the camp.",
                'onupdate'          => 'onupdateEmployeeId',
                'domain'            => ['relationship', '=', 'employee']
            ],

            'max_children' => [
                'type'              => 'integer',
                'description'       => "Max quantity of children that can take part to the camp.",
                'default'           => 10
            ],

            'booking_activities_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\BookingActivity',
                'foreign_field'     => 'camp_group_id',
                'description'       => "All Booking Activities this camp group relates to.",
                'ondetach'          => 'delete'
            ],

            'partner_events_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\PartnerEvent',
                'foreign_field'     => 'camp_group_id',
                'description'       => "All Booking Activities this camp group relates to.",
                'ondetach'          => 'delete'
            ]

        ];
    }
}
