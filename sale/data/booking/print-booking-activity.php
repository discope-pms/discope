<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use communication\Template;
use discope\setting\Setting;
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
            'type'              => 'integer',
            'description'       => 'Identifier of the booking to print.',
            'required'          => true
        ],
        'type' => [
            'type'              => 'string',
            'selection'         => ['global', 'weekly'],
            'description'       => 'The type of activities planning.',
            'default'           => 'global'
        ],
        'mode' =>  [
            'type'              => 'string',
            'selection'         => ['simple', 'grouped', 'detailed'],
            'description'       => 'Mode in which document has to be rendered: simple or detailed.',
            'default'           => 'grouped'
        ],
        'lang' =>  [
            'type'              => 'string',
            'description'       => 'Language in which labels and multilang field have to be returned (2 letters ISO 639-1).',
            'default'           => constant('DEFAULT_LANG')
        ],
        'output' =>  [
            'type'              => 'string',
            'selection'         => ['pdf', 'html'],
            'description'       => 'Output format of the document.',
            'default'           => 'pdf'
        ],
        'booking_line_group_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\booking\BookingLineGroup',
            'description'       => 'Identifier of the booking line group (sojourn) to print.'
        ]
    ],
    'access'        => [
        'visibility'    => 'protected',
        'groups'        => ['booking.default.user'],
    ],
    'response'      => [
        'content-type'  => 'application/pdf',
        'accept-origin' => '*'
    ],
    'constants'     => ['DEFAULT_LANG'],
    'providers'     => ['context']
]);


['context' => $context] = $providers;

$type = $params['type'];

// sanitize params
if($params['type'] === 'weekly') {
    unset($params['mode']);
    unset($params['booking_line_group_id']);
}
unset($params['type']);

$output = eQual::run('get', "sale_booking_print-booking-activity-$type", $params);

if($params['output'] == 'html') {
    $context
        ->httpResponse()
        ->header('Content-Type', 'text/html')
        ->body($output)
        ->send();
}
else {
    $context
        ->httpResponse()
        ->header('Content-Disposition', 'inline; filename="document.pdf"')
        ->body($output)
        ->send();
}
