<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

[$params, $providers] = eQual::announce([
    'description'   => "Render enrollments as a PDF document, one page per camp.",
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
$date_to = strtotime('Saturday this week');
$confirmed = true;
$validated = true;

if(!empty($params['params'])) {
    if(isset($params['params']['date_from'])) {
        $date_from = $adapter->adaptIn($params['params']['date_from'], 'datetime');
    }
    if(isset($params['params']['date_to'])) {
        $date_to = $adapter->adaptIn($params['params']['date_to'], 'datetime');
    }
    if(isset($params['params']['confirmed'])) {
        $confirmed = $params['params']['confirmed'];
    }
    if(isset($params['params']['validated'])) {
        $validated = $params['params']['validated'];
    }
}

$output = eQual::run('get', 'sale_camp_print-enrollments-list', compact('date_from', 'date_to', 'confirmed', 'validated'));

$context->httpResponse()
        ->header('Content-Disposition', 'inline; filename="enrollments-list.pdf"')
        ->body($output)
        ->send();
