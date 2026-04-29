<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\orm\Domain;
use identity\Center;
use sale\camp\Child;
use sale\camp\Enrollment;
use sale\camp\Guardian;
use sale\pay\Payment;

list($params, $providers) = eQual::announce([
    'description'   => 'Advanced search for the Funding: returns a collection of Reports according to extra parameters.',
    'extends'       => 'core_model_collect',
    'params'        => [
        'entity' =>  [
            'description'       => 'name',
            'type'              => 'string',
            'default'           => 'sale\pay\Funding'
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
         * Enrollment filters
         */
        'enrollment_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\camp\Enrollment',
            'description'       => 'Filter by enrollment.'
        ],
        'center_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'identity\Center',
            'description'       => 'The center to which the funding relates to.',
            'default'           => function() {
                return ($centers = Center::search())->count() === 1 ? current($centers->ids()) : null;
            }
        ],
        'enrollment_external_ref' => [
            'type'              => 'string',
            'description'       => 'The external ref of the funding enrollment.'
        ],
        'child_name' => [
            'type'              => 'string',
            'description'       => 'The name of the child concerned by the funding enrollment.'
        ],
        'guardian_name' => [
            'type'              => 'string',
            'description'       => 'The name of the guardian concerned by the funding enrollment.'
        ],
        'guardian_email' => [
            'type'              => 'string',
            'description'       => 'The email address of the guardian concerned by the funding enrollment.'
        ],
        /**
         * Payment filters
         */
        'payment_external_ref' => [
            'type'              => 'string',
            'description'       => 'The external ref of the funding payment.'
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

// Force domain on fundings linked to enrollments
$domain = Domain::conditionAdd($params['domain'], ['enrollment_id', '<>', null]);

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

if(isset($params['enrollment_id']) && $params['enrollment_id'] > 0) {
    $domain = Domain::conditionAdd($domain, ['enrollment_id', '=', $params['enrollment_id']]);
}

/**
 * Enrollment filters
 */

$enrollment_domain = [];
if(isset($params['enrollment_id']) && $params['enrollment_id'] > 0) {
    $enrollment_domain[] = ['id', '=', $params['enrollment_id']];
}
else {
    $guardian_domain = [];
    if(!empty($params['guardian_name'])) {
        $guardian_domain[] = ['name', 'ilike', "%{$params['guardian_name']}%"];
    }
    if(!empty($params['guardian_email'])) {
        $guardian_domain[] = ['email', 'ilike', "%{$params['guardian_email']}%"];
    }
    if(!empty($guardian_domain)) {
        $guardians = Guardian::search($guardian_domain)
            ->read(['children_ids'])
            ->get();

        $map_children_ids = [];
        foreach($guardians as $guardian) {
            foreach($guardian['children_ids'] as $child_id) {
                $map_children_ids[$child_id] = $child_id;
            }
        }

        $enrollment_domain[] = ['child_id', 'in', array_keys($map_children_ids)];
    }

    if(!empty($params['child_name'])) {
        $children_ids = Child::search(['name', 'ilike', "%{$params['child_name']}%"])->ids();
        $enrollment_domain[] = ['child_id', 'in', $children_ids];
    }
    
    if(isset($params['center_id']) && $params['center_id'] > 0) {
        $enrollment_domain[] = ['center_id', '=', $params['center_id']];
    }
    if(!empty($params['enrollment_external_ref'])) {
        $enrollment_domain[] = ['external_ref', '=', $params['enrollment_external_ref']];
    }
}

if(!empty($enrollment_domain)) {
    $enrollment_ids = Enrollment::search($enrollment_domain)->ids();
    $domain = Domain::conditionAdd($domain, ['enrollment_id', 'in', $enrollment_ids]);
}

/**
 * Payment filters
 */

if(!empty($params['payment_external_ref'])) {
    $payments = Payment::search(['external_ref', '=', $params['payment_external_ref']])
        ->read(['funding_id'])
        ->get();

    $map_fundings_ids = [];
    foreach($payments as $payment) {
        $map_fundings_ids[$payment['funding_id']] = true;
    }

    $domain = Domain::conditionAdd($domain, ['id', 'in', array_keys($map_fundings_ids)]);
}

$params['domain'] = $domain;
$result = eQual::run('get', 'model_collect', $params, true);

$context->httpResponse()
        ->body($result)
        ->send();
