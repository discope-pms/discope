<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;

use sale\booking\Booking;
use sale\booking\Funding;
use sale\booking\Invoice;

class BankStatementLine extends \sale\pay\BankStatementLine {

    public static function getColumns() {

        return [

            'bank_statement_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\BankStatement',
                'description'       => 'The bank statement the line relates to.',
                'ondelete'          => 'cascade'
            ],

            'center_office_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\CenterOffice',
                'description'       => 'Center office related to the statement (based on account number).',
                'onupdate'          => 'onupdateCenterOfficeId'
            ],

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => 'The customer the line relates to, if known (set at status change).',
                'readonly'          => true
            ],

            'payments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\Payment',
                'foreign_field'     => 'statement_line_id',
                'description'       => 'The list of payments this line relates to .',
                'onupdate'          => 'onupdatePaymentsIds',
                'ondetach'          => 'delete'
            ]

        ];
    }

    /**
     * Handler for center_office_id updates.
     */
    public static function onupdateCenterOfficeId($om, $ids, $values, $lang) {
        trigger_error("ORM::calling sale\booking\BankStatementLine::onupdateCenterOfficeId", QN_REPORT_DEBUG);

        $om->call(self::getType(), 'reconcile', $ids, $values, $lang);
    }

    /**
     * Try to automatically reconcile a newly created statement line with a funding.
     * This method is called by current class (onupdateCenterOfficeId) and controller `sale_pay_bankstatementline_do-reconcile`
     */
    public static function reconcile($om, $ids, $values, $lang) {
        $lines = $om->read(self::getType(), $ids, ['id','status', 'amount', 'center_office_id', 'structured_message', 'message', 'bank_statement_id', 'payments_ids.amount', 'payments_ids.funding_id']);

        if($lines > 0) {
            foreach($lines as $lid => $line) {
                // discard non-pending lines
                if($line['status'] != 'pending') {
                    continue;
                }

                // check if the line must still be reconciled
                $total_paid = 0.0;
                foreach((array) $line['payments_ids.amount'] as $pid => $payment) {
                    $total_paid += $payment['amount'];
                }

                if( abs(round($total_paid, 2)) >= abs(round($line['amount'], 2)) ) {
                    // mark the line as reconciled
                    $om->update(self::getType(), $lid, ['status' => 'reconciled']);
                    // force recomputing computed fields
                    $fundings_ids = [];
                    $bookings_ids = [];
                    $invoices_ids = [];
                    foreach((array) $line['payments_ids.funding_id'] ?? [] as $pid => $payment) {
                        $fundings_ids[] = $payment['funding_id'];
                    }
                    $fundings = $om->read(Funding::getType(), $fundings_ids, ['booking_id', 'invoice_id']);
                    foreach($fundings as $fid => $funding) {
                        $bookings_ids[] = $funding['booking_id'];
                        $invoices_ids[] = $funding['invoice_id'];
                    }
                    $om->update(Funding::getType(), $fundings_ids, ['paid_amount' => null, 'is_paid' => null]);
                    $om->update(Booking::getType(), $bookings_ids, ['payment_status' => null, 'paid_amount' => null]);
                    $om->update(Invoice::getType(), $invoices_ids, ['is_paid' => null]);
                    Booking::updateStatusFromFundings($om, $bookings_ids);
                }
                else {
                    // attempt retrieving Funding candidates from targeted booking

                    // #memo - negative fundings can be created and linked to outgoing payments, for reimbursement, so we consider those as well
                    $found_booking = false;
                    $matching_funding = null;

                    $booking_name = substr($line['structured_message'], 4, 6);

                    // when coming from channel manager, bookings can be targeted by message (non structured communication)
                    $booking_extref_id = preg_replace('/[^0-9]/', '', $line['message']);

                    $format_structured_message = '/^\+\+\+\d{3}\/\d{4}\/\d{4}\+\+\+$/';
                    if (empty($line['structured_message']) && isset($line['message']) && preg_match($format_structured_message, $line['message'])) {
                        $booking_name = substr(preg_replace('/[^0-9]/', '', $line['message']), 3, 6);
                    }

                    $bookings_ids = $om->search(Booking::getType(), ['name', '=', $booking_name]);

                    if(count($bookings_ids)) {
                        $booking_id = reset($bookings_ids);
                        $found_booking = true;
                        $candidates_fundings_ids = $om->search(Funding::getType(), [ ['booking_id', '=', $booking_id], ['is_paid', '=', false] ], ['created' => 'asc']);
                    }

                    if(!$found_booking && $booking_extref_id) {
                        $bookings_ids = $om->search(Booking::getType(), [['extref_reservation_id', '=', $booking_extref_id] ,['is_from_channelmanager', '=', true]]);
                        if($bookings_ids > 0 && count($bookings_ids)) {
                            $booking_id = reset($bookings_ids);
                            $found_booking = true;
                            $candidates_fundings_ids = $om->search(Funding::getType(), [ ['booking_id', '=', $booking_id], ['is_paid', '=', false] ], ['created' => 'asc']);
                        }
                    }

                    if($found_booking) {
                        // prevent assigning a statement line from an office bank account to a reservation of another office
                        $bookings = $om->read(Booking::getType(), $booking_id, ['center_office_id']);
                        $booking = reset($bookings);
                        if($booking['center_office_id'] != $line['center_office_id']) {
                            continue;
                        }

                        if($candidates_fundings_ids > 0 && count($candidates_fundings_ids)) {
                            $fundings = $om->read(Funding::getType(), $candidates_fundings_ids, ['id', 'due_amount', 'paid_amount', 'booking_id', 'booking_id.customer_id', 'invoice_id']);
                            if($fundings > 0 && count($fundings)) {

                                // pass-1 : search for exact match on due_amount
                                foreach($fundings as $fid => $funding) {
                                    // exact match (booking payment_reference + still waiting for full payment)
                                    if( round($funding['paid_amount'], 2) == 0 && round($funding['due_amount'], 2) == round($line['amount'], 2) ) {
                                        $matching_funding = $funding;
                                        // stop after first exact match
                                        break;
                                    }
                                }

                                // pass-2 : search for a match on remaining amount
                                if(!$matching_funding) {
                                    foreach($fundings as $fid => $funding) {
                                        // match on remaining amount
                                        if( abs($funding['due_amount'] - $funding['paid_amount']) >= abs($line['amount']) ) {
                                            $matching_funding = $funding;
                                            // stop after first exact match
                                            break;
                                        }
                                    }
                                }
                            }
                        }

                        if($matching_funding) {
                            // mark the line as successfully reconciled
                            $om->update(self::getType(), $lid, ['status' => 'reconciled']);
                            // create a new payment with assigned amount
                            // #todo - receipt_date should be set according to $line['date']
                            $payment_id = $om->create(Payment::getType(), [
                                'funding_id'        => $matching_funding['id'],
                                'booking_id'        => $matching_funding['booking_id'],
                                'partner_id'        => $matching_funding['booking_id.customer_id'],
                                'center_office_id'  => $line['center_office_id'],
                                'statement_line_id' => $lid,
                                'payment_origin'    => 'bank',
                                'payment_method'    => 'wire_transfer'
                            ],
                                $lang);
                            $om->update(Payment::getType(), $payment_id, ['amount' => $line['amount']]);
                            // force recomputing computed fields
                            $om->update(Funding::getType(), $fid, ['paid_amount' => null, 'is_paid' => null]);
                            $om->update(Booking::getType(), $matching_funding['booking_id'], ['payment_status' => null, 'paid_amount' => null]);
                            $om->update(Invoice::getType(), $matching_funding['invoice_id'], ['is_paid' => null]);
                            Booking::updateStatusFromFundings($om, (array) $matching_funding['booking_id']);
                        }
                        else {
                            // #memo - If the bank statement line is reconciled for a closed reservation, a funding is created, and a payment for the total value of the line is associated.
                            $booking_balanced = $om->search(Booking::getType(), [['id', '=', $booking_id], ['status', '=', 'balanced']]);

                            if(count($booking_balanced)) {
                                $funding_id = $om->create(Funding::getType(), [
                                    'booking_id'        => $booking_id,
                                    'center_office_id'  => $line['center_office_id'],
                                    'description'       => 'Paiement en trop',
                                    'payment_reference' => $line['structured_message'],
                                    'due_amount'        => $line['amount'],
                                    'paid_amount'       => $line['amount'],
                                    'is_paid'           => true
                                ],
                                    $lang);

                                $fundings = $om->read(Funding::getType(), $funding_id, ['booking_id.customer_id']);
                                if(count($fundings)) {
                                    $funding = reset($fundings);
                                    $payment_id = $om->create(Payment::getType(), [
                                        'funding_id'        => $funding_id,
                                        'booking_id'        => $booking_id,
                                        'partner_id'        => $funding['booking_id.customer_id'],
                                        'center_office_id'  => $line['center_office_id'],
                                        'statement_line_id' => $line['id'],
                                        'payment_origin'    => 'bank',
                                        'payment_method'    => 'wire_transfer'
                                    ],
                                        $lang);
                                    $om->update(Funding::getType(), $funding_id, ['paid_amount' => null, 'is_paid' => null]);
                                    $om->update(Payment::getType(), $payment_id, ['amount' => $line['amount']]);
                                    $om->update(BankStatementLine::getType(), $line['id'], ['status' => 'reconciled']);
                                    // set back the reservation status to credit_balance
                                    $om->update(Booking::getType(), $booking_id, ['status' => 'credit_balance']);
                                }
                            }
                        }
                    }
                }
                // recompute parent statement status
                $om->update(\sale\booking\BankStatement::getType(), $line['bank_statement_id'], ['status' => null]);
            } /* end foreach */
        }
    }
}
