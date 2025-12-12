<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use communication\Template;
use core\setting\Setting;
use Dompdf\Dompdf;
use Dompdf\Options as DompdfOptions;
use sale\booking\Booking;
use sale\booking\BookingActivity;
use sale\booking\BookingMeal;
use sale\booking\TimeSlot;
use Twig\Environment as TwigEnvironment;
use Twig\Extension\ExtensionInterface;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\FilesystemLoader as TwigFilesystemLoader;

[$params, $providers] = eQual::announce([
    'description'   => "Render a booking activities planning as a PDF document, given its id.",
    'params'        => [
        'id' => [
            'description'   => 'Identifier of the booking to print.',
            'type'          => 'integer',
            'required'      => true
        ],
        'type' => [
            'description'   => 'The type of activities planning.',
            'type'          => 'string',
            'selection'     => [
                'global',
                'weekly'
            ],
            'default'       => 'global'
        ],
        'mode' =>  [
            'description'   => 'Mode in which document has to be rendered: simple or detailed.',
            'type'          => 'string',
            'selection'     => ['simple', 'grouped', 'detailed'],
            'default'       => 'grouped'
        ],
        'lang' =>  [
            'description'   => 'Language in which labels and multilang field have to be returned (2 letters ISO 639-1).',
            'type'          => 'string',
            'default'       => constant('DEFAULT_LANG')
        ],
        'output' =>  [
            'description'   => 'Output format of the document.',
            'type'          => 'string',
            'selection'     => ['pdf', 'html'],
            'default'       => 'pdf'
        ],
        'booking_line_group_id' => [
            'type'          => 'many2one',
            'foreign_object'=> 'sale\booking\BookingLineGroup',
            'description'   => 'Identifier of the booking line group (sojourn) to print.'
        ]
    ],
    'constants'             => ['DEFAULT_LANG', 'L10N_LOCALE'],
    'access' => [
        'visibility'        => 'protected',
        'groups'            => ['booking.default.user'],
    ],
    'response'      => [
        'content-type'      => 'application/pdf',
        'accept-origin'     => '*'
    ],
    'providers'     => ['context', 'orm']
]);


['context' => $context, 'orm' => $orm] = $providers;

$type = $params['type'];

// sanitize params
if($params['type'] === 'weekly') {
    unset($params['mode']);
    unset($params['booking_line_group_id']);
}
unset($params['type']);

$output = eQual::run('get', "sale_booking_print-booking-activity-$type", $params);

if($params['output'] == 'html') {
    $context->httpResponse()
            ->header('Content-Type', 'text/html')
            ->body($output)
            ->send();
}
else {
    $context->httpResponse()
            ->header('Content-Disposition', 'inline; filename="document.pdf"')
            ->body($output)
            ->send();
}
