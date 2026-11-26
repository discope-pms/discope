<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\php\Context;
use sale\booking\Booking;
use sale\booking\Funding;

[$params, $providers] = eQual::announce([
    'description'   => 'PoS search for Bookings, based on a given clue. Returns a collection of Booking candidates based on booking number of customer name.',
    'params'        => [
        'clue' => [
            'type'          => 'string',
            'description'   => 'The direction  (i.e. \'asc\' or \'desc\').',
            'default'       => 'desc'
        ],
        'domain' => [
            'type'          => 'array',
            'description'   => 'Criterias that results have to match (serie of conjunctions)',
            'default'       => []
        ],
        'limit' => [
            'type'          => 'integer',
            'description'   => 'The maximum number of results.',
            'min'           => 1,
            'max'           => 500,
            'default'       => 25
        ]
    ],
    'access'        => [
        'groups'        => ['booking.default.user']
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


$domain = $params['domain'];

if(strlen($params['clue']) > 0 && is_numeric($params['clue'])) {
    $domain[] = ['name', 'ilike', '%'.$params['clue'].'%'];
}

$result = [];

$bookings = Booking::search($domain, ['sort' => [ 'name' => 'asc']])
    ->read(['id', 'name', 'customer_id' => 'name'])
    ->adapt('json')
    ->get(true);

// additional filter on customer name, if necessary
if(strlen($params['clue']) > 0 && !is_numeric($params['clue'])) {
    foreach($bookings as $booking) {
        if(stripos($booking['customer_id']['name'], $params['clue']) !== false) {
            $result[] = $booking;
        }
    }
}
else {
    $result = $bookings;
}

$context
    ->httpResponse()
    ->body($result)
    ->send();
