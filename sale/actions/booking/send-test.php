<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\email\Email;

// announce script and fetch parameters values
[$params, $providers] = eQual::announce([
    'description'   => "Send an instant email with given details with a booking quote as attachment.",
    'params'        => [],
    'access'        => [
        'groups'        => ['booking.default.user']
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'constants'     => ['TEST'],
    'providers'     => ['context']
]);


// init local vars with inputs
/**
 * @var \equal\php\Context $context
 */
['context' => $context] = $providers;

echo constant('TEST');


// create message
$message = new Email();
$message->setTo('cedricfrancoys@gmail.com')
        ->setReplyTo('reception@kaleo-asbl.be')
        ->setSubject('test')
        ->setContentType("text/html")
        ->setBody('<html><body>test lorem ipsum</body></html>');


// queue message
// Mail::queue($message);



$context
    ->httpResponse()
    ->status(204)
    ->send();
