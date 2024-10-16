<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2024
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\pay;
use equal\orm\Model;
use core\setting\Setting;
use sale\booking\Booking;

class Funding extends Model {

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcName',
                'store'             => true
            ],

            'amount_share' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/percent',
                'function'          => 'calcAmountShare',
                'store'             => true,
                'description'       => "Share of the payment over the total due amount (booking)."
            ],

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Booking',
                'description'       => 'Booking the contract relates to.',
                'ondelete'          => 'cascade',        // delete funding when parent booking is deleted
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => "Optional description to identify the funding."
            ],

            'payments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\pay\Payment',
                'foreign_field'     => 'funding_id',
                'dependents'         => ['is_paid', 'paid_amount']
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => [
                    'installment',
                    'invoice'
                ],
                'default'           => 'installment',
                'description'       => "Deadlines are installment except for last one: final invoice."
            ],

            'order' => [
                'type'              => 'integer',
                'description'       => 'Order by which the funding have to be sorted when presented.',
                'default'           => 0
            ],

            'due_amount' => [
                'type'              => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Amount expected for the funding (computed based on VAT incl. price).',
                'required'          => true,
                'dependents'        => ['name', 'amount_share']
            ],

            'due_date' => [
                'type'              => 'date',
                'description'       => "Deadline before which the funding is expected.",
                'default'           => time()
            ],

            'issue_date' => [
                'type'              => 'date',
                'description'       => "Date at which the request for payment has to be issued.",
                'default'           => time()
            ],

            'paid_amount' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => "Total amount that has been received (can be greater than due_amount).",
                'function'          => 'calcPaidAmount',
                'store'             => true
            ],

            'is_paid' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => "Has the full payment been received?",
                'function'          => 'calcIsPaid',
                'store'             => true,
            ],

            'payment_deadline_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pay\PaymentDeadline',
                'description'       => "The deadline model used for creating the funding, if any.",
                'onupdate'          => 'onupdatePaymentDeadlineId'
            ],

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\Invoice',
                'ondelete'          => 'null',
                'description'       => 'The invoice targeted by the funding, if any.',
                'help'              => 'As a convention, this field is set when a funding relates to an invoice: either because the funding has been invoiced (downpayment or balance invoice), or because it is an installment (deduced from the due amount)',
                'visible'           => ['type', '=', 'invoice']
            ],

            'payment_reference' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcPaymentReference',
                'description'       => 'Message for identifying the purpose of the transaction.',
                'store'             => true
            ],

            'center_office_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\CenterOffice',
                'description'       => "The center office the booking relates to.",
                'required'          => true
            ]

        ];
    }


    public static function calcName($self) {
        $result = [];
        $self->read(['due_amount', 'booking_id' => ['name']]);
        foreach($self as $id => $funding) {
            $result[$id] = $funding['booking_id']['name'].'    '.Setting::format_number_currency($funding['due_amount']);
        }

        return $result;
    }

    public static function calcAmountShare($self) {
        $result = [];
        $self->read(['booking_id' => ['price'], 'due_amount']);

        foreach($self as $id => $funding) {
            $total = round($funding['booking_id']['price'],2);
            if($total == 0) {
                $share = 1;
            }
            else {
                $share = round(abs($funding['due_amount']) / abs($total), 2);
            }
            $sign = ($funding['due_amount'] < 0)?-1:1;
            $result[$id] = $share * $sign;
        }

        return $result;
    }

    public static function calcPaymentReference($om, $ids, $lang) {
        $result = [];
        $fundings = $om->read(self::getType(), $ids, ['booking_id.payment_reference'], $lang);
        foreach($fundings as $id => $funding) {
            $result[$id] = $funding['booking_id.payment_reference'];
        }
        return $result;
    }

    public static function calcPaidAmount($om, $oids, $lang) {
        $result = [];
        $fundings = $om->read(self::getType(), $oids, ['payments_ids.amount'], $lang);
        if($fundings > 0) {
            foreach($fundings as $fid => $funding) {
                $result[$fid] = array_reduce((array) $funding['payments_ids.amount'], function ($c, $funding) {
                    return $c + $funding['amount'];
                }, 0);
            }
        }
        return $result;
    }

    public static function calcIsPaid($om, $oids, $lang) {
        $result = [];
        $fundings = $om->read(self::getType(), $oids, ['due_amount', 'paid_amount'], $lang);
        if($fundings > 0) {
            foreach($fundings as $fid => $funding) {
                $result[$fid] = false;
                if($funding['paid_amount'] >= $funding['due_amount'] && $funding['due_amount'] > 0) {
                    $result[$fid] = true;
                }
            }
        }
        return $result;
    }

    public static function onupdatePaymentDeadlineId($self) {
        $self->read(['payment_deadline_id' => ['name']]);
        foreach ($self as $id => $funding) {
            if (isset($funding['payment_deadline_id']['name']) && strlen($funding['payment_deadline_id']['name']) > 0) {
                Funding::id($id)->update(['description' => $funding['payment_deadline_id']['name']]);
            }
        }
    }


    
    /**
     * Check wether an object can be created.
     * These tests come in addition to the unique constraints returned by method `getUnique()`.
     * Checks wheter the sum of the fundings of a booking remains lower than the price of the booking itself.
     *
     * @param  \equal\orm\ObjectManager     $om         ObjectManager instance.
     * @param  array                        $values     Associative array holding the values to be assigned to the new instance (not all fields might be set).
     * @param  string                       $lang       Language in which multilang fields are being updated.
     * @return array            Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be created.
     */
    public static function cancreate($om, $values, $lang) {
        if(isset($values['booking_id']) && isset($values['due_amount'])) {
            $bookings = $om->read(Booking::getType(), $values['booking_id'], ['price', 'fundings_ids.due_amount'], $lang);
            if($bookings > 0 && count($bookings)) {
                $booking = reset($bookings);
                $fundings_price = (float) $values['due_amount'];
                foreach((array) $booking['fundings_ids.due_amount'] as $fid => $funding) {
                    $fundings_price += (float) $funding['due_amount'];
                }
                if($fundings_price > $booking['price'] && abs($booking['price']-$fundings_price) >= 0.0001) {
                    return ['status' => ['exceded_price' => 'Sum of the fundings cannot be higher than the booking total.']];
                }
            }
        }
        return parent::cancreate($om, $values, $lang);
    }


    /**
     * Check wether an object can be updated.
     * These tests come in addition to the unique constraints returned by method `getUnique()`.
     * Checks wheter the sum of the fundings of each booking remains lower than the price of the booking itself.
     *
     * @param  \equal\orm\ObjectManager     $om         ObjectManager instance.
     * @param  array                        $oids       List of objects identifiers.
     * @param  array                        $values     Associative array holding the new values to be assigned.
     * @param  string                       $lang       Language in which multilang fields are being updated.
     * @return array            Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be updated.
     */
    public static function canupdate($om, $oids, $values, $lang) {
        if(isset($values['due_amount'])) {
            $fundings = $om->read(self::getType(), $oids, ['booking_id'], $lang);

            if($fundings > 0) {
                foreach($fundings as $fid => $funding) {
                    $bookings = $om->read(Booking::getType(), $funding['booking_id'], ['price', 'fundings_ids.due_amount'], $lang);
                    if($bookings > 0 && count($bookings)) {
                        $booking = reset($bookings);
                        $fundings_price = (float) $values['due_amount'];
                        foreach((array) $booking['fundings_ids.due_amount'] as $oid => $odata) {
                            if($oid != $fid) {
                                $fundings_price += (float) $odata['due_amount'];
                            }
                        }
                        if($fundings_price > $booking['price'] && abs($booking['price']-$fundings_price) >= 0.0001) {
                            return ['status' => ['exceeded_price' => "Sum of the fundings cannot be higher than the booking total."]];
                        }
                    }
                }
            }
        }
        return parent::canupdate($om, $oids, $values, $lang);
    }


    public static function candelete($self) {
        $self->read(['is_paid', 'paid_amount', 'invoice_id' => ['status', 'type'], 'payments_ids']);
        foreach($self as $funding) {
            if($funding['is_paid'] || $funding['paid_amount'] != 0 || count($funding['payments_ids']) > 0) {
                return ['payments_ids' => ['non_removable_funding' => 'Funding paid or partially paid cannot be deleted.']];
            }
            if(isset($funding['invoice_id']['status']) && $funding['invoice_id']['status'] == 'invoice' && $funding['invoice_id']['type'] == 'invoice') {
                return ['invoice_id' => ['non_removable_funding' => 'Funding relating to an invoice cannot be deleted.']];
            }
        }

        return parent::candelete($self);
    }

    /**
     * Hook invoked before object deletion for performing object-specific additional operations.
     * Remove the scheduled tasks related to the deleted fundings.
     *
     * @param  \equal\orm\ObjectManager     $om         ObjectManager instance.
     * @param  array                        $oids       List of objects identifiers.
     * @return void
     */
    public static function ondelete($om, $oids) {
        $cron = $om->getContainer()->get('cron');

        foreach($oids as $fid) {
            // remove any previously scheduled task
            $cron->cancel("booking.funding.overdue.{$fid}");
        }
        parent::ondelete($om, $oids);
    }

    public static function onchange($event, $values) {
        $result = [];

        if(isset($event['is_paid'])) {
            $result['paid_amount'] = $values['due_amount'];
        }

        return $result;
    }

    /**
     * Compute a Structured Reference using belgian SCOR (StructuredCommunicationReference) reference format.
     *
     * Note:
     *  format is aaa-bbbbbbb-XX
     *  where Xaaa is the prefix, bbbbbbb is the suffix, and XX is the control number, that must verify (aaa * 10000000 + bbbbbbb) % 97
     *  as 10000000 % 97 = 76
     *  we do (aaa * 76 + bbbbbbb) % 97
     */
    public static function _get_payment_reference($prefix, $suffix) {
        $a = intval($prefix);
        $b = intval($suffix);
        $control = ((76*$a) + $b ) % 97;
        $control = ($control == 0)?97:$control;
        return sprintf("%3d%04d%03d%02d", $a, $b / 1000, $b % 1000, $control);
    }
}