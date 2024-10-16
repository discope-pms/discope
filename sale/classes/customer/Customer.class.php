<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\customer;

use sale\booking\Booking;

class Customer extends \identity\Partner {

    public static function getName() {
        return "Customer";
    }

    public static function getDescription() {
        return "A customer is a partner from who originates one or more bookings.";
    }

    public static function getColumns() {
        return [

            // if partner is a customer, it can be assigned to a rate class
            'rate_class_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\RateClass',
                'description'       => "Rate class that applies to the customer.",
                'visible'           => ['relationship', '=', 'customer'],
                'default'           => 1,
                'readonly'          => true
            ],

            'customer_nature_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\CustomerNature',
                'description'       => "Nature of the customer (map with rate classes).",
                'onupdate'          => 'sale\customer\Customer::onupdateCustomerNatureId'
            ],

            // if partner is a customer, it can be assigned a customer type
            'customer_type_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\CustomerType',
                'description'       => "Type of customer (map with rate classes).",
                'visible'           => ['relationship', '=', 'customer'],
                'default'           => 1                                                // default is 'individual'
            ],

            'relationship' => [
                'type'              => 'string',
                'default'           => 'customer',
                'description'       => "Force relationship to Customer"
            ],

            'is_tour_operator' => [
                'type'              => 'boolean',
                'description'       => "Mark the customer as a Tour Operator.",
                'default'           => false
            ],

            // #memo  count must be relative to booking not customer
            'count_booking_12' => [
                'type'              => 'computed',
                'deprecated'        => true,
                'result_type'       => 'integer',
                'function'          => 'calcCountBooking12',
                'description'       => "Number of bookings made during last 12 months (one year)."
            ],

            'count_booking_24' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'function'          => 'calcCountBooking24',
                'description'       => "Number of bookings made during last 24 months (2 years)."
            ],

            'address' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcAddress',
                'description'       => "Main address from related Identity.",
                'store'             => true
            ],

            'bookings_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\Booking',
                'foreign_field'     => 'customer_id',
                'description'       => "The bookings history of the customer.",
            ],

            'ref_account' => [
                'type'              => 'string',
                'usage'             => 'uri/urn.iban',
                'description'       => "Arbitrary reference account number for identifying the customer in external accounting softwares.",
                'readonly'          => true
            ],

            'email_secondary' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'usage'             => 'email',
                'description'       => "Identity secondary email address.",
                'function'          => 'calcEmailSecondary'
            ]

        ];
    }

    public static function candelete($self) {
        $self->read(['bookings_ids']);
        foreach($self as $customer) {
            if(!empty($customer['bookings_ids'])) {
                return ['bookings_ids' => ['non_removable_customer' => 'Customers relating to one or more bookings cannot be deleted.']];
            }
        }

        return parent::candelete($self);
    }

    public static function onupdateCustomerNatureId($self) {
        $self->read(['customer_nature_id' => ['rate_class_id', 'customer_type_id']]);
        foreach($self as $id => $customer) {
            $customer_type_id = $customer['customer_nature_id']['customer_type_id'];
            $rate_class_id = $customer['customer_nature_id']['rate_class_id'];
            if(!empty($customer_type_id) && !empty($rate_class_id)) {
                Customer::id($id)->update([
                    'rate_class_id'     => $rate_class_id,
                    'customer_type_id'  => $customer_type_id
                ]);
            }
        }
    }

    public static function calcAddress($self) {
        $result = [];
        $self->read(['partner_identity_id' => ['address_street', 'address_city']]);
        foreach($self as $id => $customer) {
            $result[$id] = "{$customer['partner_identity_id']['address_street']} {$customer['partner_identity_id']['address_city']}";
        }

        return $result;
    }

    /**
     * Computes the number of bookings made by the customer during the last 12 months.
     */
    public static function calcCountBooking12($self) {
        $result = [];
        $time = time();
        $from = mktime(0, 0, 0, date('m', $time)-12, date('d', $time), date('Y', $time));
        foreach($self as $id => $customer) {
            $bookings_ids = Booking::search([
                ['customer_id', '=', $id],
                ['date_from', '>=', $from],
                ['is_cancelled', '=', false],
                ['status', 'not in', ['quote', 'option']]
            ])
                ->ids();

            $result[$id] = count($bookings_ids);
        }

        return $result;
    }

    /**
     * Computes the number of bookings made by the customer during the last two years.
     */
    public static function calcCountBooking24($self) {
        $result = [];
        $time = time();
        $from = mktime(0, 0, 0, date('m', $time)-24, date('d', $time), date('Y', $time));
        foreach($self as $id => $customer) {
            $bookings_ids = Booking::search([
                ['customer_id', '=', $id],
                ['date_from', '>=', $from],
                ['is_cancelled', '=', false],
                ['status', 'not in', ['quote', 'option']]
            ])
                ->ids();

            $result[$id] = count($bookings_ids);
        }

        return $result;
    }

    public static function calcEmailSecondary($self) {
        $result = [];
        $self->read(['partner_identity_id' => ['email_secondary']]);
        foreach($self as $id => $customer) {
            $result[$id] = $customer['partner_identity_id']['email_secondary'];
        }

        return $result;
    }
}
