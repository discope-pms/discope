<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2024
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\pay\BankStatement;
use identity\CenterOffice;
use equal\orm\ModelFactory;

$tests = [
    '1001' => [
        'description'       => 'Validates IBAN calculation and determines name based on central office, date, and balances.',
        'help'              => "Validates the calculation of the bank account IBAN using the convertBbanToIban function. \n
            Additionally, it calculates the name based on the central office and the date, as well as the old balance (old_balance)
            and new balance (new_balance).",
        'arrange'           =>  function () {
            $center_data = ModelFactory::create(CenterOffice::class, [
                "values" => [
                    "name"                => "Test Identity 1",
                    "organisation_id"     => 1,
                    "bank_account_iban"   =>"BE19123456789012",
                    "bank_account_bic"    =>"DEUTDEDBXXX"
                ]
            ]);

            $center_data = CenterOffice::create(
                    $center_data
                )
                ->read(['id', 'name'])
                ->first(true);

            return [$center_data['id']];
        },
        'act'               =>  function ($data) {

            list($center_office_id) = $data;

            $bankStatement_data = ModelFactory::create(BankStatement::class, [
                "values" => [
                    "bank_account_number"    => 123456789012,
                    "center_office_id"       => $center_office_id,
                    "date"                   => time()
                ]
            ]);

            $bankStatement = BankStatement::create(
                    $bankStatement_data
                )
                ->read(['id','name', 'center_office_id' => ['id', 'name' , 'bank_account_iban'], 'bank_account_number', 'bank_account_iban'])
                ->first(true);
            return $bankStatement;

        },
        'assert'            =>  function ($bankStatement) {

            return (
                $bankStatement['bank_account_number'] == 123456789012 &&
                $bankStatement['bank_account_iban'] == 'BE19123456789012' &&
                $bankStatement['bank_account_iban'] == $bankStatement['center_office_id']['bank_account_iban'] &&
                $bankStatement['center_office_id']['name'] == "Test Identity 1"
            );

        },
        'rollback'          =>  function () {
            CenterOffice::search(['name', '=', "Test Identity 1"])->delete(true);
            BankStatement::search(['bank_account_number', '=',  123456789012])->delete(true);
        }
    ]
];
