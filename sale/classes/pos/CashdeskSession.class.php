<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\pos;

use equal\orm\Model;

class CashdeskSession extends Model {

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcName',
                'store'             => true
            ],

            'amount' => [
                'type'              => 'alias',
                'alias'             => 'amount_opening'
            ],

            'date_opening' => [
                'type'              => 'alias',
                'alias'             => 'created'
            ],

            'date_closing' => [
                'type'              => 'datetime',
                'description'       => "Date and time of the closing of the Session."
            ],

            'amount_opening' => [
                'type'              => 'float',
                'usage'             => 'amount/money:2',
                'description'       => "Amount of money in the cashdesk at the opening.",
                'required'          => true
            ],

            'amount_closing' => [
                'type'              => 'float',
                'usage'             => 'amount/money:2',
                'description'       => "Amount of money in the cashdesk at the closing.",
                'default'           => 0.0
            ],

            'note' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => "Optional explanatory note given at the closing.",
                'default'           => ''
            ],

            'user_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\User',
                'description'       => "User whom performed the log entry.",
                'required'          => true
            ],

            'cashdesk_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pos\Cashdesk',
                'description'       => "The cashdesk the session relates to.",
                'onupdate'          => 'onupdateCashdeskId',
                'required'          => true
            ],

            'center_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Center',
                'description'       => "The center the desk relates to (from cashdesk)."
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'pending',
                    'closed'
                ],
                'description'       => "Current status of the session.",
                'onupdate'          => 'onupdateStatus',
                'default'           => 'pending'
            ],

            'link_sheet' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'usage'             => 'uri/url',
                'description'       => "URL for generating the PDF version of the report.",
                'function'          => 'calcLinkSheet',
                'readonly'          => true
            ],

            'orders_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\pos\Order',
                'foreign_field'     => 'session_id',
                'description'       => "The orders that relate to the session."
            ],

            'operations_ids'  => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\pos\Operation',
                'foreign_field'     => 'session_id',
                'ondetach'          => 'delete',
                'description'       => "List of operations performed during session."
            ]

        ];
    }

    /**
     * Check for special constraint : only one session can be opened at a time on a given cashdesk.
     * Make sure there are no other pending sessions, otherwise, deny the update (which might be called on draft instance).
     */
    public static function cancreate($self, $values) {
        $sessions_ids = CashdeskSession::search([
            ['status', '=', 'pending'],
            ['cashdesk_id', '=', $values['cashdesk_id']]
        ])
            ->ids();

        if(!empty($sessions_ids)) {
            return ['status' => ['already_open' => 'There can be only one session at a time on a given cashdesk.']];
        }

        return parent::cancreate($self, $values);
    }

    /**
     * Check whether an object can be updated.
     * These tests come in addition to the unique constraints return by method `getUnique()`.
     * This method can be overridden to define a more precise set of tests.
     *
     * @param \equal\orm\Collection $self
     * @param array                 $values Associative array holding the new values to be assigned.
     * @return array                Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be updated.
     * @throws \Exception
     */
    public static function canupdate($self, $values) {
        $self->read(['status']);
        foreach($self as $session) {
            if($session['status'] === 'closed') {
                return ['status' => ['non_editable' => 'Closed session cannot be modified.']];
            }
        }

        return parent::canupdate($self, $values);
    }

    /**
     * Create an 'opening' operation in the operations log.
     * Cashdesk assignment cannot be changed, so this handler is called once, when the session has just been created.
     */
    public static function onupdateCashdeskId($self, $values) {
        $self->read(['cashdesk_id' => ['id', 'center_id'], 'amount_opening', 'user_id']);

        foreach($self as $id => $session) {
            CashdeskSession::id($id)
                ->update(['center_id' => $session['cashdesk_id']['center_id']]);

            Operation::create([
                'cashdesk_id'   => $session['cashdesk_id']['id'],
                'session_id'    => $id,
                'user_id'       => $session['user_id'],
                'amount'        => $session['amount_opening'],
                'type'          => 'opening'
            ]);
        }
    }

    public static function onupdateStatus($self, $values, $lang) {
        // upon session closing, create additional operation if there is a delta in cash amount
        if(isset($values['status']) && $values['status'] === 'closed') {
            $self->read(['cashdesk_id', 'user_id', 'amount_opening', 'amount_closing', 'operations_ids' => ['amount']]);
            foreach($self as $sid => $session) {
                $total_cash = 0.0;
                foreach($session['operations_ids'] as $operation) {
                    $total_cash += $operation['amount'];
                }

                // compute the difference (if any) between expected cash and actual cash in the cashdesk
                $expected_cash = $total_cash + $session['amount_opening'];
                $delta = $session['amount_closing'] - $expected_cash;
                if($delta != 0) {
                    Operation::create(
                        [
                            'cashdesk_id'   => $session['cashdesk_id'],
                            'session_id'    => $sid,
                            'user_id'       => $session['user_id'],
                            'amount'        => $delta,
                            'type'          => 'move',
                            'description'   => 'cashdesk closing'
                        ],
                        $lang
                    );

                    /** @var \equal\dispatch\Dispatcher $dispatch */
                    ['dispatch' => $dispatch] = \eQual::inject(['dispatch']);

                    $dispatch->dispatch('lodging.pos.close-discrepancy', 'sale\pos\CashdeskSession', $sid, 'warning');
                }
            }
        }
    }

    public static function calcName($self) {
        $result = [];
        $self->read([
            'user_id'       => ['name'],
            'cashdesk_id'   => ['name']]
        );

        foreach($self as $id => $session) {
            if(!empty($session['user_id']['name']) && !empty($session['cashdesk_id']['name'])) {
                $result[$id] = sprintf('%s - %s',
                    $session['user_id']['name'],
                    $session['cashdesk_id']['name']
                );
            }
        }

        return $result;
    }

    public static function calcLinkSheet($self) {
        $result = [];
        foreach($self as $id => $session) {
            $result[$id] = "/?get=sale_pos_print-cashdeskSession-day&id=$id";
        }

        return $result;
    }
}
