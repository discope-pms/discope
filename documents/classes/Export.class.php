<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace documents;

class Export extends Document {

    public function getTable(): string {
        return 'lodging_documents_export';
    }

    public static function getColumns(): array {
        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcName',
                'store'             => true
            ],

            'center_office_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\CenterOffice',
                'description'       => 'Office the invoice relates to (for center management).',
            ],

            'export_type' => [
                'type'              => 'string',
                'selection'         => [
                    'invoices',
                    'payments'
                ],
                'required'          => true,
                'readonly'          => true
            ],

            'is_exported' => [
                'type'              => 'boolean',
                'default'           => false,
                'description'       => 'Mark the archive as already exported.'
            ]

        ];
    }

    public static function calcName($self): array {
        $result = [];
        $self->read(['created', 'export_type', 'center_office_id' => ['name']]);
        foreach($self as $id => $export) {
            $result[$id] = sprintf(
                '%s - %s - %s',
                date('Ymd', $export['created']),
                $export['export_type'],
                $export['center_office_id']['name']
            );
        }

        return $result;
    }
}
