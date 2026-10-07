<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace hr\absence;

class AbsenceCode extends \equal\orm\Model {

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
                'function'          => 'calcName',
                'description'       => 'Label describing the code.',
                'multilang'         => true,
                'store'             => true
            ],

            'code' => [
                'type'              => 'string',
                'description'       => 'Code (based on local legislation).',
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Description of the absence code (reason).',
                'multilang'         => true
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

}
