import { Component, Inject, OnInit } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { GuestListItem } from '../../../../type';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';

@Component({
    selector: 'guest-form-dialog',
    templateUrl: './guest-form-dialog.component.html',
    styleUrls: ['./guest-form-dialog.component.scss']
})
export class GuestFormDialogComponent implements OnInit  {

    public form: FormGroup = new FormGroup({
        firstname: new FormControl(''),
        lastname: new FormControl(''),
        gender: new FormControl('M'),
        is_coordinator: new FormControl(false),
        date_of_birth: new FormControl(''),
        citizen_identification: new FormControl(''),
        address_street: new FormControl(''),
        address_zip: new FormControl(''),
        address_city: new FormControl(''),
        address_country: new FormControl('')
    });

    constructor(
        private formBuilder: FormBuilder,
        public dialogRef: MatDialogRef<GuestFormDialogComponent>,
        @Inject(MAT_DIALOG_DATA) public data: GuestListItem
    ) {}

    public ngOnInit() {
        let dateOfBirth: Date|null = null;
        if(this.data.date_of_birth !== null) {
            const date_of_birth = (typeof this.data.date_of_birth == 'string') ? (new Date(this.data.date_of_birth)) : (new Date(this.data.date_of_birth * 1000));
            dateOfBirth = date_of_birth;
        }

        this.form = this.formBuilder.group({
            firstname: [this.data.firstname, Validators.required],
            lastname: [this.data.lastname, Validators.required],
            gender: [this.data.gender, Validators.required],
            is_coordinator: [this.data.is_coordinator],
            date_of_birth: [dateOfBirth, Validators.required],
            citizen_identification: [this.data.citizen_identification],
            address_street: [this.data.address_street],
            address_zip: [this.data.address_zip],
            address_city: [this.data.address_city],
            address_country: [this.data.address_country]
        });

        if(this.isLessThanFifteenYearsOld(dateOfBirth) || this.data.address_country !== 'BE') {
            this.form.get('citizen_identification')?.disable();
        }

        this.form.get('address_country')?.valueChanges.subscribe(value => {
            const citizenIdentificationFormControl = this.form.get('citizen_identification');
            if(!citizenIdentificationFormControl) {
                return;
            }

            if(value === 'BE') {
                citizenIdentificationFormControl.enable();
            }
            else {
                citizenIdentificationFormControl.disable();
                citizenIdentificationFormControl.setValue(null);
            }
        });

        this.form.get('date_of_birth')?.valueChanges.subscribe(value => {
            const citizenIdentificationFormControl = this.form.get('citizen_identification');
            if(!citizenIdentificationFormControl) {
                return;
            }

            if(this.isLessThanFifteenYearsOld(value)) {
                citizenIdentificationFormControl.disable();
                citizenIdentificationFormControl.setValue(null);
            }
            else {
                citizenIdentificationFormControl.enable();
                if(citizenIdentificationFormControl.value === null || citizenIdentificationFormControl.value.length === 0) {
                    let year = value.getFullYear().toString().slice(-2);
                    let month = (value.getMonth() + 1).toString().padStart(2, '0');
                    let day = value.getDate().toString().padStart(2, '0');

                    citizenIdentificationFormControl.setValue(`${year}.${month}.${day}-`);
                }
            }
        });
    }

    public isLessThanFifteenYearsOld(dateOfBirth: Date|null) {
        if(!dateOfBirth) {
            return false;
        }

        const today = new Date();
        const fifteenYearsAgo = new Date();
        fifteenYearsAgo.setFullYear(today.getFullYear() - 15);

        return dateOfBirth > fifteenYearsAgo;
    }

    public cancel() {
        this.dialogRef.close();
    }

    public save() {
        this.form.updateValueAndValidity();
        if(this.form.valid) {
            const guest: Partial<GuestListItem> = {
                firstname: this.form.controls.firstname.value,
                lastname: this.form.controls.lastname.value,
                is_coordinator: this.form.controls.is_coordinator.value,
                gender: this.form.controls.gender.value,
                date_of_birth: this.form.controls.date_of_birth.value.toISOString(),
                citizen_identification: this.form.controls.citizen_identification.value,
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
