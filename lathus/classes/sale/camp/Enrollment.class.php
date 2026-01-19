<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2025
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace lathus\sale\camp;

class Enrollment extends \sale\camp\Enrollment {

    public static function getDescription(): string {
        return "Override of camp Enrollment to add data fetched from CPA Lathus API.";
    }

    public static function getColumns(): array {
        return [

            'phone' => [
                'type'              => 'string',
                'description'       => "Specific phone number given by the person who's handling the enrollment."
            ]

        ];
    }
}
