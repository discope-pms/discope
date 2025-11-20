<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

[$params, $providers] = eQual::announce([
    'description'   => "Render camps activities and meals condensed planning as a PDF document.",
    'params'        => [
        'params' => [
            'type'          => 'array',
            'description'   => "Additional params to relay to the data controller.",
            'default'       => []
        ]
    ],
    'access' => [
        'visibility'        => 'protected',
        'groups'            => ['camp.default.user'],
    ],
    'response'      => [
        'content-type'      => 'application/pdf',
        'accept-origin'     => '*'
    ],
    'providers'     => ['context', 'adapt']
]);

/**
 * @var \equal\php\Context                      $context
 * @var \equal\data\adapt\DataAdapterProvider   $dap
 */
['context' => $context, 'adapt' => $dap] = $providers;

$adapter = $dap->get('json');

$date_from = strtotime('last Sunday');

if(!empty($params['params'])) {
    if(isset($params['params']['date_from'])) {
        $date_from = $adapter->adaptIn($params['params']['date_from'], 'datetime');
    }
}

$output = eQual::run('get', 'sale_camp_print-planning-condensed', ['date_from' => $date_from]);

$context->httpResponse()
        ->header('Content-Disposition', 'inline; filename="planning_condensed.pdf"')
        ->body($output)
        ->send();
