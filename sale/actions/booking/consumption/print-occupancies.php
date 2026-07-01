<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

[$params, $providers] = eQual::announce([
    'description'   => "Render rooms occupancies.",
    'params'        => [
        'domain' => [
            'description'   => 'Criterias that results have to match (serie of conjunctions)',
            'type'          => 'array',
            'default'       => []
        ],
        'params' => [
            'type'          => 'array',
            'description'   => 'Additional params to relay to the data controller.',
            'default'       => []
        ]
    ],
    'access' => [
        'visibility'        => 'protected',
        'groups'            => ['booking.default.user'],
    ],
    'response'      => [
        'content-type'      => 'application/pdf',
        'accept-origin'     => '*'
    ],
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context  $context
 */
['context' => $context] = $providers;

$data = [
    'domain'    => $params['domain'] ?? [],
    'date_from' => strtotime('Monday this week'),
    'date_to'   => strtotime('Sunday this week')
];
if(isset($params['params']['date_from'])) {
    $date = new DateTime($params['params']['date_from']);
    $day_of_week = (int) $date->format('N');
    if($day_of_week !== 1) {
        $date->modify('last Monday');
    }
    $data['date_from'] = $date->getTimestamp();
    $data['date_to'] = $date->modify('next Sunday')->getTimestamp();
}
if(isset($params['params']['center_id'])) {
    $data['center_id'] = $params['params']['center_id'];
}
if(isset($params['params']['is_not_option'])) {
    if(gettype($params['params']['is_not_option']) === 'boolean') {
        $data['is_not_option'] = $params['params']['is_not_option'];
    }
    else {
        $data['is_not_option'] = in_array($params['params']['is_not_option'], ['true', '1']);
    }
}

$output = eQual::run('get', 'sale_booking_consumption_print-occupancies', $data);

$context->httpResponse()
        ->header('Content-Disposition', 'inline; filename="room_occupancies.pdf"')
        ->body($output)
        ->send();
