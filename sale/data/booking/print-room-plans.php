<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use discope\setting\Setting;
use sale\booking\Booking;

[$params, $providers] = eQual::announce([
    'description'   => "Render a room plan of a specific booking as a PDF document, given its id.",
    'params'        => [
        'id' => [
            'type'          => 'integer',
            'description'   => 'Identifier of the concerned booking.',
            'required'      => true
        ],
        'view_id' =>  [
            'type'          => 'string',
            'description'   => 'The identifier of the view <type.name>.',
            'default'       => 'print.room-plans'
        ],
        'lang' =>  [
            'type'          => 'string',
            'description'   => 'Language in which labels and multilang field have to be returned (2 letters ISO 639-1).',
            'default'       => constant('DEFAULT_LANG')
        ],
        'output' =>  [
            'type'          => 'string',
            'description'   => 'Output format of the document.',
            'selection'     => ['pdf', 'html'],
            'default'       => 'pdf'
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

$getCustomPackageOutput = function() use($context, $params) {
    $has_custom_package = Setting::get_value('discope', 'features', 'has_custom_package', false);
    if(!$has_custom_package) {
        return null;
    }

    $output = null;
    $custom_package = Setting::get_value('discope', 'features', 'custom_package');
    if(is_null($custom_package)) {
        trigger_error('APP::Missing customization package setting (despite `discope.features.has_custom_package`)', EQ_REPORT_WARNING);
    }
    elseif($custom_package !== 'sale') {
        $operation = $context->get('operation');

        $custom_ctrl_file = sprintf(
            '%s/packages/%s/data/%s/%s',
            EQ_BASEDIR,
            $custom_package,
            $operation['package'],
            $operation['script']
        );

        if(file_exists($custom_ctrl_file)) {
            $custom_ctrl = sprintf(
                '%s_%s',
                $custom_package,
                $operation['operation']
            );

            $output = eQual::run('get', $custom_ctrl, $params, true, true);
        }
    }

    return $output;
};

$getTemplateFilePath = function($entity, $view_id) {
    $template_file = '';

    $parts = explode('\\', $entity);
    $package = array_shift($parts);
    $class_path = implode('/', $parts);

    $has_custom_package = Setting::get_value('discope', 'features', 'has_custom_package', false);
    if($has_custom_package) {
        $custom_package = Setting::get_value('discope', 'features', 'custom_package');
        if(is_null($custom_package)) {
            trigger_error('APP::Missing customization package setting (despite `discope.features.has_custom_package`)', EQ_REPORT_WARNING);
        }
        elseif(file_exists(EQ_BASEDIR."/packages/{$custom_package}/views/{$package}/{$class_path}.{$view_id}.html")) {
            $template_file = EQ_BASEDIR . "/packages/{$custom_package}/views/{$package}/{$class_path}.{$view_id}.html";
        }
    }

    if(empty($template_file)) {
        $template_file = EQ_BASEDIR."/packages/{$package}/views/{$class_path}.{$view_id}.html";

        if(!file_exists($template_file)) {
            throw new Exception("unknown_view_id", QN_ERROR_UNKNOWN_OBJECT);
        }
    }

    return $template_file;
};

// handle custom package override, if any
$output = $getCustomPackageOutput();

if(is_null($output)) {
    $template_file_path = $getTemplateFilePath(Booking::getType(), $params['view_id']);

    // #todo - handle room plans for most customers (for now only lathus specific is handled)
}

if($params['output'] === 'html') {
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
