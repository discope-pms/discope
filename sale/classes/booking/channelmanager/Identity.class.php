<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace sale\booking\channelmanager;

/**
 * This class overrides the fields on which specific usage constraints are applied, in order to remove those and allow arbitrary values.
 */
class Identity extends \identity\Identity {

    public static function getModelScope(): ?string {
        return \identity\Identity::getType();
    }

    public static function getColumns() {
        return [

            'firstname' => [
                'type'              => 'string',
                'description'       => "Full name of the contact (must be a person, not a role)."
            ],

            'lastname' => [
                'type'              => 'string',
                'description'       => 'Reference contact surname.'
            ],

            'email' => [
                'type'              => 'string',
                'description'       => "Identity main email address."
            ],

            'phone' => [
                'type'              => 'string',
                'description'       => "Identity main phone number (mobile or landline)."
            ],

            'address_country' => [
                'type'              => 'string',
                'description'       => 'Country.',
                'onupdate'          => 'onupdateAddressCountry'
            ]

        ];
    }

    public static function onupdateAddressCountry($self, $values) {
        if(isset($values['address_country'])) {
            if(in_array($values['address_country'], ['be', 'Belgium', 'belgium', 'belgique', 'Belgique', 'Belgie', 'België', 'belgie', 'belgië'])) {
                $self->update(['address_country' => 'BE']);
            }
        }
        elseif(isset($values['address_country'])) {
            if(in_array($values['address_country'], ['nl', 'The Netherlands', 'the netherlands', 'netherlands', 'Netherlands'])) {
                $self->update(['address_country' => 'NL']);
            }
        }
        elseif(isset($values['address_country'])) {
            if(in_array($values['address_country'], ['fr', 'France', 'france'])) {
                $self->update(['address_country' => 'FR']);
            }
        }
    }

    // remove all constraints
    public static function getConstraints() {
        return [];
    }
}
