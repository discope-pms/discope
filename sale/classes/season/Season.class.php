<?php
/*
    This file is part of the Discope property management software <https://github.com/discope-pms/discope>
    Some Rights Reserved, Discope PMS, 2020-2024
    Original author(s): Yesbabylon SRL
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\season;
use equal\orm\Model;

class Season extends Model {
    public static function getColumns() {
        /**
         */

        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Short mnemo of the season.",
                'required'          => true
            ],

            'year' => [
                'type'              => 'integer',
                'usage'             => 'date/year:4',
                'description'       => "Year the season applies to.",
                'required'          => true
            ],

            'season_category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\season\SeasonCategory',
                'description'       => "The category the season relates to.",
                'required'          => true,
                'onupdate'          => 'onupdateSeasonCategoryId'
            ],

            'has_rate_class' => [
                'type'              => 'boolean',
                'description'       => "Is the season specific to a given Rate Class?",
                'default'           => false
            ],

            'rate_class_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\RateClass',
                'description'       => "The rate class that applies to this Season defintion.",
                'visible'           => ['has_rate_class', '=', true]
            ],

            'season_periods_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\season\SeasonPeriod',
                'foreign_field'     => 'season_id',
                'description'       => 'Periods that are part of the season (on a yearly basis).',
                'ondetach'          => 'delete'
            ],

            'center_category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\CenterCategory',
                'description'       => "Center category targeted by season.",
                'required'          => true
            ]

        ];
    }

    public static function onupdateSeasonCategoryId($om, $oids, $values, $lang) {
        $seasons = $om->read(self::getType(), $oids, ['season_periods_ids']);
        foreach($seasons as $season) {
            $om->update(SeasonPeriod::getType(), $season['season_periods_ids'], ['season_category_id' => null]);
        }
    }
}
