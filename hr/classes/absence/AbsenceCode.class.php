<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2024
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace hr\absence;

use equal\orm\Model;

class AbsenceCode extends Model {

    public static function getName() {
        return 'Absence code';
    }

    public static function getDescription() {
        return "An absence code allows to identify the reason of an absence.";
    }

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => "Label describing the code.",
                'store'             => true,
                'instant'           => true,
                'function'          => 'calcName',
                'readonly'          => true
            ],

            'code' => [
                'type'              => 'string',
                'description'       => "Code (based on local legislation).",
                'required'          => true,
                'dependents'        => ['name']
            ],

            'description' => [
                'type'              => 'string',
                'description'       => "Description of the absence code (reason).",
                'dependents'        => ['name']
            ]

        ];
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['code', 'description']);
        foreach($self as $id => $code) {
            $result[$id] = $code['code'].' - '.$code['description'];
        }

        return $result;
    }

    public static function onchange($event, $values) {
        $result = [];
        if(isset($event['code']) || isset($event['description'])) {
            $code = $event['code'] ?? $values['code'];
            $description = $event['description'] ?? $values['description'];
            $result['name'] = $code.' - '.$description;
        }

        return $result;
    }
}
