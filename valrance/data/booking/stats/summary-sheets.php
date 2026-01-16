<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2026
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\booking\Booking;
use sale\booking\BookingLine;
use sale\booking\BookingMeal;
use sale\catalog\Product;
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
        'date_from',
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
            'type',
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
            'group_type',
            'age_range_assignments_ids' => [
                'age_range_id',
                'qty'
            ],
            'booking_line_group_attributes_ids' => [
                'code'
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

    /*
     * Customer name
     */

    $customer_name = $booking['customer_identity_id']['display_name'];
    if($booking['customer_identity_id']['type_id'] !== 1) {
        $customer_name = $booking['customer_identity_id']['legal_name'];
    }

    /*
     * Contacts
     */

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

    /*
     * First day meal description
     */

    $first_day_meals = BookingMeal::search([
        ['booking_id', '=', $booking['id']],
        ['date', '=', $booking['date_from']]
    ])
        ->read([
            'is_self_provided',
            'time_slot_id'      => ['code'],
            'meal_type_id'      => ['code']
        ])
        ->get();

    $first_day_meals_config = [
        'has_breakfast'     => false,
        'has_lunch'         => false,
        'has_diner'         => false,
        'has_snack'         => false,
        'is_lunch_picnic'   => false
    ];

    foreach($first_day_meals as $meal_id => $meal) {
        if($meal['time_slot_id']['code'] === 'B' && !$meal['is_self_provided']) {
            $first_day_meals_config['has_breakfast'] = true;
        }
        elseif($meal['time_slot_id']['code'] === 'L') {
            if(!$meal['is_self_provided']) {
                $first_day_meals_config['has_lunch'] = true;
            }
            if($meal['meal_type_id']['code'] === 'picnic') {
                $first_day_meals_config['is_lunch_picnic'] = true;
            }
        }
        elseif($meal['time_slot_id']['code'] === 'D' && !$meal['is_self_provided']) {
            $first_day_meals_config['has_diner'] = true;
        }
        elseif($meal['time_slot_id']['code'] === 'PM' && !$meal['is_self_provided']) {
            $first_day_meals_config['has_snack'] = true;
        }
    }

    $first_meal_description = '';
    if($first_day_meals_config['has_breakfast']) {
        $first_meal_description = 'pour le petit-déjeuner';
    }
    elseif($first_day_meals_config['has_lunch']) {
        $first_meal_description = 'pour le déjeuner';
    }
    elseif($first_day_meals_config['has_snack']) {
        $first_meal_description = 'pour le goûter';
    }
    elseif($first_day_meals_config['has_diner']) {
        $first_meal_description = 'pour le dîner';
    }
    else {
        $first_meal_description = 'pour la nuitée';
    }

    if($first_day_meals_config['is_lunch_picnic']) {
        $first_meal_description .= ', ';

        if($first_day_meals_config['has_lunch']) {
            if($first_day_meals_config['has_snack']) {
                $first_meal_description .= 'avec pique-nique et goûter fournis par le Relais Valrance';
            }
            else {
                $first_meal_description .= 'avec pique-nique fourni par le Relais Valrance';
            }
        }
        else {
            if($first_day_meals_config['has_snack']) {
                $first_meal_description .= 'avec pique-nique amenés par vos soins et goûter fourni par le Relais Valrance';
            }
            else {
                $first_meal_description .= 'avec pique-nique et goûter amenés par vos soins';
            }
        }
    }

    /*
     * Last day meal description
     */

    $last_day_meals = BookingMeal::search([
        ['booking_id', '=', $booking['id']],
        ['date', '=', $booking['date_to']]
    ])
        ->read([
            'is_self_provided',
            'time_slot_id'      => ['code'],
            'meal_place_id'     => ['place_type']
        ])
        ->get();

    $last_day_meals_config = [
        'has_breakfast'         => false,
        'has_lunch'             => false,
        'has_diner'             => false,
        'has_snack'             => false,
        'is_breakfast_offsite'  => false,
        'is_lunch_offsite'      => false,
        'is_snack_offsite'      => false,
        'is_diner_offsite'      => false
    ];

    foreach($last_day_meals as $meal_id => $meal) {
        $offsite = in_array($meal['meal_place_id']['place_type'], ['offsite', 'auto']);
        if($meal['time_slot_id']['code'] === 'B' && !$meal['is_self_provided']) {
            $last_day_meals_config['has_breakfast'] = true;
            $last_day_meals_config['is_breakfast_offsite'] = $offsite;
        }
        elseif($meal['time_slot_id']['code'] === 'L' && !$meal['is_self_provided']) {
            $last_day_meals_config['has_lunch'] = true;
            $last_day_meals_config['is_lunch_offsite'] = $offsite;
        }
        elseif($meal['time_slot_id']['code'] === 'PM' && !$meal['is_self_provided']) {
            $last_day_meals_config['has_snack'] = true;
            $last_day_meals_config['is_snack_offsite'] = $offsite;
        }
        elseif($meal['time_slot_id']['code'] === 'D' && !$meal['is_self_provided']) {
            $last_day_meals_config['has_diner'] = true;
            $last_day_meals_config['is_diner_offsite'] = $offsite;
        }
    }

    $last_meal_description = '';
    if($last_day_meals_config['has_diner'] && !$last_day_meals_config['is_diner_offsite']) {
        $last_meal_description .= 'après le dîner';
    }
    elseif($last_day_meals_config['has_snack'] && !$last_day_meals_config['is_snack_offsite']) {
        $last_meal_description .= 'après le goûter';
    }
    elseif($last_day_meals_config['has_lunch'] && !$last_day_meals_config['is_lunch_offsite']) {
        $last_meal_description .= 'après le déjeuner';
    }
    elseif($last_day_meals_config['has_breakfast'] && !$last_day_meals_config['is_breakfast_offsite']) {
        $last_meal_description .= 'après le petit-déjeuner';
    }

    if($last_day_meals_config['has_breakfast'] && $last_day_meals_config['is_breakfast_offsite']) {
        if(strlen($last_meal_description) > 0) {
            $last_meal_description .= ', ';
        }
        if($last_day_meals_config['is_lunch_offsite']) {
            if($last_day_meals_config['is_snack_offsite']) {
                if($last_day_meals_config['is_diner_offsite']) {
                    $last_meal_description .= 'avec collation petit-déjeuner, pique-nique, goûter, et pique-nique du soir à emporter';
                }
                else {
                    $last_meal_description .= 'avec collation petit-déjeuner, pique-nique et goûter à emporter';
                }
            }
            else {
                $last_meal_description .= 'avec collation petit-déjeuner et pique-nique à emporter';
            }
        }
        else {
            $last_meal_description .= 'avec collation petit-déjeuner à emporter';
        }
    }
    elseif($last_day_meals_config['has_lunch'] && $last_day_meals_config['is_lunch_offsite']) {
        if(strlen($last_meal_description) > 0) {
            $last_meal_description .= ', ';
        }
        if($last_day_meals_config['is_snack_offsite']) {
            if($last_day_meals_config['is_diner_offsite']) {
                $last_meal_description .= 'avec pique-nique, goûter, et pique-nique du soir à emporter';
            }
            else {
                $last_meal_description .= 'avec pique-nique et goûter à emporter';
            }
        }
        else {
            $last_meal_description .= 'avec pique-nique à emporter';
        }
    }
    elseif($last_day_meals_config['has_snack'] && $last_day_meals_config['is_snack_offsite']) {
        if(strlen($last_meal_description) > 0) {
            $last_meal_description .= ', ';
        }
        if($last_day_meals_config['is_diner_offsite']) {
            $last_meal_description .= 'avec goûter et pique-nique du soir à emporter';
        }
        else {
            $last_meal_description .= 'avec goûter à emporter';
        }
    }
    elseif($last_day_meals_config['has_diner'] && $last_day_meals_config['is_diner_offsite']) {
        if(strlen($last_meal_description) > 0) {
            $last_meal_description .= ', ';
        }
        $last_meal_description .= 'avec pique-nique du soir à emporter';
    }

    /*
     * Quantities of people
     */

    $people_qty_conf = [
        'children'  => 0,
        'teachers'  => 0,
        'adults'    => 0,
        'drivers'   => 0,
    ];

    foreach($booking['booking_lines_groups_ids'] as $group) {
        if($group['group_type'] !== 'sojourn') {
            continue;
        }

        foreach($group['age_range_assignments_ids'] as $age_range_assignment) {
            switch($age_range_assignment['age_range_id']) {
                case 2:
                    $people_qty_conf['children'] += $age_range_assignment['qty'];
                    break;
                case 7:
                    $people_qty_conf['teachers'] += $age_range_assignment['qty'];
                    break;
                case 9:
                    $people_qty_conf['drivers'] += $age_range_assignment['qty'];
                    break;
                case 10:
                    $people_qty_conf['adults'] += $age_range_assignment['qty'];
                    break;
                default:
                    throw new Exception("not_handled_age_range", EQ_ERROR_INVALID_PARAM);
            }
        }
    }

    /*
     * Travel description
     */

    $products_ids = Product::search(['sku', 'in', ['RV-transport_aller_retour-2926', 'RV-Transport-Massol', 'RV-Transport-Verbus']])->ids();

    $booking_lines = BookingLine::search([
        ['booking_id', '=', $id],
        ['product_id', 'in', $products_ids]
    ])
        ->read(['product_id' => ['sku']])
        ->get();

    $travel_products_config = [
        'round_trip'        => false,
        'massol_activities' => false,
        'verbus_activities' => false
    ];

    foreach($booking_lines as $line) {
        switch($line['product_id']['sku']) {
            case 'RV-transport_aller_retour-2926':
                $travel_products_config['round_trip'] = true;
                break;
            case 'RV-Transport-Massol':
                $travel_products_config['massol_activities'] = true;
                break;
            case 'RV-Transport-Verbus':
                $travel_products_config['verbus_activities'] = true;
                break;
        }
    }

    $travel_description = '';
    if($travel_products_config['round_trip']) {
        $travel_description .= 'A/R avec les Voyages MASSOL';
    }
    if($people_qty_conf['drivers'] > 0) {
        if(strlen($travel_description) > 0) {
            $travel_description .= ', ';
        }
        $travel_description = 'Le bus reste sur place';
    }
    if($travel_products_config['massol_activities']) {
        if(strlen($travel_description) > 0) {
            $travel_description .= ', ';
        }
        $travel_description .= 'Déplacement avec les Voyages MASSOL';
    }
    if($travel_products_config['verbus_activities']) {
        if(strlen($travel_description) > 0) {
            $travel_description .= ', ';
        }
        $travel_description .= 'Déplacement avec VERBUS';
    }

    /*
     * Kindergarten attribute
     */

    $has_kindergarten = false;
    foreach($booking['booking_lines_groups_ids'] as $group) {
        foreach($group['booking_line_group_attributes_ids'] as $attribute) {
            if($attribute['code'] === 'kindergarten') {
                $has_kindergarten = true;
                break 2;
            }
        }
    }

    /*
     * Rental units
     */

    $rental_units = array_map(fn($rental_unit) => $rental_unit['name'], $booking['rental_unit_assignments_ids']);

    /*
     * Add data to result
     */

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
        'first_meal'                => $first_meal_description,
        'last_meal'                 => $last_meal_description,
        'nb_children'               => $people_qty_conf['children'],
        'nb_teachers'               => $people_qty_conf['teachers'],
        'nb_adults'                 => $people_qty_conf['adults'],
        'nb_drivers'                => $people_qty_conf['drivers'],
        'kindergarten'              => $has_kindergarten ? 'oui' : '',
        'travel'                    => $travel_description,
        'rental_units'              => implode(', ', $rental_units)
    ];
}

$context->httpResponse()
        ->header('X-Total-Count', count($result))
        ->body($result)
        ->send();
