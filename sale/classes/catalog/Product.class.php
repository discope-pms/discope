<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\catalog;

use equal\orm\Model;

class Product extends Model {

    public static function getName() {
        return "Product";
    }

    public static function getDescription() {
        return "A Product is a variant of a Product Model. There is always at least one Product for a given Product Model.\n
         Within the organisation, a product is always referenced by a SKU code (assigned to each variant of a Product Model).\n
         A SKU code identifies a single product with all its specific characteristics.\n";
    }

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcName',
                'store'             => true,
                'description'       => 'The full name of the product (label + sku).'
            ],

            'label' => [
                'type'              => 'string',
                'description'       => 'Human readable mnemo for identifying the product. Allows duplicates.',
                'required'          => true,
                'dependents'        => ['name']
            ],

            'sku' => [
                'type'              => 'string',
                'description'       => "Stock Keeping Unit code for internal reference. Must be unique.",
                'required'          => true,
                'unique'            => true,
                'dependents'        => ['name', 'prices_ids' => ['name']]
            ],

            'ean' => [
                'type'              => 'string',
                'usage'             => 'uri/urn.ean',
                'description'       => "IAN/EAN code for barcode generation."
            ],

            'code_legacy' => [
                'type'              => 'string',
                'description'       => "Old code of the product."
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => "Description of the variant (specifics)."
            ],

            'product_model_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\ProductModel',
                'description'       => "Product Model of this variant.",
                'required'          => true,
                'onupdate'          => 'onupdateProductModelId'
            ],

            'family_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Family',
                'description'       => "Product Family which current product belongs to."
            ],

            'is_pack' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'function'          => 'calcIsPack',
                'description'       => 'Is the product a pack? (from model).',
                'store'             => true,
                'readonly'          => true
            ],

            'has_own_price' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'function'          => 'calcHasOwnPrice',
                'description'       => 'Product is a pack with its own price (from model).',
                'visible'           => ['is_pack', '=', true],
                'store'             => true,
                'readonly'          => true
            ],

            'pack_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\catalog\PackLine',
                'foreign_field'     => 'parent_product_id',
                'description'       => "Products that are bundled in the pack.",
                'ondetach'          => 'delete'
            ],

            // #todo - deprecate
            'ref_pack_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\catalog\PackLine',
                'foreign_field'     => 'child_product_id',
                'description'       => "Pack lines that relate to the product."
            ],

            'product_attributes_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\catalog\ProductAttribute',
                'foreign_field'     => 'product_id',
                'description'       => "Attributes set for the product.",
                'ondetach'          => 'delete'
            ],

            'is_locked' => [
                'type'              => 'boolean',
                'description'       => 'Is the pack static? (cannot be modified).',
                'default'           => false,
                'visible'           => [ ['is_pack', '=', true] ]
            ],

            // if the organisation uses price-lists, the price to use depends on the applicable

            'prices_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\price\Price',
                'foreign_field'     => 'product_id',
                'description'       => "Prices that are related to this product.",
                'ondetach'          => 'delete'
            ],

            'stat_section_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\stats\StatSection',
                'description'       => 'Statistics section (overloads the model one, if any).'
            ],

            /* can_buy and can_sell are adapted when related values are changed in parent product_model */

            'can_buy' => [
                'type'              => 'boolean',
                'description'       => "Can this product be purchased?",
                'default'           => false
            ],

            'can_sell' => [
                'type'              => 'boolean',
                'description'       => "Can this product be sold?",
                'default'           => true
            ],

            'groups_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'sale\catalog\Group',
                'foreign_field'     => 'products_ids',
                'rel_table'         => 'sale_catalog_product_rel_product_group',
                'rel_foreign_key'   => 'group_id',
                'rel_local_key'     => 'product_id',
                'description'       => "Groups this product belongs to."
            ],

            'has_age_range' => [
                'type'              => 'boolean',
                'description'       => "Applies on a specific age range?",
                'default'           => false
            ],

            'allow_price_adaptation' => [
                'type'              => 'boolean',
                'description'       => "Flag telling if price adaptation can be applied on the variants (or children for packs).",
                'default'           => true,
                'visible'           => ['is_pack', '=', true],
            ],

            'age_range_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\AgeRange',
                'description'       => "Customers age range the product is intended for.",
                'onupdate'          => 'onupdateAgeRangeId',
                'visible'           => [ ['has_age_range', '=', true] ]
            ],

        ];
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['label', 'sku']);
        foreach($self as $id => $product) {
            if(!empty($product['label']) || !empty($product['sku'])) {
                $result[$id] = "{$product['label']} ({$product['sku']})";
            }
        }

        return $result;
    }

    public static function calcIsPack($self) {
        $result = [];
        $self->read(['product_model_id' => ['is_pack']]);
        foreach($self as $id => $product) {
            $result[$id] = $product['product_model_id']['is_pack'];
        }

        return $result;
    }

    public static function calcHasOwnPrice($self) {
        $result = [];
        $self->read(['product_model_id' => ['has_own_price']]);
        foreach($self as $id => $product) {
            $result[$id] = (bool) $product['product_model_id']['has_own_price'];
        }

        return $result;
    }

    public static function onupdateProductModelId($self) {
        $self->read(['product_model_id' => ['can_sell', 'groups_ids', 'family_id']]);
        foreach($self as $id => $product) {
            Product::id($id)->update([
                'is_pack'       => null,
                'has_own_price' => null,
                'can_sell'      => $product['product_model_id']['can_sell'],
                'groups_ids'    => $product['product_model_id']['groups_ids'],
                'family_id'     => $product['product_model_id']['family_id']
            ]);
        }
    }

    public static function onupdateAgeRangeId($self) {
        $self->read(['age_range_id']);
        foreach($self as $id => $product) {
            Product::id($id)->update([
                'has_age_range' => boolval($product['age_range_id'])
            ]);
        }
    }
}
