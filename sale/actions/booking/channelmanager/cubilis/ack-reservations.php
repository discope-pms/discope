<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use discope\setting\Setting;
use equal\http\HttpRequest;
use sale\booking\channelmanager\Property;

[$params, $providers] = eQual::announce([
    'description'   => "Send an acknowledgment notification to Cubilis for a given reservation, using a `OTA_NotifReportRQ` request.",
    'params'        => [
        'property_id' => [
            'type'          => 'integer',
            'description'   => 'Identifier of the property (from Cubilis).',
            'required'      => true
        ],
        'reservations_ids' => [
            'type'          => 'array',
            'description'   => 'List of reservation IDs, as provided by Cubilis (ex. 44641530).',
            'help'          => "Identifier of the reservation is provided in the `OTA_HotelResRS` response, as `ResID_Value` attribute of the `HotelReservationID` node.",
            'required'      => true
        ]
    ],
    'access'        => [
        'visibility'    => 'protected'
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'constants'     => ['ROOT_APP_URL'],
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context $context
 */
['context' => $context] = $providers;

$channelmanager_enabled = Setting::get_value('sale', 'features', 'booking.channel_manager', false);

if(!$channelmanager_enabled) {
    throw new Exception('disabled_feature', EQ_ERROR_INVALID_CONFIG);
}

$client_domain = Setting::get_value('sale', 'organization', 'booking.channel_manager.client_domain', 'https://kaleo.discope.run');

// #memo - prevent calls from non-production server
if(constant('ROOT_APP_URL') != $client_domain) {
    throw new Exception('wrong_host', EQ_ERROR_INVALID_CONFIG);
}

// #memo - each we need the credentials from the Center
$property = Property::search(['extref_property_id', '=', $params['property_id']])->read(['id', 'username', 'password', 'api_id'])->first(true);

if(!$property) {
    throw new Exception('unknown_property', EQ_ERROR_UNKNOWN_OBJECT);
}

$xml = Property::cubilis_NotifReportRQ_generateXmlPayload($params['property_id'], $property['username'], $property['password'], $property['api_id'], $params['reservations_ids']);

$entrypoint_url = "https://cubilis.eu/plugins/PMS_ota/confirmreservations.aspx";

$request = new HttpRequest('POST '.$entrypoint_url);
$request->header('Content-Type', 'text/xml');

$response = $request->setBody($xml, true)->send();

$status = $response->getStatusCode();

if($status != 200) {
    throw new Exception('request_rejected', EQ_ERROR_INVALID_PARAM);
}

$context
    ->httpResponse()
    ->send();
