<?php
/*
    This file is part of the eQual framework <http://www.github.com/equalframework/equal>
    Some Rights Reserved, eQual framework, 2010-2026
    Original author(s): Cédric FRANCOYS
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/
namespace core\setting;

use equal\orm\Model;

class SettingValue extends Model {

    public static function getName() {
        return 'Configuration value';
    }

    public static function getDescription() {
        return "Configuration parameters are either specific to a user or global (user_id = 0).";
    }

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => "Code to serve as reference (might not be unique).",
                'relation'          => ['setting_id' => 'name'],
                'store'             => true,
                'readonly'          => true,
                'instant'           => true
            ],

            'is_deprecated' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'Marks the value as belonging to a deprecated setting.',
                'relation'          => ['setting_id' => 'is_deprecated'],
                'store'             => true,
                'readonly'          => true,
                'instant'           => true
            ],

            'setting_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'core\setting\Setting',
                'description'       => 'Setting the value relates to.',
                'ondelete'          => 'cascade',
                'required'          => true,
                'dependents'        => ['name', 'is_deprecated']
            ],

            'user_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'core\User',
                'description'       => 'User the setting is specific to (optional).',
                'ondelete'          => 'cascade'
            ],

            'organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'description'       => 'Organisation the setting is specific to (optional).',
                'ondelete'          => 'cascade'
            ],

            'center_office_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\CenterOffice',
                'description'       => 'Center office the setting is specific to (optional).',
                'ondelete'          => 'cascade'
            ],

            'center_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Center',
                'description'       => "Center the setting is specific to (optional).",
                'ondelete'          => 'cascade'
            ],

            'value' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'Value of the parameter (JSON is expected).',
                'help'              => 'For settings not having the is_multilang field set, translations are ignored.',
                'multilang'         => true
            ]

        ];
    }

    public function getUniques(): array {
        return [
            ['setting_id', 'user_id', 'organisation_id', 'center_office_id', 'center_id']
        ];
    }
}
