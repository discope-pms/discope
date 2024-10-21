<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2024
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use identity\CenterOffice;
use equal\orm\ModelFactory;
use sale\booking\Booking;
use sale\booking\Consumption;
use sale\catalog\ProductModel;
use sale\catalog\Product;
use sale\customer\Customer;
use identity\Identity;

$tests = [
    '1100' => [
        'description'       => '',
        'help'              => "
            Necessary Information for the Consumption:\n
            CenterOffice, Booking, ProductModel, Identity, and Customer. \n
            For the contract test, the consistency between the calculated values in the contract's group and the contract itself has been verified, based on what is defined in the contract line",
        'arrange'           =>  function () {
            $center_office_data = ModelFactory::create(CenterOffice::class, [
                "values" => [
                    "name"                => "Test CenterOffice 1",
                    "organisation_id"     => 1,
                    "bank_account_iban"   =>"BE19123456789012",
                    "bank_account_bic"    =>"DEUTDEDBXXX"
                ]
            ]);

            $center_office = CenterOffice::create(
                    $center_office_data
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
                ->update(["name"     => "Customer"])
                ->read(['id'])
                ->first(true);


            $booking_data = ModelFactory::create(Booking::class, [
                    "values" => [
                        "description"         => "Test Booking 1",
                        "center_office_id"    => $center_office['id'],
                        "customer_id"         => $customer['id'],
                        "status"              => "quote",
                        "price"               => 0
                    ]
                ]);

            $booking = Booking::create(
                    $booking_data
                )
                ->read(['id', 'name'])
                ->first(true);

            $product_model_data = ModelFactory::create(ProductModel::class, [
                "values" => [
                    "name"              => "Test Product Model 1",
                    "type"              => "service",
                    "is_rental_unit"    => true,
                    "is_accomodation"   => true,
                    "is_meal"           => false,
                    "is_snack"          => false,
                    "family_id"         => 1
                ]
            ]);

            $product_model = ProductModel::create(
                    $product_model_data
                )
                ->read(['id', 'name'])
                ->first(true);


            return [$booking['id'], $product_model['id']];
        },
        'act'               =>  function ($data) {

            list($booking_id, $product_model_id) = $data;


            $product_data = ModelFactory::create(Product::class, [
                "values" => [
                    "name"                  => "Product Rental Unit",
                    "product_model_id"      => $product_model_id
                ]
            ]);

            $product = Product::create(
                    $product_data
                )
                ->read(['id', 'product_model_id','name'])
                ->first(true);

            $consumption_rental_unit_data = ModelFactory::create(Consumption::class, [
                "values" => [
                    "booking_id"             => $booking_id,
                    "center_id"              => 1,
                    "type"                   => "book",
                    "product_id"             => $product['id'],
                    "product_model_id"       => $product['product_model_id'],
                    "date"                   => time(),
                    "schedule_from"          => 10,
                    "description"            => "Test Consumption  rental unit "
                ]
            ]);

            $consumption = Consumption::create(
                    $consumption_rental_unit_data
                )
                ->read(['id' , 'name' , 'date', 'schedule_from',
                                'product_id' => ['id', 'name'],
                                'customer_id' => ['id', 'name'],
                                'booking_id' => ['id', 'customer_id']])
                ->first(true);
            return $consumption;

        },
        'assert'            =>  function ($consumption) {

            $datetime = $consumption['date'] + $consumption['schedule_from'];
            $moment = date("d/m/Y H:i:s", $datetime);

            return (
                    strpos($consumption['name'], $consumption['product_id']['name'])  !== false &&
                    strpos($consumption['name'], $consumption['customer_id']['name']) !== false &&
                    strpos($consumption['name'], $moment) !== false &&
                    $consumption['customer_id']['id'] == $consumption['booking_id']['customer_id']
            );

        },
        'rollback'          =>  function () {
            CenterOffice::search(['name', '=', "Test CenterOffice 1"])->delete(true);
            $booking = Booking::search(['name', 'like', '%'. "Test Booking 1".'%'])->read('id')->first(true);
            ProductModel::search(['name', 'like', '%'. "Test Product Model 1".'%'])->delete(true);
            Product::search(['name', 'like', '%'. "Product Rental Unit".'%'])->delete(true);
            Consumption::search(['booking_id', '=', $booking['id']])->delete(true);
            Booking::id($booking['id'])->delete(true);
            $identity = Identity::search(['name', '=', 'Test Identity 1'])->read(['id'])->first(true);
            Customer::search(['partner_identity_id', '=', $identity['id']])->delete(true);
            Identity::id($identity['id'])->delete(true);
        }
    ]
];
