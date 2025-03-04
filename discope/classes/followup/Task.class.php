<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2025
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace discope\followup;

use equal\orm\Model;
use identity\User;

class Task extends Model {

    public static function getDescription(): string {
        return "Task that must be realized.";
    }

    public static function getColumns(): array {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => "Name of the task.",
                'required'          => true
            ],

            'is_done' => [
                'type'              => 'boolean',
                'description'       => "Whether the task is done.",
                'default'           => false,
                'onupdate'          => 'onupdateIsDone'
            ],

            'done_by' => [
                'type'              => 'many2one',
                'foreign_object'    => 'core\User',
                'description'       => "The user who completed the task."
            ],

            'done_date' => [
                'type'              => 'date',
                'description'       => "Date on which the task was completed."
            ],

            'visible_date' => [
                'type'              => 'date',
                'description'       => "Date on which the task must be visible.",
                'default'           => function() { return strtotime('Today'); }
            ],

            'deadline_date' => [
                'type'              => 'date',
                'description'       => "Date on which the task as to be completed."
            ],

            'task_model_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'discope\followup\TaskModel',
                'description'       => "The model used to create the task."
            ],

            'notes' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => "Notes about the task."
            ],

            'entity' => [
                'type'              => 'string',
                'description'       => "Namespace of the concerned entity.",
                'required'          => true
            ],

            'entity_id' => [
                'type'              => 'integer',
                'description'       => "Id of the associated entity.",
                'required'          => true
            ]

        ];
    }

    public static function onchange($event, $values): array {
        $result = [];
        if(isset($event['is_done'])) {
            if($event['is_done']) {
                $result['done_date'] = $values['done_date'] ?? strtotime('Today');

                /** @var \equal\auth\AuthenticationManager $auth */
                ['auth' => $auth] = \eQual::inject(['auth']);
                $user_id = $auth->userId();

                if($user_id) {
                    $user = User::id($user_id)
                        ->read(['id', 'name'])
                        ->first(true);

                    $result['done_by'] = $user ?? null;
                }
                else {
                    $result['done_date'] = null;
                    $result['done_by'] = null;
                }
            }
            else {
                $result['done_date'] = null;
                $result['done_by'] = null;
            }
        }

        return $result;
    }

    public static function onupdateIsDone($self) {
        /** @var \equal\auth\AuthenticationManager $auth */
        ['auth' => $auth] = \eQual::inject(['auth']);
        $user_id = $auth->userId();

        $self->read(['is_done', 'done_by', 'done_date']);
        foreach($self as $id => $task) {
            $done_data = [];
            if($task['is_done']) {
                if(is_null($task['done_by'])) {
                    $done_data['done_by'] = $user_id;
                }
                if(is_null($task['done_date'])) {
                    $done_data['done_date'] = strtotime('Today');
                }
            }
            else {
                if(!is_null($task['done_by'])) {
                    $done_data['done_by'] = null;
                }
                if(!is_null($task['done_date'])) {
                    $done_data['done_date'] = null;
                }
            }

            if(!empty($done_data)) {
                self::id($id)->update($done_data);
            }
        }
    }
}
