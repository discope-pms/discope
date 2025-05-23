<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;

class Contact extends \identity\Partner {

    public static function getName() {
        return "Contact";
    }

    public static function getDescription() {
        return "Booking contacts are persons involved in the organisation of a booking.";
    }

    public function getTable() {
        // force table name to use distinct tables and ID columns
        return 'sale_booking_contact';
    }

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'relation'          => ['partner_identity_id' => 'name'],
                'store'             => true,
                'description'       => 'The display name of the partner (related organisation name).'
            ],

            'owner_identity_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'description'       => 'The organisation which the targeted identity is a partner of.',
                'default'           => 1
            ],

            'partner_identity_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'description'       => 'The targeted identity (the partner).',
                'onupdate'          => 'onupdatePartnerIdentityId',
                'dependents'        => ['name', 'title', 'email', 'phone', 'mobile']
            ],

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Booking',
                'description'       => 'Booking the contact relates to.'
            ],

            'relationship' => [
                'type'              => 'string',
                'default'           => 'contact',
                'description'       => "The partnership should remain 'contact'."
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => [
                    'booking',          // person that is in charge of handling the booking details
                    'invoice',          // person to who the invoice of the booking must be sent
                    'contract',         // person to who the contract(s) must be sent
                    'sojourn',          // person that will be present during the sojourn (beneficiary)
                    'guest_list'        // person to who the guest list must be sent
                ],
                'description'       => 'The kind of contact, based on its responsibilities.',
                'default'           => 'booking'
            ],

            'origin' => [
                'type'              => 'string',
                'selection'         => [
                    'auto',          // contact imported from customer contacts
                    'manual'         // manually created contact
                ],
                'default'           => 'manual'
            ],

            'is_direct_contact' => [
                'type'              => 'boolean',
                'description'       => 'The new contact for the person responsible for the guest list.',
                'visible'           => ['type', '=', 'guest_list'],
                'default'           => false
            ]

        ];
    }

    public static function onchange($om, $event, $values, $lang='en') {
        $result = [];
        if(isset($event['type'])) {
            $result['is_direct_contact'] = ($event['type'] == "guest_list");
        }
        if(isset($event['is_direct_contact'])) {
            $result['partner_identity_id'] = null;
        }
        return $result;
    }

    public function getUnique() {
        return [
            ['owner_identity_id', 'partner_identity_id', 'booking_id']
        ];
    }
}
