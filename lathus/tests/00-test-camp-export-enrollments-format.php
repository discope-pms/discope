<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

$tests = [
    '0010' => [
        'description'   => 'Validate the fixed-width AIGA camp enrollment export format.',
        'help'          => 'Checks the supplied reference layout, corrected examples, per-camp customer accounts and zero amount exclusion.',
        'arrange'       => function() {
            $format_config = [
                'writing_number'        => ['length' => 6, 'pad_type' => STR_PAD_LEFT],
                'writing_line_number'   => ['length' => 6, 'pad_type' => STR_PAD_LEFT],
                'date'                  => ['length' => 8],
                'general_account'       => ['length' => 11],
                'analytic_account'      => ['length' => 10],
                'journal_account'       => ['length' => 3],
                'file_type'             => ['length' => 3],
                'file_number'           => ['length' => 14],
                'amount_sign'           => ['length' => 1],
                'amount'                => ['length' => 15],
                'writing_label'         => ['length' => 50],
                'due_date'              => ['length' => 8],
                'check_number'          => ['length' => 12],
                'invoice_number'        => ['length' => 20],
                'currency_type'         => ['length' => 1]
            ];

            $expected_lines = file(
                __DIR__.'/00-test-camp-export-enrollments-format.expected.txt',
                FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
            );

            $customer_account = '411C4476';
            if(!str_starts_with($customer_account, 'C411C')) {
                $customer_account = 'C'.$customer_account;
            }

            $customer_line = [
                'writing_number'        => 1,
                'writing_line_number'   => 1,
                'date'                  => '06072026',
                'general_account'       => $customer_account,
                'analytic_account'      => '',
                'journal_account'       => 'VAC',
                'file_type'             => '',
                'file_number'           => 'C26/0001',
                'amount'                => '401.00',
                'writing_label'         => 'AURIAU Axelle',
                'due_date'              => '',
                'check_number'          => '',
                'invoice_number'        => 'C26/0001',
                'currency_type'         => 'E'
            ];

            $product_line = [
                'writing_number'        => 1,
                'writing_line_number'   => 2,
                'date'                  => '06072026',
                'general_account'       => '70680000',
                'analytic_account'      => 'A980084',
                'journal_account'       => 'VAC',
                'file_type'             => '',
                'file_number'           => 'C26/0001',
                'amount'                => '-401.00',
                'writing_label'         => 'AURIAU Axelle',
                'due_date'              => '',
                'check_number'          => '',
                'invoice_number'        => 'C26/0001',
                'currency_type'         => 'E'
            ];

            return [
                $format_config,
                $expected_lines,
                $customer_line,
                $product_line
            ];
        },
        'act'           => function($data) {
            [
                $format_config,
                $expected_lines,
                $customer_line,
                $product_line
            ] = $data;

            $format_line = function(array $line) use($format_config): string {
                $line['amount_sign'] = str_starts_with($line['amount'], '-') ? '-' : '';
                $line['amount'] = ltrim($line['amount'], '-');

                $formatted_line = [];
                foreach($format_config as $key => $config) {
                    $formatted_line[] = str_pad(
                        $line[$key],
                        $config['length'],
                        ' ',
                        $config['pad_type'] ?? STR_PAD_RIGHT
                    );
                }

                return implode('', $formatted_line);
            };

            $formatted_customer_line = $format_line($customer_line);
            $formatted_product_line = $format_line($product_line);

            return [
                'expected_lines'                 => $expected_lines,
                'expected_line_length'           => array_sum(array_column($format_config, 'length')),
                'formatted_customer_line'        => $formatted_customer_line,
                'formatted_product_line'         => $formatted_product_line,
                'second_camp_customer_account'   => 'C'.'411C5123',
                'zero_amount_is_exportable'      => round(0.0, 2) !== 0.0,
                'rounded_zero_is_exportable'     => round(0.004, 2) !== 0.0,
                'non_zero_amount_is_exportable'  => round(0.01, 2) !== 0.0
            ];
        },
        'assert'        => function($data) {
            if(!is_array($data['expected_lines']) || count($data['expected_lines']) !== 2) {
                return false;
            }

            foreach($data['expected_lines'] as $line) {
                if(
                    str_contains($line, "\t")
                    || mb_strlen($line, 'UTF-8') !== $data['expected_line_length']
                ) {
                    return false;
                }
            }

            return (
                trim(mb_substr($data['expected_lines'][0], 20, 11, 'UTF-8')) === 'C411C4476'
                && trim(mb_substr($data['expected_lines'][1], 31, 10, 'UTF-8')) === 'A980084'
                && mb_substr($data['expected_lines'][0], 41, 3, 'UTF-8') === 'VAC'
                && $data['formatted_customer_line'] === $data['expected_lines'][0]
                && $data['formatted_product_line'] === $data['expected_lines'][1]
                && strlen($data['formatted_customer_line']) === 168
                && strlen($data['formatted_product_line']) === 168
                && substr($data['formatted_customer_line'], 20, 11) === 'C411C4476  '
                && substr($data['formatted_product_line'], 20, 11) === '70680000   '
                && substr($data['formatted_product_line'], 31, 10) === 'A980084   '
                && $data['second_camp_customer_account'] === 'C411C5123'
                && $data['zero_amount_is_exportable'] === false
                && $data['rounded_zero_is_exportable'] === false
                && $data['non_zero_amount_is_exportable'] === true
            );
        },
        'rollback'      => function() {
            // No persistent data is created by this format-only test.
        }
    ]
];
