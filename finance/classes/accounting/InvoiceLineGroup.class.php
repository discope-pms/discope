<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2024
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace finance\accounting;

use equal\orm\Model;

class InvoiceLineGroup extends Model {

    public static function getName() {
        return "Invoice line group";
    }

    public static function getDescription() {
        return "Invoice line groups are related to an invoice and are meant to join several invoice lines.";
    }

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => "Label of the group (displayed on invoice).",
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => "Short description of the group (displayed on invoice)."
            ],

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\Invoice',
                'description'       => "Invoice the line is related to.",
                'required'          => true,
                'ondelete'          => 'cascade'
            ],

            'invoice_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'finance\accounting\InvoiceLine',
                'foreign_field'     => 'invoice_line_group_id',
                'description'       => "Detailed lines of the group.",
                'ondetach'          => 'delete',
                'onupdate'          => 'onupdateInvoiceLinesIds'
            ]

        ];
    }

    public static function onupdateInvoiceLinesIds($self) {
        $self->read(['invoice_id']);

        Invoice::ids(array_column($self->get(true), 'invoice_id'))
            ->update([
                'price' => null,
                'total' => null
            ]);
    }
}
