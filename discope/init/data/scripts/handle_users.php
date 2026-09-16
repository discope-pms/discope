<?php

$providers = eQual::inject(['orm']);

/**
 * @var \equal\orm\ObjectManager $orm
 */
['orm' => $orm] = $providers;

$users_ids = $orm->search('core\\User', []);
$orm->update('core\\User', $users_ids, ['model' => 'identity\\User']);
