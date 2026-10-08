<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace identity;

use discope\setting\Setting;
use equal\orm\Model;

class Address extends Model {

    public static function getName() {
        return "Address";
    }

    public static function getDescription() {
        return "An address is a physical location at which an identity can be contacted.";
    }

    public static function getColumns() {
        return [

            'display_name' => [
                'type'             => 'alias',
                'alias'            => 'name'
            ],

            'name' => [
                'type'              => 'computed',
                'function'          => 'calcName',
                'result_type'       => 'string',
                'store'             => true,
                'description'       => 'The display name of the address.'
            ],

            'identity_name' => [
                'type'              => 'computed',
                'function'          => 'calcIdentityName',
                'result_type'       => 'string',
                'store'             => true,
                'description'       => 'The display name of the related identity.'
            ],

            'identity_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'description'       => 'The identity that the address relates to.',
                'dependents'        => ['identity_name']
            ],

            'role' => [
                'type'              => 'string',
                'selection'         => [ 'legal', 'invoice', 'delivery', 'other' ],
                'description'       => 'The main purpose for which the address is to be preferred.'
            ],

            /*
                Description of the address.
            */
            'address_street' => [
                'type'              => 'string',
                'description'       => 'Street and number.',
                'dependents'        => ['name']
            ],

            'address_dispatch' => [
                'type'              => 'string',
                'description'       => 'Optional info for mail dispatch (appartement, box, floor, ...).',
                'dependents'        => ['name']
            ],

            'address_city' => [
                'type'              => 'string',
                'description'       => 'City.',
                'dependents'        => ['name']
            ],

            'address_zip' => [
                'type'              => 'string',
                'description'       => 'Postal code.',
                'dependents'        => ['name']
            ],

            'address_state' => [
                'type'              => 'string',
                'description'       => 'State or region.',
                'dependents'        => ['name']
            ],

            'address_country' => [
                'type'              => 'string',
                'usage'             => 'country/iso-3166:2',
                'description'       => 'Country.',
                'default'           => Setting::get_value('identity', 'organization', 'country_default', 'BE'),
                'dependents'        => ['name']
            ]

        ];
    }

    public static function calcIdentityName($self) {
        $result = [];
        $self->read(['identity_id' => ['name']]);
        foreach($self as $id => $address) {
            $result[$id] = $address['identity_id']['name'] ?? null;
        }

        return $result;
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['address_street', 'address_city', 'address_zip', 'address_country']);
        foreach($self as $id => $address) {
            $result[$id] = "{$address['address_street']} {$address['address_zip']} {$address['address_city']}";
        }

        return $result;
    }
}
