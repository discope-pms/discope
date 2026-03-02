import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import { GuestListItem } from '../../../type';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';

@Component({
    selector: 'guest-form-page',
    templateUrl: './guest-form-page.component.html',
    styleUrls: ['./guest-form-page.component.scss']
})
export class GuestFormPageComponent implements OnInit  {

    @Input() title: string;
    @Input() guest: GuestListItem;

    @Output() back = new EventEmitter();
    @Output() updateGuestListItem = new EventEmitter();

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
        private formBuilder: FormBuilder
    ) {}

    public ngOnInit() {
        let dateOfBirth: Date|null = null;
        if(this.guest.date_of_birth !== null) {
            const date_of_birth = (typeof this.guest.date_of_birth == 'string') ? (new Date(this.guest.date_of_birth)) : (new Date(this.guest.date_of_birth * 1000));
            dateOfBirth = date_of_birth;
        }

        this.form = this.formBuilder.group({
            firstname: [this.guest.firstname, Validators.required],
            lastname: [this.guest.lastname, Validators.required],
            gender: [this.guest.gender, Validators.required],
            is_coordinator: [this.guest.is_coordinator],
            date_of_birth: [dateOfBirth, Validators.required],
            citizen_identification: [this.guest.citizen_identification],
            address_street: [this.guest.address_street],
            address_zip: [this.guest.address_zip],
            address_city: [this.guest.address_city],
            address_country: [this.guest.address_country]
        });

        if(this.isLessThanFifteenYearsOld(dateOfBirth) || this.guest.address_country !== 'BE') {
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

            this.updateGuestListItem.emit({ id: this.guest.id, values: guest });

            this.back.emit();
        }
        else {
            this.form.markAllAsTouched();
        }
    }
}
