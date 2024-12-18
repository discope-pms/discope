<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use sale\booking\Booking;
use sale\booking\BookingLineGroup;

// announce script and fetch parameters values
list($params, $providers) = eQual::announce([
    'description'	=>	"Updates a sojourn based on partial patch of the main product. This script is meant to be called by the `booking/services` UI.",
    'params' 		=>	[
        'id' =>  [
            'description'       => 'Identifier of the targeted sojourn.',
            'type'              => 'many2one',
            'foreign_object'    => BookingLineGroup::getType(),
            'required'          => true
        ],
        'nb_pers' =>  [
            'type'              => 'integer',
            'description'       => 'New amount of participants (all ages).',
            'required'          => true
        ]
    ],
    'access' => [
        'visibility'        => 'protected',
        'groups'            => ['booking.default.user']
    ],
    'response' => [
        'content-type'      => 'application/json',
        'charset'           => 'utf-8',
        'accept-origin'     => '*'
    ],
    'providers' => ['context', 'orm']
]);

/**
 * @var \equal\php\Context          $context
 * @var \equal\orm\ObjectManager    $orm
 */
['context' => $context, 'orm' => $orm] = $providers;

if($params['nb_pers'] <= 0) {
    throw new Exception("invalid_value", EQ_ERROR_INVALID_PARAM);
}

// read BookingLineGroup object
$group = BookingLineGroup::id($params['id'])
    ->read([
        'id', 'is_extra', 'is_sojourn',
        'has_pack',
        'pack_id' => ['family_id'],
        'booking_id' => ['id', 'status']
    ])
    ->first(true);

if(!$group) {
    throw new Exception("unknown_sojourn", EQ_ERROR_UNKNOWN_OBJECT);
}

if(in_array($group['booking_id']['status'], ['invoiced', 'debit_balance', 'credit_balance', 'balanced'])) {
    throw new Exception("non_modifiable_booking", EQ_ERROR_NOT_ALLOWED);
}

// Callbacks are defined on Booking, BookingLine, and BookingLineGroup to ensure consistency across these entities.
// While these callbacks are useful for maintaining data integrity (they and are used in tests),
// they need to be disabled here to prevent recursive cycles that could lead to deep cycling issues.
$orm->disableEvents();


// special case for booking which price in not impacted by the nb_pers
if($group['booking_id']['status'] != 'quote') {
    // #memo - for GG, the number of persons does not impact the booking (GG only has pricing by_accomodation), so we allow change of nb_pers under specific circumstances
    // #todo - use a dedicated setting for the family_id to be exempted from nb_pers restriction
    if($group['is_sojourn'] && $group['has_pack'] && $group['pack_id']['family_id'] == 3) {
        BookingLineGroup::id($group['id'])
            ->update([
                'nb_pers' => $params['nb_pers']
            ]);
        // handle auto assignment of rental units (depending on center office prefs)
        BookingLineGroup::refreshRentalUnitsAssignments($orm, $group['id']);

        // re-create consumptions (if any, previous consumptions will be removed)
        $orm->call(Booking::getType(), 'createConsumptions', $params['id']);

        // #todo - we should also update the composition (what if already received or partially completed ?)
    }
    else {
        throw new Exception("non_modifiable_booking", EQ_ERROR_NOT_ALLOWED);
    }

}
// common case : change of nb_pers while booking is 'quote'
else {

    BookingLineGroup::id($group['id'])
        ->update([
            'nb_pers' => $params['nb_pers']
        ]);

    // #memo - this impacts autosales at booking level
    Booking::refreshNbPers($orm, $group['booking_id']['id']);
    // #memo - this might create new groups
    Booking::refreshAutosaleProducts($orm, $group['booking_id']['id']);
    // reset to a single age range
    BookingLineGroup::refreshAgeRangeAssignments($orm, $group['id']);
    // #memo - this might create new lines
    BookingLineGroup::refreshAutosaleProducts($orm, $group['id']);

    /*
        #memo - adapters depend on date_from, nb_nights, nb_pers, nb_children
            rate_class_id,
            center_id.discount_list_category_id,
            center_office_id.freebies_manual_assignment,
        and are applied both on group and each of its lines
    */
    BookingLineGroup::refreshPriceAdapters($orm, $group['id']);

    BookingLineGroup::refreshMealPreferences($orm, $group['id']);

    // refresh price_id, qty and price for all lines
    BookingLineGroup::refreshLines($orm, $group['id']);

    // handle auto assignment of rental units (depending on center office prefs)
    BookingLineGroup::refreshRentalUnitsAssignments($orm, $group['id']);

    BookingLineGroup::refreshPrice($orm, $group['id']);
    Booking::refreshPrice($orm, $group['booking_id']['id']);
}

// restore events in case this controller is chained with others
$orm->enableEvents();

$context->httpResponse()
        ->status(204)
        ->send();
