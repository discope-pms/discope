<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use support\TicketEntry;
use support\Ticket;

[$params, $providers] = eQual::announce([
    'description'   => 'Submit a ticket entry and mark it as \'sent\'.',
    'params'        => [
        'id' => [
            'type'          => 'integer',
            'description'   => 'Identifier of the ticket entry to submit (must be \'draft\').',
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
    'providers'     => ['context', 'auth']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\auth\AuthenticationManager   $auth
 */
['context' => $context, 'auth' => $auth] = $providers;

// retrieve the user making the submission
$user_id = $auth->userId();

$entry = TicketEntry::id($params['id'])->read(['id', 'creator', 'status', 'ticket_id'])->first();

if(!$entry) {
    throw new Exception('unknown_ticket_entry', EQ_ERROR_UNKNOWN);
}

if($entry['status'] != 'draft') {
    throw new Exception('invalid_status', EQ_ERROR_INVALID_PARAM);
}

TicketEntry::id($params['id'])->update(['status' => 'sent']);
$ticket = Ticket::id($entry['ticket_id'])->read(['creator', 'assignee_id'])->first();

if($ticket['creator'] == $user_id) {
    Ticket::id($entry['ticket_id'])->update(['status' => 'open']);
}
elseif(!isset($ticket['assignee_id']) || is_null($ticket['assignee_id'])) {
    Ticket::id($entry['ticket_id'])
        ->update(['assignee_id' => $user_id])
        ->update(['status' => 'pending']);
}
elseif($ticket['assignee_id'] == $entry['creator']) {
    Ticket::id($entry['ticket_id'])
        ->update(['status' => 'pending']);
}

$context
    ->httpResponse()
    ->status(205)
    ->send();
