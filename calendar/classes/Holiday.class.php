<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace calendar;

use equal\orm\Model;

class Holiday extends Model {

    public static function getName() {
        return "Ephemeris entry";
    }

    public static function getDescription() {
        return "Holidays allow to list public holidays and school vacations occuring within a given year.";
    }

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => "Reason of the holiday (ephemeris)."
            ],

            'date_from' => [
                'type'              => 'date',
                'description'       => "Date/first day of the holiday.",
                'onupdate'          => 'onupdateDateFrom'
            ],

            'is_single_day' => [
                'type'              => 'boolean',
                'description'       => "Is the holiday a single date or does it span on several days?",
                'default'           => true
            ],

            'date_to' => [
                'type'              => 'date',
                'description'       => "Last date of the holiday.",
                'visible'           => ['is_single_day', '=', false]
            ],

            'year' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'usage'             => 'date/year:4',
                'description'       => "Year on which the holiday applies (based first date).",
                'store'             => true,
                'function'          => 'calcYear'
            ],

            'holiday_year_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'calendar\HolidayYear',
                'description'       => "The Year the holiday belongs to.",
                'required'          => true,
                'dependents'        => ['year']
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => [
                    'school_vacation',
                    'public_holiday'
                ]
            ]

        ];
    }

    public static function calcYear($self) {
        $result = [];
        $self->read(['holiday_year_id' => ['year']]);
        foreach($self as $id => $holiday) {
            $result[$id] = $holiday['holiday_year_id']['year'];
        }
        return $result;
    }

    public static function onupdateDateFrom($self) {
        $self->read(['date_from', 'is_single_day']);
        foreach($self as $id => $holiday) {
            $fields = [
                'year' => date('Y', $holiday['date_from'])
            ];
            if($holiday['is_single_day']) {
                $fields['date_to'] = $holiday['date_from'];
            }

            Holiday::id($id)->update($fields);
        }
    }
}
