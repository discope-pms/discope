<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2024
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace discope\core\alert;

class Message extends \core\alert\Message {

    public static function getColumns() {
        return [

            'center_office_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'identity\CenterOffice',
                'description'       => "Office the message relates to (for targeting the users).",
                'store'             => true,
                'function'          => 'calcCenterOfficeId'
            ],

            'alert' => [
                'type'              => 'computed',
                'usage'             => 'icon',
                'result_type'       => 'string',
                'description'       => "Icon name of the message that depends on its severity.",
                'function'          => 'calcAlert'
            ]

        ];
    }

    /**
     * We hijack the group_id to target the Center Offices.
     */
    public static function calcCenterOfficeId($self) {
        $result = [];
        $self->read(['group_id']);
        foreach($self as $id => $message) {
            $result[$id] = $message['group_id'];
        }

        return $result;
    }

    public static function calcAlert($self) {
        $result = [];
        $self->read(['severity']);
        foreach($self as $id => $message) {
            switch($message['severity']) {
                case 'notice':
                    $result[$id] = 'info';
                    break;
                case 'warning':
                    $result[$id] = 'warn';
                    break;
                case 'important':
                    $result[$id] = 'major';
                    break;
                case 'error':
                default:
                    $result[$id] = 'error';
                    break;
            }
        }

        return $result;
    }
}
