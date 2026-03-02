import { Component, Inject, OnInit } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { GuestListItem } from '../../../../type';
import { FormBuilder, FormControl, FormGroup } from '@angular/forms';

@Component({
    selector: 'guest-bulk-form-dialog',
    templateUrl: './guest-bulk-form-dialog.component.html',
    styleUrls: ['./guest-bulk-form-dialog.component.scss']
})
export class GuestBulkFormDialogComponent implements OnInit  {

    public form: FormGroup = new FormGroup({
            address_street: new FormControl(''),
            address_zip: new FormControl(''),
            address_city: new FormControl(''),
            address_country: new FormControl('')
        });

    constructor(
            private formBuilder: FormBuilder,
            public dialogRef: MatDialogRef<GuestBulkFormDialogComponent>,
            @Inject(MAT_DIALOG_DATA) public data: GuestListItem
        ) {}

    public ngOnInit() {
        this.form = this.formBuilder.group({
            address_street: [this.data.address_street],
            address_zip: [this.data.address_zip],
            address_city: [this.data.address_city],
            address_country: [this.data.address_country]
        });
    }

    public cancel() {
        this.dialogRef.close();
    }

    public save() {
        this.form.updateValueAndValidity();
        if(this.form.valid) {
            const guest: Partial<GuestListItem> = {
                address_street: this.form.controls.address_street.value,
                address_zip: this.form.controls.address_zip.value,
                address_city: this.form.controls.address_city.value,
                address_country: this.form.controls.address_country.value,
            };

            this.dialogRef.close(guest);
        }
        else {
            this.form.markAllAsTouched();
        }
    }
}
