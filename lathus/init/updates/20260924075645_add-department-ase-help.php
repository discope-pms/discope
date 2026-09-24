<?php

use sale\camp\Enrollment;
use sale\camp\price\PriceAdapter;
use sale\camp\Sponsor;

['orm' => $orm] = eQual::inject(['orm']);


// 1) Update sponsors

$ase_sponsors_ids = $orm->search(Sponsor::getType(), [
    [
        ['deleted', 'in', [0, 1]],
        ['sponsor_type', '=', 'other'],
        ['name', 'ilike', '%ASE%']
    ],
    [
        ['deleted', 'in', [0, 1]],
        ['sponsor_type', '=', 'other'],
        ['name', 'ilike', '%ALSEA%']
    ]
]);

$orm->update(Sponsor::getType(), $ase_sponsors_ids, ['sponsor_type' => 'department-ase']);

$msa_sponsors_ids = $orm->search(Sponsor::getType(), [
    ['deleted', 'in', [0, 1]],
    ['state', 'in', ['draft', 'instance']],
    ['sponsor_type', '=', 'other'],
    ['name', 'ilike', '%MSA%']
]);

$orm->update(Sponsor::getType(), $msa_sponsors_ids, ['sponsor_type' => 'department-msa']);


// 2) Update price adapters

$ase_price_adapters_ids = $orm->search(PriceAdapter::getType(), [
    ['origin_type', '=', 'other'],
    ['name', 'ilike', '%ASE%']
]);

$orm->update(PriceAdapter::getType(), $ase_price_adapters_ids, [
    'origin_type' => 'department-ase',
]);


// 3) Reset enrollments status to regenerate fundings

$price_adapters = PriceAdapter::ids($ase_price_adapters_ids)
    ->read(['enrollment_id' => ['status']])
    ->get();

$map_enrollments_ids = [];
foreach($price_adapters as $id => $price_adapter) {
    if(!in_array($price_adapter['enrollment_id']['status'], ['confirmed', 'validated'])) {
        continue;
    }

    $map_enrollments_ids[$price_adapter['enrollment_id']['id']] = true;

    Enrollment::id($price_adapter['enrollment_id']['id'])->transition($price_adapter['enrollment_id']['status'] === 'confirmed' ? 'unconfirm' : 'unvalidate');

    PriceAdapter::id($id)->do('reset-enrollments-prices');

    Enrollment::id($price_adapter['enrollment_id']['id'])->transition('confirm');

    if($price_adapter['enrollment_id']['status'] === 'validated') {
        Enrollment::id($price_adapter['enrollment_id']['id'])->transition('validate');
    }
}
