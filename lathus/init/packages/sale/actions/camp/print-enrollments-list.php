<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
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
        ],
        'by_sojourn' => [
            'type'          => 'boolean',
            'description'   => "Separate the enrollments by sojourn.",
            'default'       => false
        ],
        'by_location' => [
            'type'          => 'boolean',
            'description'   => "Separate the enrollments by location.",
            'default'       => false
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
$sojourn_number = '';
$only_saturday = false;
$only_weekend = false;
$confirmed = true;
$validated = true;

if(!empty($params['params'])) {
    if(isset($params['params']['date_from'])) {
        $date_from = $adapter->adaptIn($params['params']['date_from'], 'datetime');
    }
    if(!empty($params['params']['sojourn_number'])) {
        $sojourn_number = $params['params']['sojourn_number'];
    }
    if(isset($params['params']['only_saturday'])) {
        $only_saturday = $params['params']['only_saturday'];
    }
    if(isset($params['params']['only_weekend'])) {
        $only_weekend = $params['params']['only_weekend'];
    }
    if(isset($params['params']['confirmed'])) {
        $confirmed = $params['params']['confirmed'];
    }
    if(isset($params['params']['validated'])) {
        $validated = $params['params']['validated'];
    }
}

$values = compact('date_from', 'sojourn_number', 'only_saturday', 'only_weekend', 'confirmed', 'validated');
if($params['by_sojourn']) {
    $output = eQual::run('get', 'sale_camp_print-enrollments-list-by-sojourn', $values);
}
elseif($params['by_location']) {
    $output = eQual::run('get', 'sale_camp_print-enrollments-list-by-location', $values);
}
else {
    $output = eQual::run('get', 'sale_camp_print-enrollments-list', $values);
}

$context->httpResponse()
        ->header('Content-Disposition', 'inline; filename="enrollments-list.pdf"')
        ->body($output)
        ->send();
