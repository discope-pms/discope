<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2024
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use core\setting\Setting;
use identity\CenterOffice;

list($params, $providers) = eQual::announce([
    'description'   => "This action will finalize the fiscal year. Future invoices will apply to the current year, and issuing invoices for the previous year will no longer be possible.",
    'params'        => [],
    'access' => [
        'visibility'        => 'private'
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context                  $context
 */
$context = $providers['context'];


$year = date('Y');
$fiscal_year = Setting::get_value('finance', 'invoice', 'fiscal_year');

if(!$fiscal_year) {
    throw new Exception('missing_fiscal_year', EQ_ERROR_INVALID_CONFIG);
}

if(intval($year) <= intval($fiscal_year)) {
    throw new Exception('fiscal_year_mismatch', EQ_ERROR_CONFLICT_OBJECT);
}

Setting::set_value('finance', 'invoice', 'fiscal_year', $year);

$center_offices = CenterOffice::search()->read(['id', 'code'])->get(true);
foreach($center_offices as $center_office) {
    Setting::set_value('finance', 'invoice', 'sequence.'.$center_office['code'], 1);
}

$context->httpResponse()
        ->status(204)
        ->send();
