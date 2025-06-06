<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\price;
use equal\orm\Model;

class PriceList extends Model {
    public static function getColumns() {
        /**
         */

        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Short label to ease identification of the list.",
                'onupdate'          => 'onupdateName'
            ],

            'date_from' => [
                'type'              => 'date',
                'description'       => "Start of validity period.",
                'required'          => true
            ],

            'date_to' => [
                'type'              => 'date',
                'description'       => "End of validity period.",
                'required'          => true
            ],

            'duration' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'function'          => 'calcDuration',
                'store'             => true,
                'description'       => "Pricelist validity duration, in days."
            ],

            // #memo - once published, a pricelist shouldn't be editable
            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'pending',              // list is "under construction" (to be confirmed)
                    'published',            // completed and ready to be used
                    'paused',               // (temporarily) on hold (not to be used)
                    'closed'                // can no longer be used (similar to archive)
                ],
                'description'       => 'Status of the list.',
                'onupdate'          => 'onupdateStatus',
                'default'           => 'pending'
            ],

            // needed for retrieving prices without checking the dates
            'is_active' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'function'          => 'calcIsActive',
                'description'       => "Is the pricelist currently applicable? ",
                'help'              => "When this flag is set to true, it means the list is eligible for future bookings. i.e. with a 'date_to' in the future and 'published'.",
                'store'             => true,
                'readonly'          => true
            ],

            // #todo - make this field persistent
            'prices_count' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'function'          => 'calcPricesCount',
                // 'store'             => true,
                'description'       => "Number of prices defined in list."
            ],

            'prices_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\price\Price',
                'foreign_field'     => 'price_list_id',
                'description'       => "Prices that are related to this list, if any.",
            ],

            'price_list_category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\price\PriceListCategory',
                'description'       => "Category this list is related to, if any.",
            ]

        ];
    }

    public static function calcDuration($om, $oids, $lang) {
        $result = [];
        $lists = $om->read(self::getType(), $oids, ['date_from', 'date_to']);

        if($lists > 0 && count($lists)) {
            foreach($lists as $lid => $list) {
                $result[$lid] = round( ($list['date_to'] - $list['date_from']) / (60 * 60 * 24));
            }
        }
        return $result;
    }

    public static function calcIsActive($om, $ids, $lang) {
        $result = [];
        $lists = $om->read(self::getType(), $ids, ['date_from', 'date_to', 'status']);
        $now = time();
        if($lists > 0 && count($lists)) {
            foreach($lists as $lid => $list) {
                $result[$lid] = boolval( $list['date_to'] > $now && $list['status'] == 'published' );
            }
        }
        return $result;
    }


    public static function calcPricesCount($om, $oids, $lang) {
        $result = [];
        $lists = $om->read(self::getType(), $oids, ['prices_ids']);

        if($lists > 0 && count($lists)) {
            foreach($lists as $lid => $list) {
                $result[$lid] = count($list['prices_ids']);
            }
        }
        return $result;
    }

    /**
     * Invalidate related prices names.
     */
    public static function onupdateName($om, $oids, $values, $lang) {
        $lists = $om->read(self::getType(), $oids, ['prices_ids'], $lang);

        if($lists > 0) {
            foreach($lists as $lid => $list) {
                $om->update('sale\price\Price', $list['prices_ids'], ['name' => null]);
            }
        }
    }

    public static function onupdateStatus($om, $ids, $values, $lang) {
        $pricelists = $om->read(self::getType(), $ids, ['status', 'prices_ids']);
        $om->update(self::getType(), $ids, ['is_active' => null]);
        // immediate re-compute (required by subsequent re-computations of prices is_active flag)
        $om->read(self::getType(), $ids, ['is_active']);

        if($pricelists > 0) {
            $providers = \eQual::inject(['cron']);
            $cron = $providers['cron'];

            foreach($pricelists as $pid => $pricelist) {
                if($pricelist['status'] == 'published') {
                    // add a task to the CRON for updating status of bookings waiting for the pricelist
                    $cron->schedule(
                        "booking.is_tbc.confirm",
                        time() + 60,
                        'sale_booking_pricelist_check-bookings',
                        [ 'id' => $pid ]
                    );
                }
                // immediate re-compute prices is_active flag
                $om->update('sale\price\Price', $pricelist['prices_ids'], ['is_active' => null]);
                $om->read('sale\price\Price', $pricelist['prices_ids'], ['is_active']);
            }
        }
    }

}
