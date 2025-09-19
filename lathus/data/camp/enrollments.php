<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\http\HttpRequest;

[$params, $providers] = eQual::announce([
    'description'   => "Retrieve a batch of the latest enrollments, as provided from CPA Lathus API in response to ``.", // TODO: complete
    'params'        => [
    ],
    'access'        => [
        'visibility'    => 'protected',
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'auth']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\auth\AuthenticationManager   $auth
 */
['context' => $context, 'auth' => $auth] = $providers;

$entrypoint_url = "https://cpa-lathus-api.sc6nozo6393.universe.wf/api/reservations?_format=json&page=2"; // TODO: setting

$request = new HttpRequest('GET '.$entrypoint_url);
// TODO: handle ssl
//    'ssl' => [
//        'verify_peer'      => false,
//        'verify_peer_name' => false,
//    ]

$request->header('Content-Type', 'application/json');
$request->header('X-API-KEY', 'wyLpHY4yA'); // TODO: setting

$response = $request->send();

$status = $response->getStatusCode();
if($status != 200) {
    // upon request rejection, we stop the whole job
    throw new Exception('request_rejected', QN_ERROR_INVALID_PARAM);
}

$data = $response->body();

$context->httpResponse()
        ->body($data)
        ->send();
