<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use core\Group;

$group_name = 'booking.guestlist.user';

$group = Group::search(['name', '=', $group_name])
    ->read(['id'])
    ->first(true);

if(!$group) {
    Group::create(['name' => $group_name]);
}
