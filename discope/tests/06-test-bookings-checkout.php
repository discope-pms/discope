<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/

use identity\Center;
use identity\Identity;
use sale\booking\Booking;
use sale\booking\BookingLine;
use sale\booking\BookingLineGroup;
use sale\booking\Contract;
use sale\booking\Invoice;
use realestate\RentalUnit;
use sale\booking\BookingType;
use sale\booking\SojournProductModel;
use sale\booking\SojournProductModelRentalUnitAssignement;
use sale\catalog\Product;
use sale\customer\CustomerNature;


$tests = [

    '0601' => [
        'description' => 'Validate that supplements can be added to the booked services when the reservation is in the check-out status.',

        'help' =>  "
            Create a reservation for 1 person client for two night.
            Change the reservation status from 'quote' to 'confirmed'.
            Validate the contract of the client before the check-in.
            Change the reservation status from 'confirmed' to 'checkedin'.
            Change the reservation status from 'checkedin' to 'checkedout'.
            Validate that the supplements have been included in the reservation.",

        'arrange' =>  function () {
            $center = Center::id(1)->read(['id'])->first(true);
            $booking_type = BookingType::search(['code', '=', 'TP'])->read(['id'])->first(true);
            $customer_nature = CustomerNature::search(['code', '=', 'IN'])->read(['id'])->first(true);
            $customer_identity = Identity::search([['firstname', '=', 'John'], ['lastname', '=', 'Doe']])->read(['id'])->first(true);

            return [$center['id'], $booking_type['id'], $customer_nature['id'], $customer_identity['id']];
        },

        'act' => function ($data) {

            list($center_id, $booking_type_id, $customer_nature_id, $customer_identity_id) = $data;

            $booking = Booking::create([
                    'date_from'             => strtotime('2023-05-02'),
                    'date_to'               => strtotime('2023-05-03'),
                    'center_id'             => $center_id,
                    'type_id'               => $booking_type_id,
                    'customer_nature_id'    => $customer_nature_id,
                    'customer_identity_id'  => $customer_identity_id,
                    'description'           => 'Allow Supplements in Checkout Reservation'
                ])
                ->read(['id','date_from','date_to'])
                ->first(true);

            $booking_line_group = BookingLineGroup::create([
                    'booking_id'     => $booking['id'],
                    'is_sojourn'     => true,
                    'group_type'     => 'sojourn',
                    'has_pack'       => false,
                    'name'           => 'Séjour pour 1 personne pendant 1 nuitée',
                    'order'          => 1,
                    'rate_class_id'  => 4,
                    'sojourn_type_id'=> 1,
                    'date_from'      => $booking['date_from'],
                    'date_to'        => $booking['date_to'],
                    'nb_pers'        => 1,
                ])
                ->read(['id','nb_pers'])
                ->first(true);

            $product = Product::search(['sku','=', 'GA-NuitCh1-A'])->read(['id','product_model_id'])->first(true);

            BookingLine::create([
                    'booking_id'            => $booking['id'],
                    'booking_line_group_id' => $booking_line_group['id'],
                    'product_id'            => $product['id']
                ])
                ->read(['id','name','price'])
                ->first(true);

            $sojourn_product_model = SojournProductModel::search([
                    ['booking_line_group_id', "=", $booking_line_group['id']],
                    ['product_model_id', "=", $product['product_model_id']]
                ])
                ->read(['id'])
                ->first(true);

            $rental_units = RentalUnit::search([
                    ['center_id', '=' , $center_id],
                    ['is_accomodation', '=' , true]
                ])
                ->read(['id','name','sojourn_type_id','capacity','room_types_ids']);

            $num_rua = 0;
            foreach ($rental_units as $rental_unit) {
                if ($num_rua >= $booking_line_group['nb_pers']) {
                    break;
                }
                try {
                    eQual::run('do', 'realestate_do-cleaned', ['id' => $rental_unit['id']]);
                }
                catch(Exception $e) {
                    $e->getMessage();
                }
                $spm_rental_unit_assignement = SojournProductModelRentalUnitAssignement::create([
                        'booking_id'                => $booking['id'],
                        'booking_line_group_id'     => $booking_line_group['id'],
                        'sojourn_product_model_id'  => $sojourn_product_model['id'],
                        'rental_unit_id'            => $rental_unit['id'],
                        'qty'                       => $rental_unit['capacity'],
                        'is_accomodation'           => true
                    ])
                    ->read(['id','qty'])
                    ->first(true);
                $num_rua += $spm_rental_unit_assignement['qty'];
            };

            try {
                eQual::run('do', 'sale_booking_do-option', ['id' => $booking['id']]);
            }
            catch(Exception $e) {
                $e->getMessage();
            }

            try {
                eQual::run('do', 'sale_booking_do-confirm', ['id' => $booking['id']]);
            }
            catch(Exception $e) {
                $e->getMessage();
            }

            try {
                $contract = Contract::search([
                            ['booking_id', '=',  $booking['id']],
                            ['status', '=',  'pending'],
                    ])
                    ->read(['id', 'status'])
                    ->first(true);

                eQual::run('do', 'sale_contract_signed', ['id' => $contract['id']]);
            }
            catch(Exception $e) {
                $e->getMessage();
            }

            try {
                eQual::run('do', 'sale_booking_do-checkin', ['id' => $booking['id']]);
            }
            catch(Exception $e) {
                $e->getMessage();
            }

            try {
                eQual::run('do', 'sale_booking_do-checkout', ['id' => $booking['id']]);
            }
            catch(Exception $e) {
                $e->getMessage();
            }

            $new_product = Product::search(['sku','=', 'GA-Boisson-A' ])->read(['id'])->first(true);

            try {
                $new_booking_line_group = BookingLineGroup::create([
                        'booking_id'     => $booking['id'],
                        'is_sojourn'     => false,
                        'group_type'     => 'simple',
                        'has_pack'       => false,
                        'name'           => 'Suppléments',
                        'order'          => 1,
                        'rate_class_id'  => 4,
                        'is_extra'       => true
                    ])
                    ->read(['id'])
                    ->first(true);
            }
            catch (Exception $e) {
                $e->getMessage();
            }

            try {
                BookingLine::create([
                    'booking_id'            => $booking['id'],
                    'booking_line_group_id' => $new_booking_line_group['id']
                ])
                ->update([
                    'product_id'            => $new_product['id']
                ]);
            }
            catch (Exception $e) {
                $e->getMessage();
            }
            return $booking['id'];
        },

        'assert' =>  function ($booking_id) {
            $booking = Booking::id($booking_id)
                ->read(['id','price', 'status'])
                ->first(true);

            $new_product = Product::search(['sku','=', 'GA-Boisson-A' ])->read(['id'])->first(true);

            $booking_line = BookingLine::search([
                        ['booking_id','=', $booking['id']],
                        ['product_id', '=' , $new_product['id']]])->read(['id','price']);

            return isset($booking_line);
        },

        'rollback' =>  function () {
            $booking = Booking::search(['description', 'ilike', '%'. 'Allow Supplements in Checkout Reservation'.'%'])
                  ->update(['status' => 'quote'])
                  ->read(['id'])
                  ->first(true);

            $invoice = Invoice::search(['booking_id' , '=', $booking['id']])
                  ->read(['id'])
                  ->first(true);

            Invoice::id($invoice['id'])->delete(true);
            Booking::id($booking['id'])->delete(true);
        }
    ],

    '0602' => [

        'description' => 'Validate that the reservation cannot be in checkout status if the invoice has been issued.',

        'help' =>  "
            Create a reservation for 1 person client for two night.
            Change the reservation status from 'quote' to 'confirm'.
            Validate the contract of the client before the check-in.
            Change the reservation status from 'confirmed' to 'checkedin'.
            Change the reservation status from 'checkedin' to 'checkedout'.
            Validate that the supplements have been included in the reservation.",

        'arrange' => function () {
            $center = Center::id(1)->read(['id'])->first(true);
            $booking_type = BookingType::search(['code', '=', 'TP'])->read(['id'])->first(true);
            $customer_nature = CustomerNature::search(['code', '=', 'IN'])->read(['id'])->first(true);
            $customer_identity = Identity::search([['firstname', '=', 'John'], ['lastname', '=', 'Doe']])->read(['id'])->first(true);
            return [$center['id'], $booking_type['id'], $customer_nature['id'], $customer_identity['id']];
        },

        'act' => function ($data) {
            list($center_id, $booking_type_id, $customer_nature_id, $customer_identity_id, $sojourn_type_id ) = $data;
            $booking = Booking::create([
                    'date_from'             => strtotime('2023-05-03'),
                    'date_to'               => strtotime('2023-05-04'),
                    'center_id'             => $center_id,
                    'type_id'               => $booking_type_id,
                    'customer_nature_id'    => $customer_nature_id,
                    'customer_identity_id'  => $customer_identity_id,
                    'description'           => 'Ensure reservation cannot check out if invoice is issued'
                ])
                ->read(['id','date_from','date_to'])
                ->first(true);

            $booking_line_group = BookingLineGroup::create([
                    'booking_id'     => $booking['id'],
                    'is_sojourn'     => true,
                    'group_type'     => 'sojourn',
                    'has_pack'       => false,
                    'name'           => 'Séjour pour 1 personne pendant 1 nuitée',
                    'order'          => 1,
                    'rate_class_id'  => 4,
                    'sojourn_type_id'=> 1,
                    'date_from'      => $booking['date_from'],
                    'date_to'        => $booking['date_to'],
                    'nb_pers'        => 1,
                ])
                ->read(['id','nb_pers'])
                ->first(true);

            $product = Product::search(['sku','=', 'GA-NuitCh1-A' ])->read(['id','product_model_id'])->first(true);

            BookingLine::create([
                    'booking_id'            => $booking['id'],
                    'booking_line_group_id' => $booking_line_group['id'],
                    'product_id'            => $product['id']
                ]);

            $sojourn_product_model = SojournProductModel::search([
                    ['booking_line_group_id', "=", $booking_line_group['id']],
                    ['product_model_id', "=", $product['product_model_id']]
                ])
                ->read(['id'])
                ->first(true);

            $rental_units = RentalUnit::search([
                    ['center_id', '=' , $center_id],
                    ['sojourn_type_id', '=' , $sojourn_type_id],
                    ['is_accomodation', '=' , true]
                ])
                ->read(['id','name','sojourn_type_id','capacity','room_types_ids']);

            $num_rua = 0;
            foreach ($rental_units as $rental_unit) {
                if ($num_rua >= $booking_line_group['nb_pers']) {
                    break;
                }
                try {
                    eQual::run('do', 'realestate_do-cleaned', ['id' => $rental_unit['id']]);
                }
                catch(Exception $e) {
                    $e->getMessage();
                }
                $spm_rental_unit_assignement = SojournProductModelRentalUnitAssignement::create([
                    'booking_id' => $booking['id'],
                    'booking_line_group_id' => $booking_line_group['id'],
                    'sojourn_product_model_id' => $sojourn_product_model['id'],
                    'rental_unit_id' => $rental_unit['id'],
                    'qty' => $rental_unit['capacity'],
                    'is_accomodation' => true
                ])
                ->read(['id','qty'])
                ->first(true);
                $num_rua+= $spm_rental_unit_assignement['qty'];
            }

            try {
                eQual::run('do', 'sale_booking_do-option', ['id' => $booking['id']]);
            }
            catch(Exception $e) {
                $e->getMessage();
            }

            try {
                eQual::run('do', 'sale_booking_do-confirm', ['id' => $booking['id']]);
            }
            catch(Exception $e) {
                $e->getMessage();
            }

            try {
                $contract = Contract::search([
                            ['booking_id', '=',  $booking['id']],
                            ['status', '=',  'pending'],
                    ])
                    ->read(['id', 'status'])
                    ->first(true);
                eQual::run('do', 'sale_contract_signed', ['id' => $contract['id']]);
            }
            catch(Exception $e) {
                $e->getMessage();
            }

            try {
                eQual::run('do', 'sale_booking_do-checkin', ['id' => $booking['id']]);
            }
            catch(Exception $e) {
                $e->getMessage();
            }

            try {
                eQual::run('do', 'sale_booking_do-checkout', ['id' => $booking['id']]);
            }
            catch(Exception $e) {
                $e->getMessage();
            }

            try {
                eQual::run('do', 'sale_booking_do-invoice', ['id' => $booking['id']]);
            }
            catch(Exception $e) {
                $e->getMessage();
            }

            Invoice::search(['booking_id' , '=', $booking['id']])->update(['status' => 'invoice']);

            try {
                eQual::run('do', 'sale_booking_do-checkout', ['id' => $booking['id']]);
            }
            catch (Exception $e) {
                $message = $e->getMessage();

            }

            return $message;
        },

        'assert' => function ($message) {
            return ($message == "emitted_balance_invoice");
        },

        'rollback' => function () {
            $booking = Booking::search(['description', 'ilike', '%'. 'Ensure reservation cannot check out if invoice is issued'.'%'])
                ->update(['status' => 'quote'])
                ->read(['id'])
                ->first(true);

            $invoice = Invoice::search(['booking_id' , '=', $booking['id']])
                    ->read(['id'])
                    ->first(true);

            Invoice::id($invoice['id'])->delete(true);
            Booking::id($booking['id'])->delete(true);
        }
    ]
];
