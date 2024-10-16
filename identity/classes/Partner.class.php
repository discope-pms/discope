<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace identity;

use equal\orm\Model;

class Partner extends Model {

    public static function getName() {
        return "Partner";
    }

    public static function getDescription() {
        return "A Partner describes a relationship between two Identities (contact, employee, customer, provider, payer, other).";
    }

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => "The display name of the partner (related organisation name).",
                'store'             => true,
                'instant'           => true,
                'function'          => 'calcName',
            ],

            'owner_identity_id' => [
                'type'              => 'many2one',
                'foreign_object'    => Identity::getType(),
                'description'       => 'The identity organisation which the targeted identity is a partner of.',
                'default'           => 1
            ],

            'partner_identity_id' => [
                'type'              => 'many2one',
                'foreign_object'    => Identity::getType(),
                'description'       => "The targeted identity (the partner).",
                'onupdate'          => 'onupdatePartnerIdentityId',
                'required'          => true,
                'dependents'        => ['name', 'title', 'phone', 'mobile', 'email']
            ],

            'relationship' => [
                'type'              => 'string',
                'selection'         => [
                    'contact',
                    'employee',
                    'customer',
                    'provider',
                    'payer',
                    'other'
                ],
                'description'       => "The kind of partnership that exists between the identities."
            ],

            // if partner is a contact, keep the organisation (s)he is a contact from
            'partner_organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'description'       => "Target organisation which the contact is working for.",
                'visible'           => [ ['relationship', '=', 'contact'] ]
            ],

            // if partner is a contact, keep its 'position' within the
            'partner_position' => [
                'type'              => 'string',
                'description'       => "Position of the contact (natural person) within the target organisation (legal person), e.g. 'director', 'CEO', 'Regional manager'.",
                'visible'           => [ ['relationship', '=', 'contact'] ]
            ],

            // if partner is a customer, it can have an external reference (e.g. reference assigned by previous software)
            'customer_external_ref' => [
                'type'              => 'string',
                'description'       => "External reference for customer, if any.",
                'visible'           => ['relationship', '=', 'customer']
            ],

            // #memo - email remains related to identity
            'email' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'usage'             => 'email',
                'function'          => 'calcEmail',
                'description'       => "Email of the contact (from Identity)."
            ],

            // #memo - phone remains related to identity
            'phone' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'usage'             => 'phone',
                'function'          => 'calcPhone',
                'description'       => "Phone number of the contact (from Identity)."
            ],

            // #memo - mobile remains related to identity
            'mobile' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'usage'             => 'phone',
                'function'          => 'calcMobile',
                'description'       => "Mobile phone number of the contact (from Identity)."
            ],

            'title' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcTitle',
                'description'       => "Title of the contact (from Identity)."
                // #memo - title origin remains the related identity
            ],

            'lang_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'core\Lang',
                'description'       => "Preferred language of the partner (relates to identity).",
                'default'           => 1
            ],

            'is_active' => [
                'type'              => 'boolean',
                'description'       => "Mark the partner as active.",
                'default'           => true
            ]
        ];
    }

    public function getUnique() {
        return [
            ['owner_identity_id', 'partner_identity_id', 'relationship']
        ];
    }

    public static function onupdatePartnerIdentityId($self) {
        $self->read(['partner_identity_id' => ['lang_id']]);
        foreach($self as $id => $partner) {
            Partner::id($id)->update([
                'lang_id' => $partner['partner_identity_id']['lang_id'] ?? 1
            ]);
        }
    }

    public static function calcName($self) {
        return self::calcFieldOnIdentity($self, 'name');
    }

    public static function calcEmail($self) {
        return self::calcFieldOnIdentity($self, 'email');
    }

    public static function calcPhone($self) {
        return self::calcFieldOnIdentity($self, 'phone');
    }

    public static function calcMobile($self) {
        return self::calcFieldOnIdentity($self, 'mobile');
    }

    public static function calcTitle($self) {
        return self::calcFieldOnIdentity($self, 'title');
    }

    /**
     * Returns value of given field for relation partner_identity_id
     */
    public static function calcFieldOnIdentity($self, $field) {
        $result = [];
        $self->read(['partner_identity_id' => [$field]]);
        foreach($self as $id => $partner) {
            if(isset($partner['partner_identity_id'][$field])) {
                $result[$id] = $partner['partner_identity_id'][$field];
            }
        }

        return $result;
    }

    public static function onchange($event, $values) {
        $result = [];

        if(isset($event['partner_identity_id'])) {
            $identity = Identity::id($event['partner_identity_id'])
                ->read(['name'])
                ->first();

            if(isset($identity['name'])) {
                $result['name'] = $identity['name'];
            }
        }

        return $result;
    }
}
