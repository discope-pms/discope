<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\booking\PartnerEvent;

[$params, $providers] = eQual::announce([
    'description'   => "Removes a partner event.",
    'params'        => [

        'id' =>  [
            'type'          => 'many2one',
            'description'   => "Identifier of the targeted partner event.",
            'min'           => 1,
            'required'      => true
        ]

    ],
    'access'        => [
        'visibility'    => 'protected',
        'groups'        => ['booking.default.user', 'camp.default.user'],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context  $context
 */
['context' => $context] = $providers;

$partner_event = PartnerEvent::id($params['id'])
    ->read(['camp_id'])
    ->first();

if(is_null($partner_event)) {
    throw new Exception("unknown_partner_event", EQ_ERROR_UNKNOWN_OBJECT);
}

if(!is_null($partner_event['camp_id'])) {
    throw new Exception("not_allowed", EQ_ERROR_NOT_ALLOWED);
}

PartnerEvent::id($partner_event['id'])->delete();

$context->httpResponse()
        ->status(205)
        ->send();
