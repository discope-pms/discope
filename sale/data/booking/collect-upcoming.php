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
use realestate\RentalUnit;
use sale\booking\Booking;
use sale\booking\Consumption;
use sale\booking\Contact;
use sale\booking\Funding;
use sale\booking\BankStatementLine;
use sale\booking\Payment;
use sale\booking\SojournProductModelRentalUnitAssignement;

[$params, $providers] = eQual::announce([
    'description'   => 'Advanced search for Bookings: returns a collection of upcoming Booking (week or month) according to extra parameters.',
    'extends'       => 'core_model_collect',
    'params'        => [
        'entity' =>  [
            'type'          => 'string',
            'description'   => 'Full name (including namespace) of the class to look into (e.g. \'core\\User\').',
            'default'       => 'sale\booking\Booking'
        ],

        'period_type' => [
            'type'          => 'string',
            'description'   => 'Upcoming week or month.',
            'selection'     => [
                'week',
                'month'
            ],
            'default'       => 'week'
        ],

        'bank_account_iban' => [
            'type'          => 'string',
            'usage'         => 'uri/urn:iban',
            'description'   => "Number of the bank account of the Identity, if any."
        ],

        'structured_message' => [
            'type'          => 'string',
            'description'   => "Structured message from bank statement."
        ],

        'display_name' => [
            'type'          => 'string',
            'description'   => "Complete name of the booking."
        ],

        'customer_accounting_account' => [
            'type'          => 'string',
            'description'   => "Accounting account of the Identity of the customer."
        ],

        'identity_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'identity\Identity',
            'domain'            => ["id", ">", 4],
            'description'       => 'Customer identity.'
        ],

        'center_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'identity\Center',
            'description'       => 'The center to which the booking relates to.',
            'default'           => function() {
                return ($centers = Center::search())->count() === 1 ? current($centers->ids()) : null;
            }
        ],

        'rental_unit_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'realestate\RentalUnit',
            'description'       => 'Rental unit on which to perform the search.'
        ],

        'has_tour_operator' => [
            'type'              => 'boolean',
            'description'       => 'Mark the booking as completed by a Tour Operator.'
        ],

        'tour_operator_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\customer\TourOperator',
            'domain'            => ['is_tour_operator', '=', true],
            'description'       => 'Mark the booking as completed by a Tour Operator.',
            'visible'           => ['has_tour_operator', '=', true]
        ],

        'tour_operator_ref' => [
            'type'              => 'string',
            'description'       => 'Specific reference, voucher code, or booking ID from the TO.',
            'visible'           => ['has_tour_operator', '=', true]
        ],

        'extref_reservation_id' => [
            'type'              => 'string',
            'description'       => 'Identifier of the reservation at Channel Manager side.'
        ],

        'email' => [
            'type'              => 'string',
            'description'       => 'Email of the contacts of the booking.'
        ],

        'rental_unit_category_id' => [
            'type'              => 'many2one',
            'description'       => "Category which current unit belongs to, if any.",
            'foreign_object'    => 'realestate\RentalUnitCategory'
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
 * @var \equal\php\Context $context
 * @var \equal\orm\ObjectManager $orm
 */
['context' => $context, 'orm' => $orm] = $providers;


/*
    Add conditions to the domain to consider advanced parameters
*/

$domain = $params['domain'];

$domain = Domain::conditionAdd($domain, ['date_from', '>', time()]);
if($params['period_type'] === 'week') {
    $domain = Domain::conditionAdd($domain, ['date_from', '<=', time() + (7 * 86400)]);
}
elseif($params['period_type'] === 'month') {
    $domain = Domain::conditionAdd($domain, ['date_from', '<=', time() + (31 * 86400)]);
}

$params['domain'] = $domain;

$result = eQual::run('get', 'sale_booking_collect', $params, true);


$context->httpResponse()
        ->body($result)
        ->send();
