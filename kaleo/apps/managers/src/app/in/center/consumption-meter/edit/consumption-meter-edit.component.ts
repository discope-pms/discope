import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { Center, ConsumptionMeter } from '../../../../../type';
import { Subject } from 'rxjs';
import { ApiService } from '../../../../_services/api.service';
import { ActivatedRoute, Router } from '@angular/router';
import { AppService } from '../../../../_services/app.service';
import { finalize, skip, takeUntil } from 'rxjs/operators';

@Component({
    selector: 'app-consumption-meter-edit',
    templateUrl: 'consumption-meter-edit.component.html',
    styleUrls: ['consumption-meter-edit.component.scss']
})
export class ConsumptionMeterEditComponent implements OnInit  {

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

    public loading: boolean = true;

    public selectedCenter: Center|null = null;

    private consumptionMeterId: number = 0;

    private destroy$ = new Subject<void>();

    constructor(
        private formBuilder: FormBuilder,
        private api: ApiService,
        private router: Router,
        private route: ActivatedRoute,
        private app: AppService,
    ) {}

    public ngOnInit() {
        this.app.center$.pipe(takeUntil(this.destroy$)).subscribe((center) => {
            this.selectedCenter = center;
        });

        this.route.params.pipe(takeUntil(this.destroy$)).subscribe(params => {
            this.consumptionMeterId = parseInt(params['consumption_meter_id']);
            if(this.consumptionMeterId) {
                this.loading = true;
                this.api.fetchConsumptionMeter(this.consumptionMeterId)
                    .pipe(takeUntil(this.destroy$))
                    .pipe(finalize(() => {
                        this.loading = false;
                    }))
                    .subscribe({
                        next: (consumptionMeter) => {
                            if(!consumptionMeter) {
                                console.error(`Consumption meter ${this.consumptionMeterId} not found!`);
                                return;
                            }

                            this.initForm(consumptionMeter);
                        },
                        error: (error) => {
                            console.error('Error fetching consumption meter', error);
                        }
                    });
            }
        });

        this.app.center$.pipe(takeUntil(this.destroy$), skip(1)).subscribe((center) => {
            if(center) {
                this.router.navigate([`/center/${center.id}/consumption-meters`]);
            }
        });
    }

    private initForm(consumptionMeter: Partial<ConsumptionMeter>) {
        this.form = this.formBuilder.group({
            type_meter: [consumptionMeter.type_meter, Validators.required],
            index_value: [consumptionMeter.index_value, Validators.required],
            coefficient: [consumptionMeter.coefficient, Validators.required],
            meter_number: [consumptionMeter.meter_number, Validators.required],
            has_ean: [consumptionMeter.has_ean],
            meter_ean: [consumptionMeter.meter_ean],
            meter_unit: [consumptionMeter.meter_unit, Validators.required],
            product_id: [consumptionMeter.product_id, Validators.required]
        }, {
            validators: [this.validatorEanRequiredIfHasEan]
        });

        if(!consumptionMeter.has_ean) {
            this.form.get('meter_ean')?.disable();
        }

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
            this.api.updateConsumptionMeter(this.consumptionMeterId, newConsumptionMeter)
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
