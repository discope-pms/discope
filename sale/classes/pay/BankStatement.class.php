<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2024
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\pay;
use equal\orm\Model;

class BankStatement extends Model {

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcName',
                'store'             => true
            ],

            'raw_data'  => [
                'type'              => 'binary',
                'description'       => 'Original file used for creating the statement.'
            ],

            'date' => [
                'type'              => 'date',
                'description'       => 'Date the statement was received.',
                'required'          => true,
                'readonly'          => true
            ],

            'old_balance' => [
                'type'              => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Account balance before the transactions.',
                'required'          => true,
                'readonly'          => true
            ],

            'new_balance' => [
                'type'              => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Account balance after the transactions.',
                'required'          => true,
                'readonly'          => true
            ],

            'statement_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\pay\BankStatementLine',
                'foreign_field'     => 'bank_statement_id',
                'description'       => 'The lines that are assigned to the statement.'
            ],

            'status' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcStatus',
                'selection'         => [
                    'pending',                // hasn't been fully processed yet
                    'reconciled'              // has been fully processed (all lines either ignored or reconciled)
                ],
                'description'       => 'Status of the statement (depending on lines).',
                'store'             => true
            ],

            'is_exported' => [
                'type'              => 'boolean',
                'description'       => 'Flag for marking statement as exported (for import in external tool).',
                'default'           => false
            ],

            // #memo - CODA statements comes with IBAN or BBAN numbers for reference account
            'bank_account_number' => [
                'type'              => 'string',
                'description'       => 'Original number of the account (as provided in the statement might not be IBAN).'
            ],

            'bank_account_bic' => [
                'type'              => 'string',
                'description'       => 'Bank Identification Code of the account.'
            ],

            'bank_account_iban' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'usage'             => 'uri/urn.iban',
                'function'          => 'calcBankAccountIban',
                'description'       => 'IBAN representation of the account number.',
                'store'             => true
            ],

            'center_office_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\CenterOffice',
                'description'       => 'Center office related to the statement (based on account number).'
            ]

        ];
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['center_office_id' => ['name'], 'date', 'old_balance', 'new_balance']);
        foreach($self as $oid => $statement) {
            $result[$oid] = sprintf("%s - %s - %s - %s", $statement['center_office_id']['name'], date('Ymd', $statement['date']), $statement['old_balance'], $statement['new_balance']);
        }
        return $result;
    }


    public static function calcBankAccountIban($self) {
        $result = [];
        $self->read(['bank_account_number']);
        foreach($self as $id => $statement) {
            $result[$id] = self::convertBbanToIban($statement['bank_account_number']);
        }
        return $result;
    }

    public static function calcStatus($self) {
        $result = [];
        $self->read(['statement_lines_ids' => ['id', 'status']]);
        foreach ($self as $sid => $statement) {
            $is_reconciled = true;
            if (isset($statement['statement_lines_ids']) && is_array($statement['statement_lines_ids'])) {
                foreach ($statement['statement_lines_ids'] as $line) {
                    if (!in_array($line['status'], ['reconciled', 'ignored'])) {
                        $is_reconciled = false;
                        break;
                    }
                }
            } else {
                $is_reconciled = false;
            }
            $result[$sid] = $is_reconciled ? 'reconciled' : 'pending';
        }

        return $result;
    }


    public static function convertBbanToIban($account_number,$country_code = 'BE') {

        if( !is_numeric(substr($account_number, 0, 2)) ) {
            return $account_number;
        }

        $code_alpha = $country_code;
        $code_num = '';

        for($i = 0; $i < strlen($code_alpha); ++$i) {
            $letter = substr($code_alpha, $i, 1);
            $order = ord($letter) - ord('A');
            $code_num .= '1'.$order;
        }

        $check_digits = substr($account_number, -2);
        $dummy = intval($check_digits.$check_digits.$code_num.'00');
        $control = 98 - ($dummy % 97);
        return trim(sprintf("%s%02d%s", $country_code, $control, $account_number));
    }


    public function getUnique() {
        return [
            ['date', 'old_balance', 'new_balance']
        ];
    }
}