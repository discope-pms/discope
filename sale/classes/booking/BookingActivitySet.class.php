<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace sale\booking;

use equal\orm\Model;

class BookingActivitySet extends Model {

    public static function getDescription(): string {
        return "Generates activities.";
    }

    public static function getColumns(): array {
        return [

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => "The product the activity set relates to.",
                'domain'            => [
                    ['is_activity', '=', true],
                    ['can_sell', '=', true]
                ]
            ],

            'date_from' => [
                'type'              => 'date',
                'description'       => "Starting date of the set.",
                'default'           => time(),
                'onupdate'          => 'onupdateDateFrom'
            ],

            'date_to' => [
                'type'              => 'date',
                'description'       => "Ending date of the set.",
                'default'           => time(),
                'onupdate'          => 'onupdateDateTo'
            ],

            'day_of_week' => [
                'type'              => 'string',
                'description'       => "Day of the week on which the activity must be generated.",
                'selection'         => [
                    'all',
                    'monday',
                    'tuesday',
                    'wednesday',
                    'thursday',
                    'friday',
                    'saturday',
                    'sunday'
                ],
                'default'           => 'all'
            ],

            'time_slot_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\TimeSlot',
                'description'       => "Specific day time slot on which the service is delivered.",
                'domain'            => ['code', 'in', ['AM', 'PM', 'EV']],
                'required'          => true
            ],

            'schedule_from' => [
                'type'              => 'computed',
                'result_type'       => 'time',
                'description'       => "Time at which the activity starts (included).",
                'store'             => true,
                'relation'          => ['time_slot_id' => 'schedule_from']
            ],

            'schedule_to' => [
                'type'              => 'computed',
                'result_type'       => 'time',
                'description'       => "Time at which the activity ends (excluded).",
                'store'             => true,
                'relation'          => ['time_slot_id' => 'schedule_to']
            ],

            'booking_activities_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\BookingActivity',
                'foreign_field'     => 'booking_activity_set_id',
                'description'       => "Activities generated from this set."
            ]

        ];
    }

    public static function onchange($event, $values): array {
        $result = [];
        if(isset($event['time_slot_id'])) {
            $time_slot = TimeSlot::id($event['time_slot_id'])
                ->read(['schedule_from', 'schedule_to'])
                ->first();

            if(!is_null($time_slot)) {
                $result['schedule_from'] = $time_slot['schedule_from'];
                $result['schedule_to'] = $time_slot['schedule_to'];
            }
        }

        return $result;
    }

    public static function getActions(): array {
        return [
            'generate-activities' => [
                'description'   => "Generate booking activities from set.",
                'policies'      => [],
                'function'      => 'doGenerateBookingActivities'
            ]
        ];
    }

    public static function doGenerateBookingActivities($self) {
        $self->read([
            'product_id',
            'date_from',
            'date_to',
            'day_of_week',
            'time_slot_id',
            'schedule_from',
            'schedule_to',
            'booking_activities_ids' => [
                'activity_date',
                'time_slot_id'
            ]
        ]);
        foreach($self as $id => $activity_set) {
            $date = $activity_set['date_from'];
            while($date <= $activity_set['date_to']) {
                if($activity_set['day_of_week'] !== 'all' && strtolower(date('l', $date)) !== $activity_set['day_of_week']) {
                    $date += 86400;
                    continue;
                }

                $activity_already_exists = false;
                foreach($activity_set['booking_activities_ids'] as $booking_activity) {
                    if($booking_activity['activity_date'] === $date && $booking_activity['time_slot_id'] === $activity_set['time_slot_id']) {
                        $activity_already_exists = true;
                    }
                }

                if(!$activity_already_exists) {
                    BookingActivity::create([
                        'product_id'                => $activity_set['product_id'],
                        'activity_date'             => $date,
                        'time_slot_id'              => $activity_set['time_slot_id'],
                        'schedule_from'             => $activity_set['schedule_from'],
                        'schedule_to'               => $activity_set['schedule_to'],
                        'booking_activity_set_id'   => $id
                    ]);
                }

                $date += 86400;
            }
        }
    }

    public static function onupdateDateFrom($self) {
        $self->read(['state']);
        foreach($self as $id => $activity) {
            if($activity['state'] !== 'draft') {
                BookingActivitySet::id($id)->do('generate-activities');
            }
        }
    }

    public static function onupdateDateTo($self) {
        $self->read(['state']);
        foreach($self as $id => $activity) {
            if($activity['state'] !== 'draft') {
                BookingActivitySet::id($id)->do('generate-activities');
            }
        }
    }
}
