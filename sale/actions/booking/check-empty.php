<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\booking\Booking;
use sale\booking\BookingLine;
use sale\booking\Consumption;

[$params, $providers] = eQual::announce([
    'description'   => "Checks if there is at least one service attached to the given booking.",
    'params'        => [
        'id' => [
            'type'          => 'integer',
            'description'   => 'Identifier of the booking the check against emptyness.',
            'required'      => true
        ]
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'dispatch']
]);

/**
 * @var \equal\php\Context          $context
 * @var \equal\dispatch\Dispatcher  $dispatch
 */
['context' => $context, 'dispatch' => $dispatch] = $providers;

// ensure booking object exists and is readable
$booking = Booking::id($params['id'])->read(['id', 'name', 'center_office_id', 'booking_lines_ids', 'price', 'is_cancelled'])->first(true);

if(!$booking) {
    throw new Exception("unknown_booking", EQ_ERROR_UNKNOWN_OBJECT);
}

/*
    This controller is a check: an empty response means that no alert was raised
*/
$result = [];
$httpResponse = $context->httpResponse()->status(200);

// #memo - in some cases the booking price might result to a null value (ex. price with 100% discount : this is used for internal booking for the organization itself), so we cannot test ` $booking['price'] == 0`
if(!$booking['is_cancelled'] && !count($booking['booking_lines_ids'])) {
    $result[] = $booking['id'];

    // by convention we dispatch an alert that relates to the controller itself.
    $dispatch->dispatch('lodging.booking.empty', 'sale\booking\Booking', $params['id'], 'important', 'sale_booking_check-empty', ['id' => $params['id']], [], null, $booking['center_office_id']);

    $httpResponse->status(qn_error_http(EQ_ERROR_MISSING_PARAM));
}
else {
    // symmetrical removal of the alert (if any)
    $dispatch->cancel('lodging.booking.empty', 'sale\booking\Booking', $params['id']);
}

$httpResponse->body($result)
             ->send();
