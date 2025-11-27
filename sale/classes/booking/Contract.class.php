<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;

class Contract extends \sale\contract\Contract {

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'computed',
                'function'          => 'calcName',
                'result_type'       => 'string',
                'store'             => true,
                'description'       => 'The display name of the contract.'
            ],

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Booking',
                'description'       => 'Booking the contract relates to.',
                'required'          => true
            ],

            'contract_line_groups_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\ContractLineGroup',
                'foreign_field'     => 'contract_id',
                'description'       => 'Contract lines that belong to the contract.',
                'ondetach'          => 'delete'
            ],

            'contract_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\ContractLine',
                'foreign_field'     => 'contract_id',
                'description'       => 'Contract lines that belong to the contract.',
                'ondetach'          => 'delete'
            ],

            'is_locked' => [
                "type"              => "boolean",
                "description"       => "Flag for preventing automated cancellation of the contract.",
                "default"           => false
            ]

        ];
    }

    public static function calcName($self): array {
        $result = [];
        $self->read([
            'customer_id'   => ['name'],
            'booking_id'    => ['name']
        ]);
        foreach($self as $id => $contract) {
            $ids = Contract::search(['booking_id', '=', $contract['booking_id']['id']])->ids();

            $result[$id] = sprintf("%s - %s - %d",
                $contract['customer_id']['name'],
                $contract['booking_id']['name'],
                count($ids)
            );
        }

        return $result;
    }


    /**
     * Check whether an object can be updated, and perform some additional operations if necessary.
     * This method can be overridden to define a more precise set of tests.
     *
     * @param  object   $om         ObjectManager instance.
     * @param  array    $oids       List of objects identifiers.
     * @param  array    $values     Associative array holding the new values to be assigned.
     * @param  string   $lang       Language in which multilang fields are being updated.
     * @return array    Returns an associative array mapping fields with their error messages. En empty array means that object has been successfully processed and can be updated.
     */
    public static function canupdate($om, $oids, $values, $lang='en') {
        $allowed_fields = ['status', 'is_locked', 'price', 'total'];

        if(count(array_diff(array_keys($values), $allowed_fields))) {
            return ['status' => ['not_allowed' => 'Contract cannot be manually updated.']];
        }

        return [];
        // #memo - we prevent parent logic (which uses status but not is_locked)
        // return parent::canupdate($om, $oids, $values, $lang);
    }
}
