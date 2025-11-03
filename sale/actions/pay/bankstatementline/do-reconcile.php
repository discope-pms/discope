<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\booking\BankStatementLine;
use sale\booking\Booking;

[$params, $providers] = eQual::announce([
    'description'   => "Attempt to reconcile a BankStatementLine using its communication (VCS or free-text message).",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the BankStatementLine to reconcile.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ],
    ],
    'access' => [
        'visibility'        => 'protected',
        'groups'            => ['finance.default.user', 'sale.default.user'],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'dispatch']
]);

/**
 * @var \equal\php\Context          $context
 * @var \equal\orm\ObjectManager    $orm
 * @var \equal\dispatch\Dispatcher  $dispatch
 */
['context' => $context, 'orm' => $orm, 'dispatch' => $dispatch] = $providers;

$line = BankStatementLine::id($params['id'])
    ->read(['structured_message', 'message' , 'amount', 'center_office_id', 'payments_ids' => ['amount']])
    ->first(true);

if(is_null($line)) {
    throw new Exception("unknown_bank_statement_line", EQ_ERROR_UNKNOWN_OBJECT);
}

if($line['status'] === 'reconciled') {
    throw new Exception("already_reconciled", EQ_ERROR_INVALID_PARAM);
}

$total_paid = 0.0;
foreach($line['payments_ids'] as $payment) {
    $total_paid += $payment['amount'];
}

$already_paid = abs(round($total_paid, 2)) >= abs(round($line['amount'], 2));

if($already_paid) {
    // #memo - mark as reconciled, allows to handle bug "line back to pending status after CODA import"
    $orm->update(BankStatementLine::getType(), $line['id'], ['status' => 'reconciled']);
}
else {
    // reconcile
    if($line['amount'] < 0 && $line['structured_message']) {
        throw new Exception('invalid_bankStatementLine', EQ_ERROR_INVALID_PARAM);
    }

    $booking_name = substr($line['structured_message'], 4, 6);
    $booking_extref_id = preg_replace('/[^0-9]/', '', $line['message']);

    $format_structured_message = '/^\+\+\+\d{3}\/\d{4}\/\d{4}\+\+\+$/';
    if (empty($line['structured_message']) && isset($line['message']) && preg_match($format_structured_message, $line['message'])) {
        $booking_name = substr(preg_replace('/[^0-9]/', '', $line['message']), 3, 6);
    }

    $booking_before = Booking::search([[['name', '=', $booking_name]], [['extref_reservation_id', '=', $booking_extref_id]]])
        ->read(['id', 'status', 'payment_reference', 'center_office_id'])
        ->first(true);

    if($booking_before) {
        if($line['structured_message'] && ($booking_before['payment_reference'] != $line['structured_message'])){
            throw new Exception('invalid_structured_message', EQ_ERROR_INVALID_PARAM);
        }

        // prevent assigning a statement line from an office bank account to a reservation of another office
        if($booking_before['center_office_id'] != $line['center_office_id']) {
            throw new Exception('invalid_center_office', EQ_ERROR_INVALID_PARAM);
        }
    }

    $orm->call(BankStatementLine::getType(), 'reconcile', (array) $params['id']);

    if($booking_before) {
        $booking_after = Booking::id($booking_before['id'])
            ->read(['status'])
            ->first(true);

        if($booking_before['status'] != $booking_after['status']) {
            $dispatch->dispatch('lodging.booking.payment.overpaid', 'sale\booking\Booking', $booking_before['id'], 'important', null, [], [], null, $booking_before['center_office_id']);
        }
        else{
            $dispatch->cancel('lodging.booking.payment.overpaid', 'sale\booking\Booking', $booking_before['id']);
        }
    }
}

$context->httpResponse()
        ->status(204)
        ->send();
