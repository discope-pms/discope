import { Component, Inject, Input, OnInit } from '@angular/core';
import { Observable } from 'rxjs';
import { FormGroup } from '@angular/forms';
import { countries } from '../../../assets/data/countries';
import { genders } from '../../../assets/data/gender';
import { BreakpointState } from '@angular/cdk/layout';
import { ResponsiveService } from 'src/app/_services/ResponsiveService';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';

@Component({
    selector: 'guest-form',
    templateUrl: './guest-form.component.html',
    styleUrls: ['./guest-form.component.scss']
})
export class GuestFormComponent implements OnInit  {

    @Input() form: FormGroup;

    public genderOptions: { value: string, label: string }[] = [];

    public countryOptions: { value: string, label: string }[] = [];

    public isHandset$: Observable<BreakpointState>;
    public isWeb$: Observable<BreakpointState>;

    public show_popup: any = {};

    constructor(
            private responsiveService: ResponsiveService,
            private dialog: MatDialog) {

        this.isHandset$ = this.responsiveService.isHandset();
        this.isWeb$ = this.responsiveService.isWeb();

        Object.entries(genders).forEach(([key, value]) => {
            this.genderOptions.push({ value: key, label: value });
        });

        Object.entries(countries).forEach(([key, value]) => {
            this.countryOptions.push({ value: key, label: value });
        });
    }

    public ngOnInit() {
    }

    public onSelectGender(option: any): void {
        this.show_popup['gender'] = false;
        this.form.get('gender')?.setValue(option.value);
    }

    public onSelectCountry(option: any): void {
        this.show_popup['address_country'] = false;
        this.form.get('address_country')?.setValue(option.value);
    }
}

