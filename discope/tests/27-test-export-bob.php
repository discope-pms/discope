<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2026
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use documents\Export;
use finance\accounting\AccountingJournal;
use identity\Center;
use identity\Identity;
use sale\booking\Booking;
use sale\booking\BookingLineGroup;
use sale\booking\BookingType;
use sale\booking\Invoice;
use sale\catalog\Product;
use sale\customer\CustomerNature;

$services = eQual::inject(['orm']);

/** @var \equal\orm\ObjectManager    $orm */
$orm = $services['orm'];

$tests = [

    '2700' => [
        'description' =>  'Tests that BOB export-invoice handle adaptation of lines VAT to match the invoice subtotal per vat rate.',

        'help' => 'The VAT included price of an booking/invoice line is informational, but has to be used for the compatibility with BOB accounting software. ' .
            'We need to test that the compatibility works in case the lines vat amounts does not match the subtotals per vat rates.',

        'arrange' => function () {
            $center_vat = Center::id(2)->read(['id'])->first(true);
            $booking_type = BookingType::search(['code', '=', 'TP'])->read(['id'])->first(true);
            $customer_nature = CustomerNature::search(['code', '=', 'IN'])->read(['id'])->first(true);
            $customer_identity = Identity::search([['firstname', '=', 'John'], ['lastname', '=', 'Doe']])->read(['id'])->first(true);

            return [$center_vat['id'], $booking_type['id'], $customer_nature['id'], $customer_identity['id']];
        },

        'act' => function ($data) use ($orm) {
            [$center_id, $booking_type_id, $customer_nature_id, $customer_identity_id] = $data;

            $booking = Booking::create([
                'date_from'             => strtotime('2023-08-02'),
                'date_to'               => strtotime('2023-08-05'),
                'center_id'             => $center_id,
                'type_id'               => $booking_type_id,
                'customer_nature_id'    => $customer_nature_id,
                'customer_identity_id'  => $customer_identity_id,
                'description'           => 'Booking to test BOB invoice export'
            ])
                ->read(['id','date_from','date_to'])
                ->first(true);

            $booking_line_group = BookingLineGroup::create([
                'booking_id'        => $booking['id'],
                'is_sojourn'        => true,
                'group_type'        => 'sojourn',
                'rate_class_id'     => 4,
                'sojourn_type_id'   => 2,
                'nb_pers'           => 4
            ])
                ->read(['id'])
                ->first(true);

            $orm->disableEvents();

            try {
                eQual::run('do', 'sale_booking_update-sojourn-dates', [
                    'id'            => $booking_line_group['id'],
                    'date_from'    => $booking['date_from'],
                    'date_to'      => $booking['date_to']
                ]);
            }
            catch(Exception $e) {
                trigger_error("APP::error while running sale_booking_update-sojourn-dates: ".$e->getMessage(), EQ_REPORT_ERROR);
            }

            $pack = Product::search(['sku','=','VS-ChSglPC-A'])
                ->read(['id','label'])
                ->first(true);

            try {
                eQual::run('do', 'sale_booking_update-sojourn-pack-set', [
                    'id'        => $booking_line_group['id'],
                    'pack_id'   => $pack['id']
                ]);
            }
            catch(Exception $e) {
                trigger_error("APP::error while running sale_booking_update-sojourn-pack-set: ".$e->getMessage(), EQ_REPORT_ERROR);
            }

            $orm->enableEvents();

            $booking = Booking::id($booking['id'])
                ->read([
                    'id',
                    'price',
                    'booking_lines_ids'         => ['id', 'name', 'total', 'price'],
                    'booking_lines_groups_ids'  => ['id', 'price']
                ])
                ->first(true);

            Booking::id($booking['id'])->update(['status' => 'checkedout']);

            try {
                eQual::run('do', 'sale_booking_do-invoice', [
                    'id' => $booking['id']
                ]);
            }
            catch(Exception $e) {
                trigger_error("APP::error while running sale_booking_do-invoice: ".$e->getMessage(), EQ_REPORT_ERROR);
            }

            $center = Center::id($center_id)
                ->read(['center_office_id'])
                ->first();

            AccountingJournal::create([
                'name'              => 'Accounting journal to test BOB invoice export',
                'type'              => 'sales',
                'center_office_id'  => $center['center_office_id']
            ]);

            try {
                $invoice = Invoice::search(['booking_id', '=', $booking['id']])
                    ->read(['status'])
                    ->first();

                // update date to match fiscal year
                Invoice::id($invoice['id'])->update(['date' => strtotime('2023-01-01')]);

                eQual::run('do', 'sale_booking_invoice_do-emit', [
                    'id' => $invoice['id']
                ]);

                // update number to not use sequence and format settings
                $orm->update(Invoice::getType(), $invoice['id'], ['number' => '23-02-00001']);

                eQual::run('do', 'finance_payments_bob_export-invoices', [
                    'center_office_id' => $center['center_office_id']
                ]);
            }
            catch(Exception $e) {
                trigger_error("APP::error while running sale_booking_invoice_do-emit or finance_payments_bob_export-invoices: ".$e->getMessage(), EQ_REPORT_ERROR);
            }

            return [$booking['id'], $center['center_office_id']];
        },

        'assert' => function ($data) {
            [$booking_id, $center_office_id] = $data;

            $export = Export::search([
                ['center_office_id', '=', $center_office_id],
                ['export_type', '=', 'invoices'],
                ['is_exported', '=', false],
            ])
                ->read(['data'])
                ->first();

            try {
                $extractTo = sys_get_temp_dir() . '/zip_extract_' . uniqid();
                $tempZip = tempnam(sys_get_temp_dir(), 'zip_');

                file_put_contents($tempZip, $export['data']);

                $zip = new ZipArchive();
                $zip->open($tempZip);

                $zip->extractTo($extractTo);
                $zip->close();

                $filePath = $extractTo . '/LOPDIV_FACT.txt';

                $lopdiv_fact = file_get_contents($filePath);

                $lopdiv_fact_lines = explode(PHP_EOL, $lopdiv_fact);

                $lopdiv_fact_amounts = [];
                foreach($lopdiv_fact_lines as $index => $line) {
                    $is_last = $index === (count($lopdiv_fact_lines) - 1);
                    if($is_last) {
                        continue;
                    }

                    $lopdiv_fact_amounts[] = [
                        'ext_vat_amount'    => floatval(str_replace(',', '.', substr($line, 163, 21))),
                        'vat_amount'        => floatval(str_replace(',', '.', substr($line, 163 + 21, 21)))
                    ];
                }

                $export_file_sum_lines_vat_inc_prices = 0.0;
                foreach($lopdiv_fact_amounts as $lopdiv_fact_amount) {
                    $export_file_sum_lines_vat_inc_prices += $lopdiv_fact_amount['ext_vat_amount'] + $lopdiv_fact_amount['vat_amount'];
                }

                $invoice = Invoice::search(['booking_id', '=', $booking_id])
                    ->read([
                        'price',
                        'invoice_lines_ids' => [
                            'name',
                            'total',
                            'price',
                            'price_id' => ['accounting_rule_id']
                        ]
                    ])
                    ->first(true);

                $invoice_sum_lines_vat_inc_prices = 0.0;
                foreach($invoice['invoice_lines_ids'] as $line) {
                    $invoice_sum_lines_vat_inc_prices += $line['price'];
                }

                return $invoice['price'] === 631.19                                          // Invoice vat included price
                    && round($export_file_sum_lines_vat_inc_prices, 2) === 631.19   // Export invoice vat included price
                    && round($invoice_sum_lines_vat_inc_prices, 2) === 631.21;      // Invoice vat included price with sum of lines prices
            }
            catch(Exception $e) {
                trigger_error("APP::error while reading export: ".$e->getMessage(), EQ_REPORT_ERROR);
            }

            return false;
        },

        'rollback' => function () use ($orm) {
            $booking = Booking::search(['description', 'like', '%'.'Booking to test BOB invoice export'.'%'])
                ->read(['center_office_id'])
                ->first();
            $orm->delete(Booking::getType(), $booking['id'], true);

            $invoice = Invoice::search(['booking_id', '=', $booking['id']])
                ->read(['id'])
                ->first();
            $orm->delete(Invoice::getType(), $invoice['id'], true);

            AccountingJournal::search(['name', '=', 'Accounting journal to test BOB invoice export'])->delete(true);

            Export::search([
                ['center_office_id', '=', $booking['center_office_id']],
                ['export_type', '=', 'invoices'],
                ['is_exported', '=', false],
            ])
                  ->delete(true);
        }
    ],

];
