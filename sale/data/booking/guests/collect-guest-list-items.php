<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2026
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\orm\Domain;
use sale\booking\Booking;

[$params, $providers] = eQual::announce([
    'description'   => 'Advanced search for guest list items: returns a collection of GuestListItem according to extra parameters.',
    'extends'       => 'core_model_collect',
    'params'        => [
        'entity' => [
            'type'              => 'string',
            'description'       => 'Full name (including namespace) of the class to return.',
            'default'           => 'sale\booking\GuestListItem'
        ],
        'date_from' => [
            'type'              => 'date',
            'description'       => 'Date interval lower limit.',
            'default'           => fn() => strtotime('Monday this week')
        ],
        'date_to' => [
            'type'              => 'date',
            'description'       => 'Date interval upper limit.',
            'default'           => fn() => strtotime('Sunday this week')
        ],
        'center_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'identity\Center',
            'description'       => 'The center the guest list items must be part of..'
        ]
    ],
    'access' => [
        'visibility'    => 'protected',
        'groups'        => ['booking.guestlist.user'],
        'level'         => 2
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context $context
 */
['context' => $context] = $providers;

$bookings_domain = [
    ['status', 'not in', ['quote', 'option', 'cancelled']],
    ['is_cancelled', '=', false],
    ['date_from', '>=', $params['date_from']],
    ['date_from', '<=', $params['date_to']]
];
if(isset($params['center_id'])) {
    $bookings_domain[] = ['center_id', '=', $params['center_id']];
}

$bookings_ids = Booking::search($bookings_domain)->ids();

if(!empty($bookings_ids)) {
    $domain = Domain::conditionAdd($params['domain'], ['booking_id', 'in', $bookings_ids]);
}
else {
    // force empty response
    $domain = Domain::conditionAdd($params['domain'], ['booking_id', '=', 0]);
}

$params['domain'] = $domain;

$result = eQual::run('get', 'model_collect', $params, true);

usort($result, function($a, $b) {
    if($a['center_id']['name'] !== $b['center_id']['name']) {
        return $a['center_id']['name'] <=> $b['center_id']['name'];
    }

    if($a['booking_id']['name'] !== $b['booking_id']['name']) {
        return $a['booking_id']['name'] <=> $b['booking_id']['name'];
    }

    if($a['booking_line_group_id']['name'] !== $b['booking_line_group_id']['name']) {
        return $a['booking_line_group_id']['name'] <=> $b['booking_line_group_id']['name'];
    }

    if($a['lastname'] !== $b['lastname']) {
        return $a['lastname'] <=> $b['lastname'];
    }

    return $a['firstname'] <=> $b['firstname'];
});

$context
    ->httpResponse()
    ->body($result)
    ->send();

