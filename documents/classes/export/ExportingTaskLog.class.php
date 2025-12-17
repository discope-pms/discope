<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace documents\export;

use equal\orm\Model;

class ExportingTaskLog extends Model {

    public static function getDescription(): string {
        return "A TaskLog is an entry that relates to a task. One TaskLog is created after each execution of a task.";
    }

    public static function getColumns(): array {
        return [

            'task_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'documents\export\ExportingTask',
                'description'       => "The Task the log entry refers to.",
                'required'          => true
            ],

            'task_line_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'documents\export\ExportingTaskLine',
                'description'       => "The Task line the log entry refers to.",
                'required'          => true
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'success',
                    'error'
                ],
                'description'       => "Status depending on the Task execution outcome."
            ],

            'log' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => "The value returned at the execution of the controller targeted by the Task."
            ]

        ];
    }
}
