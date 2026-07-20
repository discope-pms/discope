<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use discope\setting\Setting;
use sale\booking\Booking;
use sale\booking\BookingLine;
use sale\booking\BookingLineGroup;
use sale\booking\BookingLineGroupAgeRangeAssignment;

[$params, $providers] = eQual::announce([
    'description'	=>	"Update an age range assignment. This script is meant to be called by the `booking/services` UI.",
    'params' 		=> [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\booking\BookingLineGroup',
            'description'       => 'Identifier of the targeted sojourn.',
            'required'          => true
        ],
        'age_range_assignment_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\booking\BookingLineGroupAgeRangeAssignment',
            'description'       => 'Pack (product) the group relates to, if any.',
            'required'          => true
        ],
        'qty' => [
            'type'              => 'integer',
            'description'       => 'New amount of participants assigned to given ages.',
            'required'          => true
        ],
        'free_qty' => [
            'type'              => 'integer',
            'description'       => 'New amount of free participants assigned to given ages.',
            'default'           => 0
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

$group = BookingLineGroup::id($params['id'])
    ->read(['nb_pers', 'date_from', 'booking_id' => ['status', 'center_office_id']])
    ->first();

if($group['booking_id']['status'] !== 'checkedout') {
    throw new Exception("not_allowed", EQ_ERROR_NOT_ALLOWED);
}

$modify_checkedout_booking = Setting::get_value('sale', 'features', 'booking.modify_checkedout_booking', false, [
    ['center_office_id' => $group['booking_id']['center_office_id']],   // by center_office
    []                                                                  // fallback on global
]);
if(!$modify_checkedout_booking) {
    throw new Exception("not_allowed", EQ_ERROR_NOT_ALLOWED);
}

$age_range_assignment = BookingLineGroupAgeRangeAssignment::id($params['age_range_assignment_id'])
    ->read(['age_range_id', 'qty', 'free_qty'])
    ->first();

if(!$age_range_assignment) {
    throw new Exception("unknown_age_range_assignment", EQ_ERROR_UNKNOWN_OBJECT);
}

if($params['qty'] < 0) {
    throw new Exception("qty_negative_value", EQ_ERROR_INVALID_PARAM);
}

if($params['free_qty'] < 0) {
    throw new Exception("free_qty_negative_value", EQ_ERROR_INVALID_PARAM);
}

if($params['free_qty'] > $params['qty']) {
    throw new Exception("free_qty_greater_than_value", EQ_ERROR_INVALID_PARAM);
}

$events_mask = $orm->disableEvents();

$orm->update(BookingLineGroupAgeRangeAssignment::getType(), [$params['age_range_assignment_id']], [
    'qty'       => $params['qty'],
    'free_qty'  => $params['free_qty']
]);

BookingLineGroup::refreshNbPers($orm, $group['id']);
BookingLineGroup::refreshNbChildren($orm, $group['id']);

Booking::refreshNbPers($orm, $group['booking_id']['id']);

$bookingLines = BookingLine::search(['booking_line_group_id', '=', $group['id']])
    ->read(['product_id' => ['has_age_range', 'age_range_id']])
    ->get();

foreach($bookingLines as $booking_line_id => $bookingLine) {
    if(!$bookingLine['product_id']['has_age_range'] || $bookingLine['product_id']['age_range_id'] === $age_range_assignment['age_range_id']) {
        BookingLine::refreshQty($orm, $booking_line_id);
    }
}

$g = BookingLineGroup::id($params['id'])
    ->read(['booking_id', 'booking_lines_ids'])
    ->first();

foreach($g['booking_lines_ids'] as $line_id) {
    BookingLine::refreshPrice($orm, $line_id);
}

BookingLineGroup::refreshPrice($orm, $g['id']);
Booking::refreshPrice($orm, $g['booking_id']);

$orm->enableEvents($events_mask);

$context
    ->httpResponse()
    ->status(204)
    ->send();
