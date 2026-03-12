<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2025
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace sale\booking\followup;

class Task extends \core\followup\Task {

    public static function getDescription(): string {
        return "Booking task that has been or must be completed.";
    }

    public static function getLink(): string {
        return "/booking/#/task/object.id";
    }

    public static function getColumns(): array {
        return [

            'is_done' => [
                'type'              => 'boolean',
                'description'       => "Whether the task is done.",
                'default'           => false,
                'onupdate'          => 'onupdateIsDone'
            ],

            'done_by' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\User',
                'description'       => "The user who completed the task."
            ],

            'task_model_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\followup\TaskModel',
                'description'       => 'The model used to create the task.',
                'help'              => 'Based on model or arbitrary',
                'required'          => false
            ],

            'entity' => [
                'type'              => 'string',
                'description'       => "Namespace of the concerned entity.",
                'default'           => 'sale\booking\Booking'
            ],

            'entity_id' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'description'       => 'Id of the associated entity. In this case it is the booking id.',
                'store'             => true,
                'instant'           => true,
                'function'          => 'calcEntityId',
                'readonly'          => true
            ],

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Booking',
                'description'       => 'Booking the task relates to.',
                'readonly'          => true
            ],

            'object_class' => [
                'type'              => 'string',
                'description'       => 'Namespace of the concerned entity.',
                'required'          => false,
                'help'              => 'Overloaded to make field optional.'
            ],

            'object_id' => [
                'type'              => 'integer',
                'description'       => 'Id of the associated entity.',
                'required'          => false,
                'help'              => 'Overloaded to make field optional.'
            ]

        ];
    }

    public static function calcEntityId($self): array {
        $result = [];
        $self->read(['booking_id']);
        foreach($self as $id => $task) {
            if(isset($task['booking_id'])) {
                $result[$id] = $task['booking_id'];
            }
        }

        return $result;
    }

    public static function getConstraints(): array {
        return [

            'entity' =>  [
                'not_allowed' => [
                    'message'   => 'Entity must be "sale\booking\Booking".',
                    'function'  => function ($entity, $values) {
                        return $entity === 'sale\booking\Booking';
                    }
                ]
            ]

        ];
    }

    protected static function doCancelAlerts($self, $dispatch) {
        foreach($self as $id => $task) {
            $dispatch->cancel('sale.booking.followup.task.reminder', 'sale\booking\followup\Task', $id);
        }
    }

    public static function getActions(): array {
        return [

            'cancel_alerts' => [
                'description'   => "Cancel alerts linked to this task.",
                'policies'      => [],
                'function'      => 'doCancelAlerts'
            ]

        ];
    }

    public static function onupdateIsDone($self) {
        parent::onupdateIsDone($self);

        $self->do('cancel_alerts');
    }
}
