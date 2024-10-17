<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2024
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace hr\holiday;

use equal\orm\Model;

class Holiday extends Model {

    public static function getName() {
        return "Holiday";
    }

    public static function getDescription() {
        return "A holiday is a date at which employees are expected to benefit from a legal day of inactivity.";
    }

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => "Name of the holiday.",
                'multilang'         => true
            ],

            'date' => [
                'type'              => 'date',
                'description'       => "Date of the holiday.",
                'onupdate'          => 'onupdateDate',
                'dependencies'      => ['year']
            ],

            'year' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'usage'             => 'date/year:4',
                'description'       => "Year on which the holiday applies (based first date).",
                'store'             => true,
                'function'          => 'calcYear'
            ]

        ];
    }

    public static function calcYear($self) {
        $result = [];
        $self->read(['date']);
        foreach($self as $id => $holiday) {
            $result[$id] = date('Y', $holiday['date']);
        }

        return $result;
    }
}
