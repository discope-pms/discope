<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2024
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\booking\Booking;

list($params, $providers) = eQual::announce([
    'description'   => 'Returns descriptor of current User, based on received guest_access_token (no user_id).',
    'response'      => [
        'content-type'      => 'application/json',
        'charset'           => 'UTF-8',
        'accept-origin'     => '*',
        'errors'            => ['invalid_token', 'malformed_token', 'expired_token']
    ],
    'constants'     => ['AUTH_SECRET_KEY', 'DEFAULT_LANG'],
    'access'        => [
        'visibility'    => 'public'
    ],
    'providers'     => ['context', 'auth']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\auth\AuthenticationManager   $auth
 */
['context' => $context, 'auth' => $auth] = $providers;

$request = $context->getHttpRequest();
$jwt = $request->cookie('guest_access_token');
$check = $auth->verifyToken($jwt, constant('AUTH_SECRET_KEY'));

if($check === false || $check <= 0) {
    throw new Exception('invalid_token', EQ_ERROR_NOT_ALLOWED);
}

$token = $auth->decodeToken($jwt);
$payload = $token['payload'];

if(!isset($payload['exp']) || !isset($payload['booking_id']) || !isset($payload['email'])) {
    throw new Exception('malformed_token', EQ_ERROR_NOT_ALLOWED);
}

if($payload['exp'] < time()) {
    throw new Exception('expired_token', QN_ERROR_INVALID_USER);
}

$identity_fields = ['email', 'lang_id' => ['code']];

$booking = Booking::id($payload['booking_id'])
    ->read([
        'customer_identity_id' => $identity_fields,
        'contacts_ids' => [
            'partner_identity_id' => $identity_fields
        ]
    ])
    ->first();

$lang = constant('DEFAULT_LANG');
if($booking['customer_identity_id']['email'] === $payload['email']) {
    $lang = $booking['customer_identity_id']['lang_id']['code'];
}
else {
    foreach($booking['contacts_ids'] as $contact) {
        if($contact['email'] === $payload['email']) {
            $lang = $contact['lang_id']['code'];
        }
    }
}

$user = [
    'booking_id'    => $payload['booking_id'],
    'email'         => $payload['email'],
    'lang'          => $lang
];

$context->httpResponse()
        ->body($user)
        ->send();
