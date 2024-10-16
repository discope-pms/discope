<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2024
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\pay;
use equal\orm\Model;

class Payment extends Model {

    public static function getColumns() {

        return [

            'booking_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'function'          => 'calcBookingId',
                'foreign_object'    => 'sale\booking\Booking',
                'description'       => 'The booking the payement relates to, if any (computed).',
                'store'             => true
            ],

            'partner_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Partner',
                'description'       => "The partner to whom the payment relates."
            ],

            'amount' => [
                'type'              => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Amount paid (whatever the origin).'
            ],

            'communication' => [
                'type'              => 'string',
                'description'       => "Message from the payer.",
            ],

            'receipt_date' => [
                'type'              => 'datetime',
                'description'       => "Time of reception of the payment.",
                'default'           => time()
            ],

            'payment_origin' => [
                'type'              => 'string',
                'selection'         => [
                    'cashdesk', // money was received at the cashdesk
                    'bank', // money was received on a bank account
                    'online' // money was received online, through a PSP
                ],
                'description'       => "Origin of the received money.",
                'default'           => 'bank'
            ],

            'payment_method' => [
                'type'              => 'string',
                'selection'         => [
                    'voucher',              // gift, coupon, or tour-operator voucher
                    'cash',                 // cash money
                    'bank_card',            // electronic payment with credit or debit card
                    'wire_transfer'         // transfer between bank account
                ],
                'description'       => "The method used for payment at the cashdesk.",
                'visible'           => [ ['payment_origin', '=', 'cashdesk'] ],
                'default'           => 'cash'
            ],

            'operation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pos\Operation',
                'description'       => 'The operation the payment relates to.',
                'visible'           => ['payment_origin', '=', 'cashdesk']
            ],

            'statement_line_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pay\BankStatementLine',
                'description'       => 'The bank statement line the payment relates to.',
                'visible'           => ['payment_origin', '=', 'bank']
            ],

            'voucher_ref' => [
                'type'              => 'string',
                'description'       => 'The reference of the voucher the payment relates to.',
                'visible'           => ['payment_method', '=', 'voucher']
            ],

            'funding_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pay\Funding',
                'description'       => 'The funding the payement relates to, if any.',
                'onupdate'          => 'onupdateFundingId'
            ],

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\Invoice',
                'description'       => 'The invoice targeted by the payment, if any.'
            ],

            'is_exported' => [
                'type'              => 'boolean',
                'description'       => 'Mark the payment as exported (part of an export to elsewhere).',
                'default'           => false
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'pending',
                    'paid'
                ],
                'description'       => 'Current status of the payment.',
                'default'           => 'paid'
            ],

            'center_office_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'identity\CenterOffice',
                'function'          => 'calcCenterOfficeId',
                'description'       => 'Center office related to the statement (from statement_line_id).',
                'store'             => true
            ],

            'is_manual' => [
                'type'              => 'boolean',
                'description'       => 'Payment was created manually at the checkout directly in the booking (not through cashdesk).',
                'default'           => false
            ]

        ];
    }

    public static function calcBookingId($self) {
        $result = [];
        $self->read(['funding_id' => ['booking_id']]);
        foreach($self as $id => $payment) {
            if(isset($payment['funding_id']['booking_id'])) {
                $result[$id] = $payment['funding_id']['booking_id'];
            }
        }
        return $result;
    }


    public static function calcCenterOfficeId($self) {
        $result = [];
        $self->read(['statement_line_id' => 'center_office_id']);
        foreach($self as $id => $payment) {
            if(isset($payment['statement_line_id']['center_office_id'])) {
                $result[$id] = $payment['statement_line_id']['center_office_id'];
            }
        }
        return $result;
    }

    /**
     * Assign partner_id and invoice_id from invoice relating to funding, if any.
     * Force recomputing of target funding computed fields (is_paid and paid_amount).
     *
     */
    public static function onupdateFundingId($om, $ids, $values, $lang) {
        trigger_error("ORM::calling sale\pay\Payment::onupdateFundingId", QN_REPORT_DEBUG);

        $payments = $om->read(self::getType(), $ids, ['funding_id', 'partner_id']);

        if($payments > 0) {
            // $fundings_ids = [];
            foreach($payments as $pid => $payment) {

                if($payment['funding_id']) {
                    // make sure a partner_id is assigned to the payment
                    if(!$payment['partner_id']) {
                        $fundings = $om->read('sale\pay\Funding', $payment['funding_id'], [
                                'type',
                                'due_amount',
                                'invoice_id',
                                'invoice_id.partner_id.id',
                                'invoice_id.partner_id.name'
                            ],
                            $lang);

                        if($fundings > 0 && count($fundings) > 0) {
                            $funding = reset($fundings);
                            if($funding['type'] == 'invoice') {
                                $values['partner_id'] = $funding['invoice_id.partner_id.id'];
                                $values['invoice_id'] = $funding['invoice_id'];
                            }
                            $om->update(self::getType(), $pid, $values);
                        }
                    }

                }
            }
        }
    }

    public static function canupdate($self, $values) {
        $self->read(['state', 'is_exported', 'payment_origin', 'amount', 'statement_line_id' => ['amount', 'remaining_amount']]);
        foreach($self as $payment) {
            if($payment['is_exported']) {
                return ['is_exported' => ['non_editable' => 'Once exported a payment can no longer be updated.']];
            }

            $payment_origin = $values['payment_origin'] ?? $payment['payment_origin'];
            if($payment_origin == 'bank' && isset($values['amount'])) {
                $statement_line = $payment['statement_line_id'];
                if(isset($values['statement_line_id'])) {
                    $statement_line = BankStatementLine::id($values['statement_line_id'])
                        ->read(['amount', 'remaining_amount'])
                        ->first();
                }

                $sign_line = intval($statement_line['amount'] > 0) - intval($statement_line['amount'] < 0);
                $sign_payment = intval($values['amount'] > 0) - intval($values['amount'] < 0);

                // #memo - we prevent creating payment that do not decrease the remaining amount
                if($sign_line != $sign_payment) {
                    return ['amount' => ['incompatible_sign' => "Payment amount ({$values['amount']}) and statement line amount ({$statement_line['amount']}) must have the same sign."]];
                }

                // #memo - when state is still draft, it means that reconcile is made manually
                if($payment['state'] == 'draft') {
                    if(round($statement_line['amount'], 2) < 0) {
                        if(round($statement_line['remaining_amount'] - $values['amount'], 2) > 0) {
                            return ['amount' => ['excessive_amount' => "Payment amount ({$values['amount']}) cannot be higher than statement line remaining amount ({$statement_line['remaining_amount']}) (err#1)."]];
                        }
                    }
                    else {
                        if(round($statement_line['remaining_amount'] - $values['amount'], 2) < 0) {
                            return ['amount' => ['excessive_amount' => "Payment amount ({$values['amount']}) cannot be higher than statement line remaining amount ({$statement_line['remaining_amount']}) (err#2)."]];
                        }
                    }
                }
                else  {
                    if(round($statement_line['amount'], 2) < 0) {
                        if(round($statement_line['remaining_amount'] + $payment['amount'] - $values['amount'], 2) > 0) {
                            return ['amount' => ['excessive_amount' => "Payment amount ({$values['amount']}) cannot be higher than statement line remaining amount ({$statement_line['remaining_amount']}) (err#3)."]];
                        }
                    }
                    else {
                        if(round($statement_line['remaining_amount'] + $payment['amount'] - $values['amount'], 2) < 0) {
                            return ['amount' => ['excessive_amount' => "Payment amount ({$values['amount']}) cannot be higher than statement line remaining amount ({$statement_line['remaining_amount']}) (err#4)."]];
                        }
                    }
                }
            }
        }

        return parent::canupdate($self, $values);
    }


    public static function ondelete($self) {
        $self->read(['statement_line_id', 'funding_id']);
        foreach($self as $payment) {
            BankStatementLine::id($payment['statement_line_id'])->update(['status' => 'pending']);
            Funding::id($payment['funding_id'])->update(['is_paid' => false]);
        }

        return parent::ondelete($self);
    }

    public static function candelete($self) {
        $self->read(['payment_origin', 'status', 'is_manual', 'statement_line_id'=>['status']]);

        foreach($self as $id => $payment) {
            if($payment['status'] == 'paid') {
                return ['status' => ['non_removable' => 'Paid payment cannot be removed.']];
            }
            if($payment['payment_origin'] == 'bank') {
                if($payment['statement_line_id']['status'] != 'pending') {
                    return ['status' => ['non_removable' => 'Payment from reconciled line cannot be removed.']];
                }
            } elseif(!$payment['is_manual']) {
                return ['payment_origin' => ['non_removable' => 'Payment cannot be removed.']];
            }
        }

        return parent::candelete($self);
    }

        /**
     * Signature for single object change from views.
     *
     * @param  Object   $om        Object Manager instance.
     * @param  Array    $event     Associative array holding changed fields as keys, and their related new values.
     * @param  Array    $values    Copy of the current (partial) state of the object.
     * @param  String   $lang      Language (char 2) in which multilang field are to be processed.
     * @return Array    Associative array mapping fields with their resulting values.
     */
    public static function onchange($om, $event, $values, $lang='en') {
        $result = [];

        if(isset($event['funding_id'])) {
            $fundings = $om->read('sale\booking\Funding', $event['funding_id'], ['type', 'due_amount', 'booking_id.customer_id.id', 'booking_id.customer_id.name', 'invoice_id.partner_id.id', 'invoice_id.partner_id.name'], $lang);
            if($fundings > 0) {
                $funding = reset($fundings);

                if($funding['type'] == 'invoice')  {
                    $result['partner_id'] = [ 'id' => $funding['invoice_id.partner_id.id'], 'name' => $funding['invoice_id.partner_id.name'] ];
                }
                else {
                    $result['partner_id'] = [ 'id' => $funding['booking_id.customer_id.id'], 'name' => $funding['booking_id.customer_id.name'] ];
                }

                if(isset($values['amount']) && $values['amount'] > $funding['due_amount']) {
                    $result['amount'] = $funding['due_amount'];
                }
            }
        }

        return $result;
    }


    public static function getConstraints() {
        return parent::getConstraints();
    }
}