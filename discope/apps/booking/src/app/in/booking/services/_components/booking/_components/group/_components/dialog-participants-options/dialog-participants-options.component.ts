import { Component, Inject, OnInit } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { FormControl } from '@angular/forms';
import {logger} from "codelyzer/util/logger";

interface vmModel {
    has_person_with_disability: {
        formControl: FormControl
    },
    person_disability_description: {
        formControl: FormControl
    },
    attributes: {
        data: { id: number, name: string, code: string, description: string },
        formControl: FormControl
    }[]
}

@Component({
    selector: 'booking-services-booking-group-dialog-participants-options',
    templateUrl: './dialog-participants-options.component.html',
    styleUrls: ['./dialog-participants-options.component.scss']
})
export class BookingServicesBookingGroupDialogParticipantsOptionsComponent implements OnInit  {

    public vm: vmModel;

    constructor(
        public dialogRef: MatDialogRef<BookingServicesBookingGroupDialogParticipantsOptionsComponent>,
        @Inject(MAT_DIALOG_DATA) public data: any
    ) {
        const attributes = [];
        for(let attribute of data.group_attributes) {
            attributes.push({
                data: attribute,
                formControl: new FormControl(data.booking_line_group_attributes_ids.map((a: any) => a.id).includes(attribute.id))
            });
        }

        this.vm = {
            has_person_with_disability: {
                formControl: new FormControl(data.has_person_with_disability)
            },
            person_disability_description: {
                formControl: new FormControl(data.person_disability_description)
            },
            attributes
        };
    }

    public ngOnInit() {
    }

    public onClose() {
        this.dialogRef.close();
    }

    public onSave() {
        if(this.vm.has_person_with_disability.formControl.invalid) {
            console.warn('invalid has person with disability');
            return;
        }
        if(this.vm.person_disability_description.formControl.invalid) {
            console.warn('invalid person disability description');
            return;
        }

        const attributesIds: number[] = [];
        for(let attribute of this.vm.attributes) {
            if(attribute.formControl.invalid) {
                console.warn('invalid ' + attribute.data.name);
                return;
            }

            if(attribute.formControl.value) {
                attributesIds.push(attribute.data.id);
            }
            else {
                attributesIds.push(-attribute.data.id);
            }
        }

        this.dialogRef.close({
            has_person_with_disability: this.vm.has_person_with_disability.formControl.value,
            person_disability_description: this.vm.person_disability_description.formControl.value,
            attributes_ids: attributesIds
        });
    }
}
