<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2024
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use identity\CenterOffice;
use equal\orm\ModelFactory;
use sale\booking\Booking;
use sale\pay\Funding;
use sale\pay\Payment;
use sale\pay\PaymentDeadline;

$tests = [
    '1010' => [
        'description'       => 'Validates paid amount, is_paid fields, and updates funding details after a payment is created and linked.',
        'help'              => "Validates the paid amount and 'is paid' calculation, and determines the name based on the due amount and payment deadline name. \n
        This occurs when a payment has been created and associated with the funding, ensuring that the paid amount and 'is paid' status are correctly updated",
        'arrange'           =>  function () {
            $center_data = ModelFactory::create(CenterOffice::class, [
                "values" => [
                    "name"                => "Test Identity 1",
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

            $payment_deadline_data = ModelFactory::create(PaymentDeadline::class, [
                "values" => [
                    "name"                => "Test PaymentDeadline 1",
                    "code"                => 123
                ]
            ]);

            $payment_deadline = PaymentDeadline::create(
                    $payment_deadline_data
                )
                ->read(['id', 'name'])
                ->first(true);

            $booking_data = ModelFactory::create(Booking::class, [
                    "values" => [
                        "center_office_id"    => $center_office['id'],
                        "price"               => 100
                    ]
                ]);

            $booking = Booking::create(
                        $booking_data
                    )
                    ->read(['id', 'name'])
                    ->first(true);


            return [$center_office['id'], $payment_deadline['id'], $booking['id']];
        },
        'act'               =>  function ($data) {

            list($center_office_id, $payment_deadline_id , $booking_id) = $data;

            $funding_data = ModelFactory::create(Funding::class, [
                "values" => [
                    "center_office_id"       => $center_office_id,
                    "booking_id"             => $booking_id,
                    "payment_deadline_id"    => $payment_deadline_id,
                    "due_amount"             => 100
                ]
            ]);

            $funding = Funding::create(
                    $funding_data
                )
                ->read(['id'])
                ->first(true);

            $payment_data = ModelFactory::create(Payment::class, [
                "values" => [
                    "funding_id"     => $funding['id'],
                    "status"         => "paid",
                    "amount"         => 100
                ]
                ]);

            Payment::create(
                    $payment_data
                )
                ->read(['id'])
                ->first(true);

            $funding = Funding::id($funding['id'])
                ->read(['id','name','due_amount', 'paid_amount', 'is_paid' , 'payments_ids' =>['id', 'amount']])
                ->first(true);

            return $funding;

        },
        'assert'            =>  function ($funding) {

            $total_payment = array_sum(array_column($funding['payments_ids'], 'amount'));


            return ($funding['due_amount'] == $total_payment);

        },
        'rollback'          =>  function () {
            CenterOffice::search(['name', '=', "Test Identity 1"])->delete(true);
            PaymentDeadline::search(['name', '=', "Test PaymentDeadline 1"])->delete(true);
            $funding = Funding::search(['description', '=',  "Test PaymentDeadline 1"])->read(['id'])->first(true);

            Payment::search(['funding_id', '=', $funding['id']])
                ->update([
                        'amount' => 0,
                        'status'        => 'pending'
                ])
                ->delete(true);

            Funding::id($funding['id'])->delete(true);

        }
    ]
];
