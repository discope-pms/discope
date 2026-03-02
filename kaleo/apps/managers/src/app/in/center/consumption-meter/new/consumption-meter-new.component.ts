import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { ApiService } from '../../../../_services/api.service';
import { Router } from '@angular/router';
import { Center } from '../../../../../type';
import { AppService } from '../../../../_services/app.service';
import { finalize, skip, takeUntil } from 'rxjs/operators';
import { Subject } from 'rxjs';

@Component({
    selector: 'app-consumption-meter-new',
    templateUrl: 'consumption-meter-new.component.html',
    styleUrls: ['consumption-meter-new.component.scss']
})
export class ConsumptionMeterNewComponent implements OnInit  {

    public form: FormGroup = new FormGroup({
        'type_meter': new FormControl('water'),
        'index_value': new FormControl(0),
        'coefficient': new FormControl(1.0),
        'meter_number': new FormControl(''),
        'has_ean': new FormControl(false),
        'meter_ean': new FormControl(''),
        'meter_unit': new FormControl('m3'),
        'product_id': new FormControl(null)
    });

    public selectedCenter: Center|null = null;

    public loading: boolean = false;

    private destroy$ = new Subject<void>();

    constructor(
        private formBuilder: FormBuilder,
        private api: ApiService,
        private router: Router,
        private app: AppService,
    ) {}

    public ngOnInit() {
        this.initForm();

        this.app.center$.pipe(takeUntil(this.destroy$)).subscribe((center) => {
            this.selectedCenter = center;
        });

        this.app.center$.pipe(takeUntil(this.destroy$), skip(1)).subscribe((center) => {
            if(center) {
                this.router.navigate([`/center/${center.id}/consumption-meters`]);
            }
        });
    }

    private initForm() {
        this.form = this.formBuilder.group({
                    type_meter: ['water', Validators.required],
                    index_value: [0, Validators.required],
                    coefficient: [1.0, Validators.required],
                    meter_number: ['', Validators.required],
                    has_ean: [false],
                    meter_ean: [''],
                    meter_unit: ['m3', Validators.required],
                    product_id: [null, Validators.required]
                }, {
                validators: [this.validatorEanRequiredIfHasEan]
            });

        this.form.get('meter_ean')?.disable();

        this.form.get('has_ean')?.valueChanges.subscribe(value => {
            const meterEanFormControl = this.form.get('meter_ean');
            if(!meterEanFormControl) {
                return;
            }

            if(value) {
                meterEanFormControl.enable();
            }
            else {
                meterEanFormControl.disable();
                meterEanFormControl.setValue('');
            }
        });
    }

    public ngOnDestroy() {
        this.destroy$.next();
        this.destroy$.complete();
    }

    private validatorEanRequiredIfHasEan(group: FormGroup) {
        const hasEanControl = group.get('has_ean');
        const meterEanControl = group.get('meter_ean');

        if (hasEanControl?.value && !meterEanControl?.value) {
            meterEanControl?.setErrors({ required: true });
        } else {
            meterEanControl?.setErrors(null);
        }

        return null;
    }

    public save() {
        if(this.loading) {
            return;
        }

        this.form.updateValueAndValidity();
        if(this.form.valid) {
            const newConsumptionMeter = {
                type_meter: this.form.controls.type_meter.value,
                index_value: this.form.controls.index_value.value,
                coefficient: this.form.controls.coefficient.value,
                meter_number: this.form.controls.meter_number.value,
                has_ean: this.form.controls.has_ean.value,
                meter_ean: this.form.controls.meter_ean.value,
                meter_unit: this.form.controls.meter_unit.value,
                product_id: this.form.controls.product_id.value,
                center_id: this.selectedCenter?.id,
                description_meter: ''
            };

            this.loading = true;
            this.api.createConsumptionMeter(newConsumptionMeter)
                .pipe(takeUntil(this.destroy$))
                .pipe(finalize(() => {
                    this.loading = false;
                }))
                .subscribe({
                    next: () => {
                        this.router.navigate([`/center/${this.selectedCenter?.id}/consumption-meters`]);
                    },
                    error: (error) => {
                        console.error('Error creating consumption meter:', error);
                    }
                });
        }
        else {
            this.form.markAllAsTouched();
        }
    }
}
