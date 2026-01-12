<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2026
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\booking\Booking;
use sale\customer\AgeRange;

[$params, $providers] = eQual::announce([
    'description'   => 'Lists all bookings with data needed to create summary sheets.',
    'params'        => [
        /*
            Filters
        */
        'date_from' => [
            'type'              => 'date',
            'description'       => "Date interval lower limit.",
            'default'           => strtotime('First day of next month')
        ],
        'date_to' => [
            'type'              => 'date',
            'description'       => "Date interval upper limit.",
            'default'           => strtotime('Last day of next month')
        ],

        /*
            Virtual entity
        */
        'name' => [
            'type'              => 'string',
            'description'       => 'Name of the booking.'
        ],
        'customer_name' => [
            'type'              => 'string',
            'description'       => 'Customer name.'
        ],
        'accounting_account' => [
            'type'              => 'string',
            'description'       => 'Accounting account of the customer of the booking.'
        ],
        'date_from_string' => [
            'type'              => 'string',
            'description'       => 'Start date of the booking formatted string d/m/Y.'
        ],
        'date_to_string' => [
            'type'              => 'string',
            'description'       => 'End date of the booking formatted string d/m/Y.'
        ],
        'rate_class' => [
            'type'              => 'string',
            'description'       => 'Rate class type of the customer.',
            'selection'         => [
                'classe_decouverte',
                'groupe_adulte',
                'sejour_adapte',
                'colonie',
                'clsh'
            ]
        ],
        'status' => [
            'type'              => 'string',
            'description'       => 'Status of the booking.'
        ],
        'customer_address_street' => [
            'type'              => 'string',
            'description'       => 'Address street of the customer.'
        ],
        'customer_address_zip' => [
            'type'              => 'string',
            'description'       => 'Address zip code of the customer.'
        ],
        'customer_address_city' => [
            'type'              => 'string',
            'description'       => 'Address city of the customer.'
        ],
        'customer_email' => [
            'type'              => 'string',
            'description'       => 'Email address of the customer.'
        ],
        'customer_phone' => [
            'type'              => 'string',
            'description'       => 'Phone number of the customer.'
        ],
        'contact_title' => [
            'type'              => 'string',
            'description'       => 'Title of the contact, if any.'
        ],
        'contact_lastname' => [
            'type'              => 'string',
            'description'       => 'Last name of the contact, if any.'
        ],
        'contact_firstname' => [
            'type'              => 'string',
            'description'       => 'First name of the contact, if any.'
        ],
        'contact_email' => [
            'type'              => 'string',
            'description'       => 'Email address of the contact, if any.'
        ],
        'contact_phone' => [
            'type'              => 'string',
            'description'       => 'Phone number of the contact, if any.'
        ],
        'contact_2_title' => [
            'type'              => 'string',
            'description'       => 'Title of the contact, if any.'
        ],
        'contact_2_lastname' => [
            'type'              => 'string',
            'description'       => 'Last name of the contact, if any.'
        ],
        'contact_2_firstname' => [
            'type'              => 'string',
            'description'       => 'First name of the contact, if any.'
        ],
        'contact_2_email' => [
            'type'              => 'string',
            'description'       => 'Email address of the contact, if any.'
        ],
        'contact_2_phone' => [
            'type'              => 'string',
            'description'       => 'Phone number of the contact, if any.'
        ],
        'first_meal' => [
            'type'              => 'string',
            'description'       => 'Description of the first meal of the sojourn.'
        ],
        'last_meal' => [
            'type'              => 'string',
            'description'       => 'Description of the last meal of the sojourn.'
        ],
        'nb_children' => [
            'type'              => 'string',
            'description'       => 'Quantity of children participating.'
        ],
        'nb_teachers' => [
            'type'              => 'string',
            'description'       => 'Quantity of teachers participating.'
        ],
        'nb_adults' => [
            'type'              => 'string',
            'description'       => 'Quantity of adults participating.'
        ],
        'nb_drivers' => [
            'type'              => 'string',
            'description'       => 'Quantity of drivers participating.'
        ],
        'kindergarten' => [
            'type'              => 'string',
            'description'       => 'Are the children in kindergarten.'
        ],
        'travel' => [
            'type'              => 'string',
            'description'       => 'Travel description.'
        ],
        'rental_units' => [
            'type'              => 'string',
            'description'       => 'List of rental units.'
        ]
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*',
        /*
        'cacheable'     => false,
        'cache-vary'    => ['body'],
        'expires'       => (60*60*1)
        */
    ],
    'providers'     => ['context', 'orm', 'adapt']
]);

/**
 * @var \equal\php\Context                      $context
 * @var \equal\orm\ObjectManager                $orm
 * @var \equal\data\adapt\DataAdapterProvider   $adapter_provider
 */
['context' => $context, 'orm' => $orm, 'adapt' => $adapter_provider] = $providers;

$bookings = Booking::search([
    ['date_from', '>=', $params['date_from']],
    ['date_from', '<=', $params['date_to']]
])
    ->read([
        'name',
        'date_from',  // jeudi 30 janvier 2025
        'date_to',
        'status',
        'customer_id' => [
            'rate_class_id' => [
                'code'
            ]
        ],
        'customer_identity_id' => [
            'display_name',
            'legal_name',
            'accounting_account',
            'address_street',
            'address_zip',
            'address_city',
            'email',
            'phone',
            'mobile'
        ],
        'contacts_ids' => [
            'type', // if booking
            'partner_identity_id' => [
                'type_id',
                'firstname',
                'lastname',
                'legal_name',
                'email',
                'phone',
                'mobile',
                'title'
            ]
        ],
        'booking_lines_groups_ids' => [
            'group_type', // if sojourn
            'age_range_assignments_ids' => [
                'age_range_id',
                'qty'
            ]
        ],
        'rental_unit_assignments_ids' => [
            'name',
            'is_accomodation'
        ]
    ])
    ->get(true);

// missing : "Repas 1er jour", "Repas dernier jour", "Accueil maternelle", "Déplacements", "Batiments", (contact 2)

$age_ranges = AgeRange::search()
    ->read(['name'])
    ->get();

$map_rate_classes = [
    '210' => 'classe_decouverte',
    '220' => 'groupe_adulte',
    '230' => 'sejour_adapte',
    '240' => 'colonie',
    '250' => 'clsh'
];

$result = [];

foreach($bookings as $id => $booking) {
    $customer_name = $booking['customer_identity_id']['display_name'];
    if($booking['customer_identity_id']['type_id'] !== 1) {
        $customer_name = $booking['customer_identity_id']['legal_name'];
    }

    $contact_booking = null;
    foreach($booking['contacts_ids'] as $contact) {
        if($contact['type'] !== 'booking' || $contact['partner_identity_id']['type_id'] !== 1) {
            continue;
        }
        $contact_booking = $contact;
        break;
    }

    $contact_second = null;
    foreach($booking['contacts_ids'] as $contact) {
        if($contact['id'] === $contact_booking['id'] || $contact['partner_identity_id']['type_id'] !== 1) {
            continue;
        }

        $contact_second = $contact;
        break;
    }

    $map_first_meal = [
        'avec PN et goûter amenés par leurs soins',
        'avec le PN amenés par leurs soins',
        'pour le déjeuner',
        'pour le dîner'
    ];

    $map_last_meal = [
        'avec collation pdj, PND et goûter à emporter',
        'avec PND et goûter à emporter',
        'avec PND à emporter',
        'avec le goûter à emporter',
        'après le petit déjeuner',
        'après le déjeuner',
        'après le goûter',
    ];

    // TODO: handle first and last meal

    $nb_children = 0;
    $nb_teachers = 0;
    $nb_adults = 0;
    $nb_drivers = 0;
    foreach($booking['booking_lines_groups_ids'] as $group) {
        if($group['group_type'] !== 'sojourn') {
            continue;
        }

        foreach($group['age_range_assignments_ids'] as $age_range_assignment) {
            switch($age_range_assignment['age_range_id']) {
                case 2:
                    $nb_children += $age_range_assignment['qty'];
                    break;
                case 7:
                    $nb_teachers += $age_range_assignment['qty'];
                    break;
                case 9:
                    $nb_drivers += $age_range_assignment['qty'];
                    break;
                case 10:
                    $nb_adults += $age_range_assignment['qty'];
                    break;
                default:
                    throw new Exception("not_handled_age_range", EQ_ERROR_INVALID_PARAM);
            }
        }
    }

    // TODO: handle travel

    $rental_units = array_map(fn($rental_unit) => $rental_unit['name'], $booking['rental_unit_assignments_ids']);

    $result[] = [
        'name'                      => $booking['name'],
        'customer_name'             => $customer_name,
        'accounting_account'        => $booking['customer_identity_id']['accounting_account'],
        'date_from_string'          => date('d/m/Y', $booking['date_from']),
        'date_to_string'            => date('d/m/Y', $booking['date_to']),
        'rate_class'                => $map_rate_classes[$booking['customer_id']['rate_class_id']['code']],
        'status'                    => $booking['status'],
        'customer_address_street'   => $booking['customer_identity_id']['address_street'],
        'customer_address_zip'      => $booking['customer_identity_id']['address_zip'],
        'customer_address_city'     => $booking['customer_identity_id']['address_city'],
        'customer_email'            => $booking['customer_identity_id']['email'],
        'customer_phone'            => !empty($booking['customer_identity_id']['phone']) ? $booking['customer_identity_id']['phone'] : $booking['customer_identity_id']['mobile'],
        'contact_title'             => !empty($contact_booking['partner_identity_id']['title']) ? str_replace(["Dr", "Ms", "Mrs", "Mr", "Pr"], ["Dr", "Melle", "Mme", "Mr", "Pr"], $contact_booking['partner_identity_id']['title']) : '',
        'contact_lastname'          => !empty($contact_booking['partner_identity_id']['lastname']) ? $contact_booking['partner_identity_id']['lastname'] : '',
        'contact_firstname'         => !empty($contact_booking['partner_identity_id']['firstname']) ? $contact_booking['partner_identity_id']['firstname'] : '',
        'contact_email'             => !empty($contact_booking['partner_identity_id']['email']) ? $contact_booking['partner_identity_id']['email'] : '',
        'contact_phone'             => !empty($contact_booking['partner_identity_id']['phone']) ? $contact_booking['partner_identity_id']['phone'] : $contact_booking['partner_identity_id']['mobile'],
        'contact_2_title'           => !empty($contact_second['partner_identity_id']['title']) ? str_replace(["Dr", "Ms", "Mrs", "Mr", "Pr"], ["Dr", "Melle", "Mme", "Mr", "Pr"], $contact_second['partner_identity_id']['title']) : '',
        'contact_2_lastname'        => !empty($contact_second['partner_identity_id']['lastname']) ? $contact_second['partner_identity_id']['lastname'] : '',
        'contact_2_firstname'       => !empty($contact_second['partner_identity_id']['firstname']) ? $contact_second['partner_identity_id']['firstname'] : '',
        'contact_2_email'           => !empty($contact_second['partner_identity_id']['email']) ? $contact_second['partner_identity_id']['email'] : '',
        'contact_2_phone'           => !empty($contact_second['partner_identity_id']['phone']) ? $contact_second['partner_identity_id']['phone'] : $contact_second['partner_identity_id']['mobile'],
        'first_meal'                => '', // TODO: handle first meal
        'last_meal'                 => '', // TODO: handle last meal
        'nb_children'               => $nb_children,
        'nb_teachers'               => $nb_teachers,
        'nb_adults'                 => $nb_adults,
        'nb_drivers'                => $nb_drivers,
        'kindergarten'              => '', // #todo - handle kindergarten
        'travel'                    => '', // TODO: handle travel
        'rental_units'              => implode(', ', $rental_units)
    ];
}

$context->httpResponse()
        ->header('X-Total-Count', count($result))
        ->body($result)
        ->send();

