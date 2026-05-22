<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace sale\camp;

use equal\orm\Model;

class Camp extends Model {

    public static function getDescription(): string {
        return "A camp for which children are hosted and some activities are organized.";
    }

    public static function getColumns(): array {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => "Name of the camp."
            ],

            'short_name' => [
                'type'              => 'string',
                'description'       => "Short name of the camp."
            ],

            'sojourn_number' => [
                'type'              => 'string',
                'description'       => "Sojourn number to distinguish camps.",
                'help'              => "Is handle by the setting sequence.",
                'required'          => true
            ],

            'center_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Center',
                'description'       => "The center to which the camp relates to.",
                'default'           => 1
            ],

            'center_office_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'identity\CenterOffice',
                'description'       => "Office the camp relates to (for center management).",
                'store'             => true,
                'relation'          => ['center_id' => 'center_office_id']
            ],

            'date_from' => [
                'type'              => 'date',
                'description'       => "When the camp starts.",
                'required'          => true,
                'dependents'        => ['name', 'enrollments_ids' => ['date_from']],
                'default'           => function() {
                    return strtotime('next Sunday');
                }
            ],

            'date_to' => [
                'type'              => 'date',
                'description'       => "When the camp ends.",
                'required'          => true,
                'dependents'        => ['name', 'enrollments_ids' => ['date_to']],
                'default'           => function() {
                    return strtotime('next Sunday +5 days');
                }
            ],

            'min_age' => [
                'type'              => 'integer',
                'description'       => "Minimal age of the participants.",
                'default'           => 5
            ],

            'max_age' => [
                'type'              => 'integer',
                'description'       => "Maximal age of the participants.",
                'default'           => 10
            ],

            'enrollments_qty' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'description'       => "Quantity of enrollments that aren't cancelled or waitlisted.",
                'store'             => true,
                'function'          => 'calcEnrollmentsQty'
            ],

            'camp_model_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'CampModel',
                'description'       => "Model that was used as a base to create this camp.",
                'onupdate'          => 'onupdateCampModelId',
                'required'          => true
            ],

            'camp_groups_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'CampGroup',
                'foreign_field'     => 'camp_id',
                'description'       => "The groups of children of the camp.",
                'ondetach'          => 'delete'
            ],

            'enrollments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'Enrollment',
                'foreign_field'     => 'camp_id',
                'description'       => "All the enrollments linked to camp.",
                'ondetach'          => 'delete'
            ],

            'booking_activities_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\BookingActivity',
                'foreign_field'     => 'camp_id',
                'description'       => "All Booking Activities this camp relates to.",
                'ondetach'          => 'delete'
            ],

            'booking_meals_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\BookingMeal',
                'foreign_field'     => 'camp_id',
                'description'       => "The children's meals for this camp.",
                'ondetach'          => 'delete'
            ]

        ];
    }

    public static function calcEnrollmentsQty($self): array {
        $result = [];
        $self->read(['enrollments_ids' => ['status']]);
        foreach($self as $id => $camp) {
            $enrollment_qty = 0;
            foreach($camp['enrollments_ids'] as $enrollment) {
                if(in_array($enrollment['status'], ['pending', 'confirmed', 'validated'])) {
                    $enrollment_qty++;
                }
            }
            $result[$id] = $enrollment_qty;
        }

        return $result;
    }
}
