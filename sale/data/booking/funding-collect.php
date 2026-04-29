<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\orm\Domain;
use identity\Center;
use identity\Identity;
use sale\booking\Booking;

list($params, $providers) = eQual::announce([
    'description'   => 'Advanced search for the Funding: returns a collection of Reports according to extra paramaters.',
    'extends'       => 'core_model_collect',
    'params'        => [
        'entity' =>  [
            'description'       => 'name',
            'type'              => 'string',
            'default'           => 'sale\booking\Funding'
        ],
        /**
         * Funding filters
         */
        'due_amount_min' => [
            'type'              => 'integer',
            'description'       => 'Minimal amount expected for the funding.'
        ],
        'due_amount_max' => [
            'type'              => 'integer',
            'description'       => 'Maximum amount expected for funding.'
        ],
        'payment_reference' => [
            'type'              => 'string',
            'description'       => 'Message for identifying the purpose of the transaction.'
        ],
        /**
         * Booking filters
         */
        'booking_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\booking\Booking',
            'description'       => 'Booking the invoice relates to.'
        ],
        'center_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'identity\Center',
            'description'       => 'The center to which the funding relates to.',
            'default'           => function() {
                return ($centers = Center::search())->count() === 1 ? current($centers->ids()) : null;
            }
        ],
        'booking_display_name' => [
            'type'              => 'string',
            'description'       => 'Name of the booking of the funding.'
        ],
        /**
         * Customer
         */
        'customer_accounting_account' => [
            'type'              => 'string',
            'description'       => 'Accounting account of the customer\'s identity.'
        ]
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => [ 'context', 'orm' ]
]);

/**
 * @var \equal\php\Context          $context
 * @var \equal\orm\ObjectManager    $orm
 */
['context' => $context, 'orm' => $orm] = $providers;

// Force domain on fundings linked to bookings
$domain = Domain::conditionAdd($params['domain'], ['booking_id', '<>', null]);

/**
 * Funding filters
 */

if(isset($params['due_amount_min']) && $params['due_amount_min'] > 0) {
    $domain = Domain::conditionAdd($domain, ['due_amount', '>=', $params['due_amount_min']]);
}

if(isset($params['due_amount_max']) && $params['due_amount_max'] > 0) {
    $domain = Domain::conditionAdd($domain, ['due_amount', '<=', $params['due_amount_max']]);
}

if(isset($params['payment_reference']) && strlen($params['payment_reference']) > 0 ) {
    $domain = Domain::conditionAdd($domain, ['payment_reference', 'like', '%'. $params['payment_reference'].'%']);
}

/**
 * Booking filters
 */

$booking_domain = [];
if(isset($params['booking_id']) && $params['booking_id'] > 0) {
    $booking_domain[] = ['booking_id', '=', $params['booking_id']];
    $domain = Domain::conditionAdd($domain, ['booking_id', '=', $params['booking_id']]);
}
else {
    if(!empty($params['customer_accounting_account'])) {
        $identities_ids = Identity::search(['accounting_account', 'like', "%{$params['customer_accounting_account']}%"])->ids();
        $booking_domain[] = ['customer_identity_id', 'in', $identities_ids];
    }

    if(isset($params['center_id']) && $params['center_id'] > 0) {
        $booking_domain[] = ['center_id', '=', $params['center_id']];
    }
    if(!empty($params['booking_display_name'])) {
        $booking_domain[] = ['display_name', 'ilike', "%{$params['booking_display_name']}%"];
    }
}

if(!empty($booking_domain)) {
    $booking_ids = Booking::search($booking_domain)->ids();
    $domain = Domain::conditionAdd($domain, ['booking_id', 'in', $booking_ids]);
}

$params['domain'] = $domain;
$result = eQual::run('get', 'model_collect', $params, true);

$context->httpResponse()
        ->body($result)
        ->send();
