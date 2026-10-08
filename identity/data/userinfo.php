<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use identity\User;

[$params, $providers] = eQual::announce([
    'description'   => "Returns descriptor of current User, based on received access_token",
    'response'      => [
        'content-type'      => 'application/json',
        'charset'           => 'UTF-8',
        'accept-origin'     => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\orm\ObjectManager            $orm
 * @var \equal\auth\AuthenticationManager   $auth
 */
['context' => $context, 'orm' => $orm, 'auth' => $auth] = $providers;

// retrieve current User identifier (HTTP headers lookup through Authentication Manager)
$user_id = $auth->userId();
// make sure user is authenticated
if($user_id <= 0) {
    throw new Exception('user_unknown', EQ_ERROR_NOT_ALLOWED);
}

// request directly the mapper to bypass permission check on User class
$ids = $orm->search('identity\User', ['id', '=', $user_id]);
// make sure the User object is available
if(!count($ids)) {
    throw new Exception('unexpected_error', EQ_ERROR_INVALID_USER);
}

// user has always READ right on its own object
$user = User::id($user_id)
    ->read([
        'login',
        'language',
        'organisation_id',
        'groups_ids'        => ['name', 'display_name'],
        'identity_id'       => ['firstname', 'lastname'],
    ])
    ->adapt('json')
    ->first(true);

$user['groups'] = array_values(array_map(function ($a) {return $a['name'];}, $user['groups_ids']));

$context
    ->httpResponse()
    ->body($user)
    ->send();
