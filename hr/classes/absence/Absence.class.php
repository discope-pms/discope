<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2024
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace hr\absence;

use core\setting\Setting;
use equal\orm\Model;

class Absence extends Model {

    public static function getName() {
        return 'Absence';
    }

    public static function getDescription() {
        return "An absence code allows to identify the reason of an absence.";
    }

    public static function getColumns() {
        return [

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'requested',
                    'planned',
                    'approved',
                    'refused'
                ],
                'default'           => 'requested'
            ],

            'organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'description'       => "The organisation which the targeted identity is a partner of.",
                'default'           => 1
            ],

            'employee_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'hr\employee\Employee',
                'description'       => "The employee the absence relates to.",
                'required'          => true
            ],

            'code_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'hr\absence\AbsenceCode',
                'description'       => "Absence code (reason).",
                'required'          => true
            ],

            'date' => [
                'type'              => 'date',
                'description'       => "Date at which the absence is planned.",
                'required'          => true
            ],

            'day_part' => [
                'type'              => 'string',
                'selection'         => [
                    'forenoon',
                    'afternoon',
                    'fullday'
                ],
                'required'          => true
            ],

            'measure_unit' => [
                'type'              => 'string',
                'selection'         => [
                    'fullday',
                    'hours'
                ],
                'description'       => "The units in which the quantity is expressed.",
                'default'           => 'hours',
                'dependents'        => ['duration']
            ],

            'qty' => [
                'type'              => 'float',
                'usage'             => 'number/real:2',
                'description'       => "Amount of units expressed in measure_unit.",
                'required'          => true,
                'dependents'        => ['duration']
            ],

            'duration' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'number/real:3',
                'description'       => "Duration in hours (computed).",
                'store'             => true,
                'instant'           => true,
                'function'          => 'calcDuration'
            ]

        ];
    }

    public static function calcDuration($self) {
        $result = [];
        $self->read(['measure_unit', 'qty']);
        foreach($self as $id => $absence) {
            $hours_per_day = Setting::get_value('hr', 'locale', 'daily_work_hours');
            $result[$id] =  ($absence['measure_unit'] == 'fullday') ? $absence['qty'] * $hours_per_day : $absence['qty'];
        }

        return $result;
    }

    public function getUnique() {
        return [
            ['employee_id', 'date', 'day_part', 'code_id']
        ];
    }
}
