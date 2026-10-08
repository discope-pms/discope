<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2026
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use identity\Identity;

[$params, $providers] = eQual::announce([
    'description'   => "Identify and mark duplicate identities.",
    'help'          => "If no \"id\" is provided, the action will affect all identities that have not yet been evaluated for duplication.",
    'params'        => [
        'id' => [
            'type'          => 'integer',
            'description'   => "The identifier of the identity for which we want to reset the duplicate information.",
            'default'       => null
        ]
    ],
    'access'        => [
        'visibility' => 'private'
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm']
]);

/**
 * @var \equal\php\Context $context
 */
['context' => $context] = $providers;

if(!is_null($params['id'])) {
    Identity::id($params['id'])->do('re-calc-is-duplicate');
}
else {
    $start = 0;
    $limit = 1000;

    do {
        $identities = Identity::search(
            [],
            [
                'sort'  => ['id' => 'asc'],
                'start' => $start,
                'limit' => $limit
            ])
            ->read(['is_duplicate'])
            ->get();

        $start += $limit;
    } while(count($identities) === $limit);
}

$context
    ->httpResponse()
    ->status(204)
    ->send();
