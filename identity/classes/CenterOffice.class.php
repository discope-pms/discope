<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2026
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace identity;

use discope\setting\Setting;
use equal\data\DataGenerator;

class CenterOffice extends \identity\Establishment {

    /** @deprecated */
    public function getTable() {
        return self::getModelTable();
    }

    public static function getModelTable() {
        return 'lodging_identity_centeroffice';
    }

    public static function getName() {
        return 'Center management Office';
    }

    public static function getDescription() {
        return 'Allow support for management of Centers by distinct Offices.';
    }

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => 'Name of the Office.',
                'generation'        => 'generateName'
            ],

            'code' => [
                'type'              => 'string',
                'description'       => 'Numeric identifier of group (1 hex. digit).',
                'usage'             => 'number/hexadecimal:1',
                'default'           => '1'
            ],

            'centers_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'identity\Center',
                'foreign_field'     => 'center_office_id',
                'description'       => 'List of centers attached to the office.'
            ],

            'contacts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'identity\CenterOfficeContact',
                'foreign_field'     => 'center_office_id',
                'description'       => 'List of contacts attached to the office.'
            ],

            'users_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'identity\User',
                'foreign_field'     => 'center_offices_ids',
                'rel_table'         => 'lodging_identity_rel_center_office_user',
                'rel_foreign_key'   => 'user_id',
                'rel_local_key'     => 'center_office_id'
            ],

            'signature' => [
                'type'              => 'string',
                'usage'             => 'text/html',
                'description'       => 'Office signature to append to communications.',
                'multilang'         => true
            ],

            'docs_default_mode' => [
                'type'              => 'string',
                'selection'         => [
                    'simple',
                    'grouped',
                    'detailed'
                ],
                'description'       => 'Default mode to use when rendering official documents.',
                'default'           => function() {
                    return Setting::get_value('identity', 'default', 'center_office.docs_default_mode', 'simple');
                }
            ],

            'printer_type' => [
                'type'              => 'string',
                'selection'         => [
                    'pos-80',
                    'iso-a4'
                ],
                'description'       => 'Printer format to be used for PoS tickets.',
                'default'           => function() {
                    return Setting::get_value('identity', 'default', 'center_office.printer_type', 'iso-a4');
                }
            ],

            'rentalunits_manual_assignment' => [
                'type'              => 'boolean',
                'description'       => 'Force manual assignment of rental units during booking.',
                'default'           => function() {
                    return Setting::get_value('identity', 'default', 'center_office.rentalunits_manual_assignment', false);
                }
            ],

            'freebies_manual_assignment' => [
                'type'              => 'boolean',
                'description'       => 'Deprecated: force manual assignment of booking freebies.',
                'default'           => function() {
                    return Setting::get_value('identity', 'default', 'center_office.freebies_manual_assignment', false);
                }
            ],

            'checkedout_revert_to_quote' => [
                'type'              => 'boolean',
                'description'       => 'Allow reverting checked-in or checked-out bookings to quotes.',
                'default'           => false
            ],

            'accounting_journals_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'finance\accounting\AccountingJournal',
                'foreign_field'     => 'center_office_id',
                'description'       => 'List of accounting journals of the office.'
            ],

            'analytic_section_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\AnalyticSection',
                'description'       => "Related analytic section, if any."
            ],

            'product_favorites_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\catalog\ProductFavorite',
                'foreign_field'     => 'center_office_id',
                'order'             => 'order',
                'description'       => 'List of product favorites of the office.'
            ],

            'email_alt' => [
                'type'              => 'string',
                'usage'             => 'email',
                'description'       => 'Secondary email address for the establishment.'
            ],

            'email_bcc' => [
                'type'              => 'string',
                'usage'             => 'email',
                'description'       => 'Service email address for sending copies of sent messages.'
            ],

            'task_models_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'sale\booking\followup\TaskModel',
                'foreign_field'     => 'center_offices_ids',
                'rel_table'         => 'identity_center_office_rel_sale_booking_task_model',
                'rel_foreign_key'   => 'task_models_id',
                'rel_local_key'     => 'center_office_id',
                'description'       => 'Follow-up task models associated with the center office.',
                'help'              => "When a booking is created for a center office, follow up tasks are generated depending on its associated models."
            ],

            'camp_task_models_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'sale\camp\followup\TaskModel',
                'foreign_field'     => 'center_offices_ids',
                'rel_table'         => 'identity_center_office_rel_sale_camp_task_model',
                'rel_foreign_key'   => 'task_models_id',
                'rel_local_key'     => 'center_office_id',
                'description'       => 'Camp follow-up task models associated with the center office.',
                'help'              => "When a camp enrollment is created for a center office, follow up tasks are generated depending on its associated models."
            ],

            'setting_values_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'discope\setting\SettingValue',
                'foreign_field'     => 'center_office_id',
                'description'       => 'List of settings that relate to the center office.'
            ],

        ];
    }

    public static function generateName() {
        return DataGenerator::legalName();
    }
}
