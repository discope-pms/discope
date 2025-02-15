<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking\channelmanager;

class ExtraService extends \equal\orm\Model {

    public function getTable() {
        return 'lodging_sale_booking_channelmanager_extraservice';
    }

    public static function getDescription() {
        return "Extra services are used as interface for mapping local product Model with Services from the channel manager.";
    }

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => "Name of the extra service.",
                'store'             => true,
                'function'          => 'calcName'
            ],

            'extref_inventory_code' => [
                'type'              => 'string',
                'description'       => "External reference of the extra service (from channel manager).",
                'required'          => true
            ],

            'property_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\channelmanager\Property',
                'description'       => "The center to the property refers to.",
                'required'          => true,
            ],

            'product_model_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\ProductModel',
                'description'       => "Product Model to use when a room of this type is booked.",
                'required'          => true,
            ],

        ];
    }

    public static function calcName($om, $ids, $lang) {
        $result = [];
        $services = $om->read(self::getType(), $ids, ['product_model_id.name'], $lang);
        if($services > 0) {
            foreach($services as $id => $service) {
                $result[$id] = $service['product_model_id.name'];
            }
        }
        return $result;
    }
}
