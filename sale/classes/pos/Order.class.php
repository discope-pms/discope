<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\pos;
use equal\orm\Model;
use equal\orm\ObjectManager;
use core\setting\Setting;

class Order extends Model {

    public static function getName() {
        return "Order";
    }

    public static function getDescription() {
        return "Point of sale Order.";
    }

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcName',
                'store'             => true,
                'description'       => 'Number of the order.'
            ],

            'sequence' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'function'          => 'calcSequence',
                'store'             => true,
                'description'       => 'Sequence number (used for naming).'
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'pending',           // consumptions (lines) are being added to the order
                    'payment',           // a waiter is proceeding to the payment
                    'paid'               // order is closed and payment has been received
                ],
                'description'       => 'Current status of the order.',
                'onupdate'          => 'onupdateStatus',
                'default'           => 'pending'
            ],

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => 'The customer the order relates to.'
            ],

            'session_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pos\CashdeskSession',
                'description'       => 'The session the order belongs to.',
                'onupdate'          => 'onupdateSessionId',
                'required'          => true
            ],

            'center_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Center',
                'description'       => "The center the desk relates to (from session)."
            ],

            'has_funding' => [
                'type'              => 'boolean',
                'description'       => 'Does the order relate to a booking funding?',
                'default'           => false
            ],

            'funding_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Funding',
                'description'       => 'The booking funding that relates to the order, if any.',
                'visible'           => ['has_funding', '=', true],
                'onupdate'          => 'onupdateFundingId'
            ],

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Booking',
                'description'       => 'Booking the order relates to.',
                'ondelete'          => 'null'
            ],

            'has_invoice' => [
                'type'              => 'boolean',
                'description'       => 'Does the order relate to an invoice?',
                'default'           => false
            ],

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\Invoice',
                'description'       => 'The invoice that relates to the order, if any.',
                'visible'           => ['has_invoice', '=', true],
                'ondelete'          => 'null',
                'onupdate'          => 'onupdateInvoiceId'
            ],

            'total' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Total tax-excluded price for all lines (computed).',
                'function'          => 'calcTotal',
                'store'             => true
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money',
                'description'       => 'Final tax-included price for all lines (computed).',
                'function'          => 'calcPrice',
                'store'             => true
            ],

            'total_paid' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Total received amount from customer (sum of payments amounts).',
                'help'              => 'The difference (total_paid - price) is expected to be returned in cash to the customer as a cashdesk operation.',
                'function'          => 'calcTotalPaid'
            ],

            'is_exported' => [
                'type'              => 'boolean',
                'description'       => 'Mark the order as exported (invoiced + payment exported).',
                'default'           => false
            ],

            'order_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\pos\OrderLine',
                'foreign_field'     => 'order_id',
                'ondetach'          => 'delete',
                'onupdate'          => 'onupdateOrderLinesIds',
                'description'       => 'The lines that relate to the order.'
            ],

            'order_payments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\pos\OrderPayment',
                'foreign_field'     => 'order_id',
                'ondetach'          => 'delete',
                'description'       => 'The payments that relate to the order.'
            ],

            'order_payment_parts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\pos\OrderPaymentPart',
                'foreign_field'     => 'order_id',
                'ondetach'          => 'delete',
                'description'       => 'The payments parts that relate to the order.'
            ],

            'accounting_entries_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'finance\accounting\AccountingEntry',
                'foreign_field'     => 'order_id',
                'description'       => 'Accounting entries relating to the lines of the order.',
                'ondetach'          => 'delete'
            ]

        ];
    }

    /**
     * Handler called after each status update.
     * Upon payment of the order, update related funding and invoice, if any.
     *
     * @param \equal\orm\ObjectManager  $om Instance of the ObjectManager service.
     */
    public static function onupdateStatus($om, $ids, $values, $lang) {
        if(!isset($values['status'])) {
            return;
        }

        switch($values['status']) {
            case 'paid':
                $orders = $om->read(self::getType(), $ids, ['has_invoice', 'has_funding', 'funding_id.type', 'funding_id.invoice_id', 'center_id.center_office_id'], $lang);
                if($orders > 0) {
                    foreach($orders as $oid => $order) {
                        if($order['has_funding']) {
                            if($order['funding_id.type'] == 'invoice') {
                                // #memo - status of the related invoice/proforma must be changed
                                // $om->update(\finance\accounting\Invoice::getType(), $order['funding_id.invoice_id'], ['status' => 'invoice', 'is_paid' => null], $lang);
                                $om->update(\finance\accounting\Invoice::getType(), $order['funding_id.invoice_id'], ['is_paid' => null], $lang);
                            }
                        }
                        // no funding and no invoice: generate stand alone accounting entries
                        elseif(!$order['has_invoice']) {

                            // filter lines that do not relate to a booking (added as 'extra' services)
                            $order_lines_ids = $om->search(
                                OrderLine::getType(),
                                [['order_id', '=', $oid], ['has_booking', '=', false]]
                            );

                            // generate accounting entries
                            $orders_accounting_entries = self::_generateAccountingEntries($om, [$oid], $order_lines_ids, $lang);
                            if(!isset($orders_accounting_entries[$oid]) || count($orders_accounting_entries[$oid]) === 0) {
                                continue;
                            }

                            $order_accounting_entries = $orders_accounting_entries[$oid];

                            $res = $om->search(
                                \finance\accounting\AccountingJournal::getType(),
                                [['center_office_id', '=', $order['center_id.center_office_id']], ['type', '=', 'bank_cash']]
                            );
                            $journal_id = reset($res);
                            if(!$journal_id) {
                                continue;
                            }

                            // create new entries objects and assign to the sale journal relating to the center_office_id
                            foreach($order_accounting_entries as $entry) {
                                $entry['journal_id'] = $journal_id;
                                $om->create(\finance\accounting\AccountingEntry::getType(), $entry);
                            }
                        }
                    }
                }
                break;
            case 'pending':
            case 'payment':
                $orders = $om->read(self::getType(), $ids, ['has_invoice', 'has_funding'], $lang);
                if($orders > 0) {
                    foreach($orders as $oid => $order) {
                        if(!$order['has_funding'] && !$order['has_invoice']) {
                            $account_entry_ids = $om->search(\finance\accounting\AccountingEntry::getType(), ['order_id', '=', $oid]);
                            if(!empty($account_entry_ids)) {
                                $om->delete(\finance\accounting\AccountingEntry::getType(), $account_entry_ids, true);
                            }
                        }
                    }
                }
                break;
        }
    }

    /**
     * Assign default customer_id based on the center that the session relates to.
     */
    public static function onupdateSessionId($om, $ids, $values, $lang) {
        // retrieve default customers assigned to centers
        $orders = $om->read(self::getType(), $ids, ['session_id.center_id', 'session_id.center_id.pos_default_customer_id'], $lang);

        if($orders > 0) {
            foreach($orders as $id => $order) {
                $om->update(self::getType(), $id, [
                    'center_id'     => $order['session_id.center_id'],
                    'customer_id'   => $order['session_id.center_id.pos_default_customer_id']
                ], $lang);
            }
            $om->update(self::getType(), $ids, ['name' => null, 'sequence' => null]);
        }

    }

    public static function onupdateOrderLinesIds($om, $ids, $values, $lang) {
        $om->write(self::getType(), $ids, ['price' => null, 'total' => null], $lang);
    }

    public static function onupdateFundingId($om, $ids, $values, $lang) {
        $orders = $om->read(self::getType(), $ids, ['funding_id'], $lang);
        if($orders > 0) {
            foreach($orders as $oid => $order) {
                $om->update(self::getType(), $oid, ['has_funding' => ($order['funding_id'] > 0)], $lang);
            }
        }
    }

    public static function onupdateInvoiceId($om, $ids, $values, $lang) {
        $orders = $om->read(self::getType(), $ids, ['invoice_id'], $lang);
        if($orders > 0) {
            foreach($orders as $id => $order) {
                $om->update(self::getType(), $id, ['has_invoice' => ($order['invoice_id'] > 0)], $lang);
            }
        }
    }

    public static function calcName($om, $ids, $lang) {
        $result = [];
        $orders = $om->read(self::getType(), $ids, ['sequence', 'session_id', 'session_id.cashdesk_id'], $lang);
        if($orders > 0) {
            foreach($orders as $oid => $order) {
                $result[$oid] = sprintf("%03d.%05d.%03d", $order['session_id.cashdesk_id'], $order['session_id'], $order['sequence']);
            }
        }
        return $result;
    }

    public static function calcSequence($om, $ids, $lang) {
        trigger_error("ORM::calling sale\pos\Order:calcSequence", QN_REPORT_DEBUG);
        $result = [];
        $orders = $om->read(self::getType(), $ids, ['session_id'], $lang);
        if($orders > 0) {
            foreach($orders as $id => $order) {
                $result[$id] = 1;
                $orders_ids = $om->search(self::getType(), [['session_id', '=', $order['session_id']], ['id', '<>', $id]]);
                // #memo - trying to access sequence of other orders here might lead to infinite loop
                if(count($orders_ids) > 0) {
                    $result[$id] = count($orders_ids) + 1;
                }
            }
        }
        return $result;
    }

    public static function calcTotal($om, $ids, $lang) {
        $result = [];
        $orders = $om->read(self::getType(), $ids, ['order_lines_ids.total']);
        if($orders > 0) {
            foreach($orders as $oid => $order) {
                $result[$oid] = 0.0;
                if($order['order_lines_ids.total'] > 0) {
                    foreach((array) $order['order_lines_ids.total'] as $lid => $line) {
                        $result[$oid] += $line['total'];
                    }
                    $result[$oid] = round($result[$oid], 4);
                }
            }
        }
        return $result;
    }

    public static function calcPrice($om, $ids, $lang) {
        $result = [];
        $orders = $om->read(self::getType(), $ids, ['order_lines_ids.price']);
        if($orders > 0) {
            foreach($orders as $oid => $order) {
                $result[$oid] = 0.0;
                if($order['order_lines_ids.price'] > 0) {
                    foreach((array) $order['order_lines_ids.price'] as $lid => $line) {
                        $result[$oid] += $line['price'];
                    }
                    $result[$oid] = round($result[$oid], 2);
                }
            }
        }
        return $result;
    }

    public static function calcTotalPaid($om, $ids, $lang) {
        $result = [];
        $orders = $om->read(self::getType(), $ids, ['order_payments_ids.total_paid']);
        if($orders > 0) {
            foreach($orders as $oid => $order) {
                $result[$oid] = 0.0;
                if($order['order_payments_ids.total_paid'] > 0) {
                    foreach((array) $order['order_payments_ids.total_paid'] as $pid => $payment) {
                        $result[$oid] += $payment['total_paid'];
                    }
                    $result[$oid] = round($result[$oid], 2);
                }
            }
        }
        return $result;
    }

    public static function canupdate($om, $ids, $values, $lang) {
        if(isset($values['session_id'])) {
            $res = $om->read(CashdeskSession::getType(), $values['session_id'], [ 'status' ]);

            if($res > 0) {
                $session = reset($res);
                if($session['status'] != 'pending') {
                    return ['session_id' => ['non_editable' => 'Orders can only be assigned to open sessions.']];
                }
            }
        }
        return parent::canupdate($om, $ids, $values, $lang);
    }

    /**
     * Check whether an object can be deleted.
     * This method can be overridden to define a more precise set of tests.
     *
     * @param  ObjectManager    $om         ObjectManager instance.
     * @param  int[]            $ids       List of objects identifiers.
     * @return array            Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be deleted.
     */
    public static function candelete($om, $ids) {
        $orders = $om->read(self::getType(), $ids, [ 'status' ]);

        if($orders > 0) {
            foreach($orders as $oid => $order) {
                if($order['status'] == 'paid') {
                    return ['status' => ['non_removable' => 'Paid orders cannot be deleted.']];
                }
            }
        }

        return [];
    }


    /**
     * Generate the accounting entries according to the order line (applies only on non-invoiced orders).
     *
     * @param  \equal\orm\ObjectManager    $om         ObjectManager instance.
     * @param  array                       $oids       List of objects identifiers.
     * @param  array                       $lines_ds   Filtered list of identifiers of lines that must generate entries.
     * @param  string                      $lang       Language code in which to process the request.
     * @return array                       Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be deleted.
     */
    public static function _generateAccountingEntries($om, $oids, $lines_ids, $lang) {
        $result = [];
        // generate the accounting entries
        $orders = $om->read(self::getType(), $oids, ['status'], $lang);
        if($orders > 0) {
            // retrieve specific accounts numbers
            $account_sales = Setting::get_value('finance', 'accounting', 'account.sales', 'not_found');
            $account_sales_taxes = Setting::get_value('finance', 'accounting', 'account.sales_taxes', 'not_found');
            $account_trade_debtors = Setting::get_value('finance', 'accounting', 'account.trade_debtors', 'not_found');

            $res = $om->search(\finance\accounting\AccountChartLine::getType(), ['code', '=', $account_sales]);
            $account_sales_id = reset($res);

            $res = $om->search(\finance\accounting\AccountChartLine::getType(), ['code', '=', $account_sales_taxes]);
            $account_sales_taxes_id = reset($res);

            $res = $om->search(\finance\accounting\AccountChartLine::getType(), ['code', '=', $account_trade_debtors]);
            $account_trade_debtors_id = reset($res);

            if(!$account_sales_id || !$account_sales_taxes_id || !$account_trade_debtors_id) {
                // a mandatory value could not be retrieved
                trigger_error("ORM::missing mandatory account", QN_REPORT_ERROR);
                return [];
            }

            foreach($orders as $oid => $order) {
                if($order['status'] != 'paid') {
                    continue;
                }

                $accounting_entries = [];

                // fetch order lines
                $lines = $om->read(OrderLine::getType(), $lines_ids, [
                    'name', 'product_id', 'qty', 'total', 'price',
                    'price_id.accounting_rule_id.accounting_rule_line_ids'
                ], $lang);

                if($lines > 0) {
                    $debit_vat_sum = 0.0;
                    $credit_vat_sum = 0.0;
                    $prices_sum = 0.0;

                    foreach($lines as $lid => $line) {
                        $vat_amount = abs($line['price']) - abs($line['total']);
                        // sum up VAT amounts
                        $credit_vat_sum += $vat_amount;
                        // sum up sale prices (VAT incl. price)
                        $prices_sum += $line['price'];
                        $rule_lines = [];
                        if(isset($line['price_id.accounting_rule_id.accounting_rule_line_ids'])) {
                            // for products, retrieve all lines of accounting rule
                            $rule_lines = $om->read(\finance\accounting\AccountingRuleLine::getType(), $line['price_id.accounting_rule_id.accounting_rule_line_ids'], ['account_id', 'share']);
                        }
                        foreach($rule_lines as $rid => $rline) {
                            if(isset($rline['account_id']) && isset($rline['share'])) {
                                // create a credit line with product name, on the account related by the product (VAT excl. price)
                                $debit = 0.0;
                                $credit = round($line['total'] * $rline['share'], 2);
                                $accounting_entries[] = [
                                    'name'          => $line['name'],
                                    'has_order'     => true,
                                    'order_id'      => $oid,
                                    'account_id'    => $rline['account_id'],
                                    'debit'         => $debit,
                                    'credit'        => $credit
                                ];
                            }
                        }
                    }

                    // create a credit line on account "taxes to pay"
                    if($credit_vat_sum > 0) {
                        $debit = 0.0;
                        $credit = round($credit_vat_sum, 2);
                        // assign with handling of reversing entries
                        $accounting_entries[] = [
                            'name'          => 'taxes TVA à payer',
                            'has_order'     => true,
                            'order_id'      => $oid,
                            'account_id'    => $account_sales_taxes_id,
                            'debit'         => $debit,
                            'credit'        => $credit
                        ];
                    }

                    // create a debit line on account "taxes to pay"
                    if($debit_vat_sum > 0) {
                        $debit = round($debit_vat_sum, 2);
                        $credit = 0.0;
                        // assign with handling of reversing entries
                        $accounting_entries[] = [
                            'name'          => 'taxes TVA à payer',
                            'has_order'     => true,
                            'order_id'      => $oid,
                            'account_id'    => $account_sales_taxes_id,
                            'debit'         => $debit,
                            'credit'        => $credit
                        ];
                    }

                    // create a debit line on account "trade debtors"
                    $debit = round($prices_sum, 2);
                    $credit = 0.0;
                    // assign with handling of reversing entries
                    $accounting_entries[] = [
                        'name'          => 'créances commerciales',
                        'has_order'     => true,
                        'order_id'      => $oid,
                        'account_id'    => $account_trade_debtors_id,
                        'debit'         => $debit,
                        'credit'        => $credit
                    ];

                    // append generated entries to result
                    $result[$oid] = $accounting_entries;
                }
            }
        }
        return $result;
    }
}
