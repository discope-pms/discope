<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use core\setting\Setting;
use sale\booking\Booking;
use sale\booking\BookingLine;
use sale\booking\BookingLineGroup;

[$params, $providers] = eQual::announce([
    'description'	=>	"Update booking line qty of checkedout booking. This script is meant to be called by the `booking/services` UI.",
    'params' 		=> [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\booking\BookingLine',
            'description'       => 'Identifier of the targeted booking line.',
            'required'          => true
        ],
        'qty' => [
            'type'              => 'float',
            'description'       => 'New amount of participants assigned to given ages.',
            'required'          => true
        ]
    ],
    'access'        => [
        'visibility'    => 'protected',
        'groups'        => ['booking.default.user']
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm']
]);

/**
 * @var \equal\php\Context          $context
 * @var \equal\orm\ObjectManager    $orm
 */
['context' => $context, 'orm' => $orm] = $providers;

$modify_checkedout_booking = Setting::get_value('sale', 'features', 'booking.modify_checkedout_booking', false);

if(!$modify_checkedout_booking) {
    throw new Exception("checked_out_booking_not_allowed", EQ_ERROR_NOT_ALLOWED);
}

$line = BookingLine::id($params['id'])
    ->read(['booking_line_group_id', 'booking_id' => ['status']])
    ->first();

if($line['booking_id']['status'] !== 'checkedout') {
    throw new Exception("non_checked_out_booking", EQ_ERROR_NOT_ALLOWED);
}

if($params['qty'] < 0) {
    throw new Exception("qty_negative_value", EQ_ERROR_INVALID_PARAM);
}

$events_mask = $orm->disableEvents();

$orm->update(BookingLine::getType(), [$line['id']], ['qty' => $params['qty']]);

BookingLine::refreshPrice($orm, $line['id']);
BookingLineGroup::refreshPrice($orm, $line['booking_line_group_id']);
Booking::refreshPrice($orm, $line['booking_id']['id']);

$orm->enableEvents($events_mask);

$context
    ->httpResponse()
    ->status(204)
    ->send();
