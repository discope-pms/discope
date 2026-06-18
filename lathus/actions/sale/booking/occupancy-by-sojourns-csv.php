<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

[$params, $providers] = eQual::announce([
    'description'   => "Provides data about current Centers capacities (according to configuration).",
    'params'        => [
        'params' => [
            'description'   => 'Additional params to relay to the data controller.',
            'type'          => 'array',
            'default'       => []
        ]
    ],
    'response'      => [
        'content-type'          => 'text/csv',
        'content-disposition'   => 'inline; filename="occupations_batiments.csv"',
        'charset'               => 'utf-8',
        'accept-origin'         => '*'
    ],
    'providers'     => ['context', 'adapt']
]);

/**
 * @var \equal\php\Context  $context
 */
['context' => $context, 'adapt' => $dap] = $providers;

$adapter = $dap->get('json');

$output = eQual::run('get', 'lathus_sale_booking_occupancy-by-sojourns-csv', [
    'center_id' => $params['params']['center_id'],
    'date_from' => $adapter->adaptIn($params['params']['date_from'], 'date'),
    'date_to'   => $adapter->adaptIn($params['params']['date_to'], 'date')
]);

$context
    ->httpResponse()
    ->body($output)
    ->send();
