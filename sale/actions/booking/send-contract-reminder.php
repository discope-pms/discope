<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use equal\email\Email;
use equal\email\EmailAttachment;

use core\Mail;
use communication\TemplateAttachment;
use documents\Document;
use sale\booking\Funding;
use sale\booking\Booking;
use sale\booking\Contract;
use core\setting\Setting;

// announce script and fetch parameters values
list($params, $providers) = announce([
    'description'	=>	"Send an instant email with given details with a booking contract as attachment.",
    'params' 		=>	[
        'contract_id' => [
            'description'   => 'Contract related to the sending of the email.',
            'type'          => 'integer',
            'required'      => true
        ],
        'title' =>  [
            'description'   => 'Title of the message.',
            'type'          => 'string',
            'required'      => true
        ],
        'message' => [
            'description'   => 'Body of the message.',
            'type'          => 'string',
            'usage'         => 'text/html',
            'required'      => true
        ],
        'sender_email' => [
            'description'   => 'Email address FROM.',
            'type'          => 'string',
            'usage'         => 'email',
            'required'      => true
        ],
        'recipient_email' => [
            'description'   => 'Email address TO.',
            'type'          => 'string',
            'usage'         => 'email',
            'required'      => true
        ],
        'recipients_emails' => [
            'description'   => 'CC email addresses.',
            'type'          => 'array',
            // #todo - wait for support for "array of" usage
            // 'usage'         => 'email'
        ],
        'lang' =>  [
            'description'   => 'Language for multilang contents (2 letters ISO 639-1).',
            'type'          => 'string',
            'default'       => constant('DEFAULT_LANG')
        ]
    ],
    'constants'             => ['DEFAULT_LANG'],
    'access' => [
        'groups'            => ['booking.default.user'],
    ],
    'response'      => [
        'content-type'      => 'application/json',
        'charset'           => 'utf-8',
        'accept-origin'     => '*'
    ],
    'providers'     => ['context', 'dispatch']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\dispatch\Dispatcher          $dispatch
 */
list($context, $dispatch) = [ $providers['context'], $providers['dispatch']];

$contract = Contract::id($params['contract_id'])
    ->read([
        'booking_id' => ['id', 'center_id' => ['id', 'center_office_id' => ['email_bcc']], 'has_contract', 'contracts_ids']
    ])
    ->first(true);

if(!$contract) {
    throw new Exception("unknown_funding", QN_ERROR_UNKNOWN_OBJECT);
}

$booking = $contract['booking_id'];

if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

if(!$booking['has_contract'] || empty($booking['contracts_ids'])) {
    throw new Exception("incompatible_status", QN_ERROR_INVALID_PARAM);
}

// by convention the most recent contract is listed first (see schema in sale/classes/booking/Booking.class.php)
$contract_id = array_shift($booking['contracts_ids']);

// generate attachment
$attachment = eQual::run('get', 'sale_booking_print-contract', [
    'id'        => $params['contract_id'] ,
    'view_id'   =>'print.default',
    'lang'      => $params['lang'],
    'mode'      => $params['mode']
]);

// #todo - store these terms in i18n
$main_attachment_name = 'contract';
switch(substr($params['lang'], 0, 2)) {
    case 'fr': $main_attachment_name = 'contrat';
        break;
    case 'nl': $main_attachment_name = 'contract';
        break;
    case 'en': $main_attachment_name = 'contract';
        break;
}

// generate signature
$signature = '';
try {
    $data = eQual::run('get', 'identity_center-signature', [
        'center_id'     => $booking['center_id']['id'],
        'lang'          => $params['lang']
    ]);
    $signature = (isset($data['signature']))?$data['signature']:'';
}
catch(Exception $e) {
    // ignore errors
}

$params['message'] .= $signature;

/** @var EmailAttachment[] */
$attachments = [];

// push main attachment
$attachments[] = new EmailAttachment($main_attachment_name.'.pdf', (string) $attachment, 'application/pdf');

// create message
$message = new Email();
$message->setTo($params['recipient_email'])
        ->setReplyTo($params['sender_email'])
        ->setSubject($params['title'])
        ->setContentType("text/html")
        ->setBody($params['message']);

$bcc = isset($booking['center_id']['center_office_id']['email_bcc'])?$booking['center_id']['center_office_id']['email_bcc']:'';

if(strlen($bcc)) {
    $message->addBcc($bcc);
}

if(isset($params['recipients_emails'])) {
    $recipients_emails = array_diff($params['recipients_emails'], (array) $params['recipient_email']);
    foreach($recipients_emails as $address) {
        $message->addCc($address);
    }
}

// append attachments to message
foreach($attachments as $attachment) {
    $message->addAttachment($attachment);
}

// queue message
Mail::queue($message, 'sale\booking\Booking', $booking['id']);
$dispatch->cancel('lodging.booking.contract.reminder.sent', 'sale\booking\Booking', $booking['id']);

$context->httpResponse()
        ->status(204)
        ->send();
