<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace sale\camp;

use equal\orm\Model;

class Enrollment extends Model {

    public static function getDescription(): string {
        return "The enrollment of a child to a camp group.";
    }

    public static function getColumns(): array {
        return [

            'camp_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'Camp',
                'description'       => "The camp the child is enrolled to.",
                'required'          => true,
                'ondelete'          => 'cascade',
                'dependents'        => ['date_from', 'date_to']
            ],

            'date_from' => [
                'type'              => 'computed',
                'result_type'       => 'date',
                'description'       => "Start date of the camp.",
                'store'             => true,
                'relation'          => ['camp_id' => 'date_from']
            ],

            'date_to' => [
                'type'              => 'computed',
                'result_type'       => 'date',
                'description'       => "End date of the camp.",
                'store'             => true,
                'relation'          => ['camp_id' => 'date_to']
            ],

            'center_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'identity\Center',
                'description'       => "The center to which the enrollment relates to.",
                'store'             => true,
                'instant'           => true,
                'relation'          => ['camp_id' => 'center_id']
            ],

            'center_office_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'identity\CenterOffice',
                'description'       => "Office the enrollment relates to (for center management).",
                'store'             => true,
                'instant'           => true,
                'relation'          => ['center_id' => 'center_office_id']
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'pending',
                    'waitlisted',
                    'confirmed',
                    'validated',
                    'cancelled'
                ],
                'description'       => "The status of the enrollment.",
                'default'           => 'pending'
            ],

            'fundings_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\pay\Funding',
                'foreign_field'     => 'enrollment_id',
                'description'       => 'Fundings that relate to the enrollment.',
                'ondetach'          => 'delete'
            ],

            'tasks_ids' => [
                'type'              => 'one2many',
                'foreign_field'     => 'enrollment_id',
                'foreign_object'    => 'sale\camp\followup\Task',
                'description'       => "Follow up tasks that are associated with the enrollment."
            ]

        ];
    }
}
