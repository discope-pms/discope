<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2024
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use identity\CenterOffice;
use equal\orm\ModelFactory;
use sale\booking\Booking;
use sale\catalog\ProductModel;
use sale\contract\Contract;
use sale\contract\ContractLineGroup;
use sale\contract\ContractLine;
use sale\catalog\Product;
use sale\customer\Customer;
use sale\price\Price;
use sale\customer\RateClass;
use identity\Identity;

$tests = [
    '1020' => [
        'description'       => 'Validates the total and price calculations in the Contract, Contract Group, and Contract Line.',
        'help'              => "
            Necessary Information for the Contract:\n
            CenterOffice, Booking, ProductModel, Product, Identity, and Customer. \n
            For the contract test, the consistency between the calculated values in the contract's group and the contract itself has been verified, based on what is defined in the contract line",
        'arrange'           =>  function () {
            $center_data = ModelFactory::create(CenterOffice::class, [
                "values" => [
                    "name"                => "Test CenterOffice 1",
                    "organisation_id"     => 1,
                    "bank_account_iban"   =>"BE19123456789012",
                    "bank_account_bic"    =>"DEUTDEDBXXX"
                ]
            ]);

            $center_office = CenterOffice::create(
                    $center_data
                )
                ->read(['id', 'name'])
                ->first(true);

            $booking_data = ModelFactory::create(Booking::class, [
                    "values" => [
                        "description"         => "Test Booking 1",
                        "center_office_id"    => $center_office['id'],
                        "status"              => "quote",
                        "price"               => 0
                    ]
                ]);

            $booking = Booking::create(
                    $booking_data
                )
                ->read(['id', 'name'])
                ->first(true);

            $rate_class = RateClass::search(['name', '=', 'T1'])->read(['id', 'name'])->first(true);

            $product_model_data = ModelFactory::create(ProductModel::class, [
                "values" => [
                    "name"          => "Test Product Model  1",
                    "family_id"     => 1
                ]
            ]);

            $product_model = ProductModel::create(
                    $product_model_data
                )
                ->read(['id', 'name'])
                ->first(true);

            $product_data = ModelFactory::create(Product::class, [
                "values" => [
                    "name"                  => "Test Product 1",
                    "product_model_id"      => $product_model['id']
                ]
            ]);

            $product = Product::create(
                    $product_data
                )
                ->read(['id', 'name'])
                ->first(true);

            $identity_data = ModelFactory::create(Identity::class, [
                "values" => [
                    "type_id"        => 4,
                    "legal_name"     => "Test Identity 1"
                ]
            ]);

            $identity = Identity::create(
                    $identity_data
                )
                ->update(["name"     => "Test Identity 1"])
                ->read(['id'])
                ->first(true);


            $customer = Customer::create([
                "partner_identity_id"   => $identity['id'],
                "relationship"          => "customer"
                ])
                ->read(['id'])
                ->first(true);

            return [$booking['id'], $rate_class['id'], $product['id'], $customer['id']];
        },
        'act'               =>  function ($data) {

            list($booking_id, $rate_class_id, $product_id , $customer_id ) = $data;

            $contract_data = ModelFactory::create(Contract::class, [
                "values" => [
                    "booking_id"             => $booking_id,
                    "description"            => "Test Contract 1",
                    "status"                 => 'pending',
                    'customer_id'            => $customer_id
                ]
            ]);

            $contract = Contract::create(
                    $contract_data
                )
                ->read(['id'])
                ->first(true);

            $contract_group = ContractLineGroup::create([
                    'name'          => "Group Contract Test",
                    'contract_id'   => $contract['id'],
                    'rate_class_id' => $rate_class_id
                ])
                ->read(['id', 'name', 'rate_class_id'])
                ->first(true);

            $product = Product::id($product_id)->read(['id'])->first(true);

            $price = Price::search(['product_id','=', $product['id']])->read(['id','name'])->first(true);

            ContractLine::create([
                    'name'                      => "Contract Line Test",
                    'contract_id'               => $contract['id'],
                    'contract_line_group_id'    => $contract_group['id'],
                    'rate_class_id'             => $rate_class_id,
                    'product_id'                => $product['id'],
                    'price_id'                  => $price['id'],
                    'unit_price'                => 100,
                    'qty'                       => 1,
                    'vat_rate'                  => 0
                ])
                ->read(['id', 'name'])
                ->first(true);

            $contract = Contract::id( $contract['id'])
                ->read(['id', 'name', 'price', 'total',
                            'contract_lines_ids' => ['id', 'price', 'total'],
                            'contract_line_groups_ids' => ['id', 'price', 'total'] ])
                ->first(true);


            return $contract;

        },
        'assert'            =>  function ($contract) {

            $total_lines = array_sum(array_column($contract['contract_lines_ids'], 'total'));
            $total_groups = array_sum(array_column($contract['contract_line_groups_ids'], 'total'));

            $price_lines = array_sum(array_column($contract['contract_lines_ids'], 'price'));
            $price_groups = array_sum(array_column($contract['contract_line_groups_ids'], 'price'));

            return ($contract['total'] == $total_lines &&
                    $contract['total'] == $total_groups &&
                    $total_lines == $total_groups &&
                    $contract['price'] == $price_lines &&
                    $contract['price'] == $price_groups &&
                    $price_lines == $price_groups);

        },
        'rollback'          =>  function () {

            CenterOffice::search(['name', '=', "Test CenterOffice 1"])->delete(true);
            Booking::search(['name', 'like', '%'. "Test Booking 1".'%'])->delete(true);
            ProductModel::search(['name', 'like', '%'. "Test Product Model  1".'%'])->delete(true);
            Product::search(['name', 'like', '%'. "Test Product 1".'%'])->delete(true);
            Contract::search(['description', 'like', '%'. "Test Contract 1".'%'])->delete(true);
            $identity = Identity::search(['name', '=', 'Test Identity 1'])->read(['id'])->first(true);
            Customer::search(['partner_identity_id', '=', $identity['id']])->delete(true);
            Identity::id($identity['id'])->delete(true);

        }
    ]
];
