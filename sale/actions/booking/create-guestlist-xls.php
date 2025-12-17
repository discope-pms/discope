<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use documents\Document;
use sale\booking\Booking;

[$params, $providers] = eQual::announce([
    'description'   => "Create the booking's guest list xls document.",
    'params'        => [

        'id' =>  [
            'type'          => 'many2one',
            'description'   => "Identifier of the booking the guest list xls document should be created.",
            'required'      => true
        ],

        'document_name' => [
            'type'          => 'string',
            'description'   => "Name of the generated xls document."
        ],

        'lang' =>  [
            'type'          => 'string',
            'description'   => "Language to use for columns names.",
            'usage'         => 'language/iso-639',
            'default'       => constant('DEFAULT_LANG')
        ]

    ],
    'access'        => [
        'visibility'    => 'protected',
        'groups'        => ['booking.default.user']
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'constants'     => ['DEFAULT_LANG'],
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context  $context
 */
['context' => $context] = $providers;

$booking = Booking::id($params['id'])
    ->read(['name', 'guest_list_id'])
    ->first();

if(is_null($booking)) {
    throw new Exception("unknown_booking", EQ_ERROR_UNKNOWN_OBJECT);
}

if(is_null($booking['guest_list_id'])) {
    throw new Exception("no_guest_list", EQ_ERROR_INVALID_PARAM);
}

$guest_list_xls = eQual::run('get','model_export-xls', [
    'entity'        => 'sale\booking\GuestListItem',
    'view_id'       => 'list.default',
    'domain'        => ['guest_list_id', '=', $booking['guest_list_id']],
    'controller'    => 'model_collect',
    'lang'          => $params['lang'],
    'params'        => ['limit' => 500]
]);

$document_name = "Guests list of booking {$booking['name']}";
if(!empty($params['document_name'])) {
    $document_name = $params['document_name'];
}
if(!str_ends_with($document_name, '.xlsx')) {
    $document_name .= '.xlsx';
}

$document = Document::create([
    'name' => $document_name,
    'data' => $guest_list_xls,
    'type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
])
    ->read(['id'])
    ->first();

$context->httpResponse()
        ->body(['document_id' => $document['id']])
        ->status(201)
        ->send();
