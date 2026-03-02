import { Component, OnInit } from '@angular/core';
import { AbstractControl, FormBuilder, FormControl, FormGroup, ValidationErrors, Validators } from '@angular/forms';
import { ApiService } from '../../../_services/api.service';
import { AppService } from '../../../_services/app.service';
import { ConsumptionMeter, ConsumptionMeterReading, TypeMeter } from '../../../../type';
import { finalize, skip, takeUntil } from 'rxjs/operators';
import { ActivatedRoute, Router } from '@angular/router';
import { Subject } from 'rxjs';
import { TranslationService } from '../../../_services/translation.service';
import { mapTypeMeterTranslationKey } from '../../../../assets/data/consumption-meter';

@Component({
    selector: 'app-consumption-meter-reading-new',
    templateUrl: 'consumption-meter-reading-new.component.html',
    styleUrls: ['consumption-meter-reading-new.component.scss']
})
export class ConsumptionMeterReadingNewComponent implements OnInit  {

    public form: FormGroup = new FormGroup({
            consumption_meter_id: new FormControl(0, [Validators.required]),
            index_value: new FormControl('', [Validators.required, this.indexValidator.bind(this)])
        });

    public consumptionMeters: ConsumptionMeter[] = [];

    public loading: boolean = true;

    public bookingInspectionId: number = 0;

    private destroy$ = new Subject<void>();
    private lastConsumption: ConsumptionMeterReading;

    constructor(
        private formBuilder: FormBuilder,
        private api: ApiService,
        private app: AppService,
        private route: ActivatedRoute,
        private router: Router,
        private translation: TranslationService
    ) {}

    public ngOnInit() {
        this.route.params.pipe(takeUntil(this.destroy$)).subscribe(params => {
            this.bookingInspectionId = parseInt(params['booking_inspection_id']);
        });

        this.app.center$.pipe(takeUntil(this.destroy$)).subscribe(center => {
            if(!center) {
                return;
            }

            this.loading = true;
            this.api.fetchDoneConsumptionMeterIdsForBookingInspection(this.bookingInspectionId)
                .pipe(takeUntil(this.destroy$))
                .pipe(finalize(() => {
                    this.loading = false;
                }))
                .subscribe({
                    next: (doneConsumptionMeterIds) => {
                        this.api.fetchCenterConsumptionMetersExcept(center.id, doneConsumptionMeterIds)
                            .pipe(finalize(() => {
                                this.loading = false;
                            }))
                            .subscribe({
                                next: async (consumptionMeters) => {
                                    this.consumptionMeters = consumptionMeters;
                                    if(consumptionMeters.length > 0) {
                                        const meter = this.consumptionMeters[0]
                                        if(meter) {
                                            this.form.get('consumption_meter_id')?.setValue(meter.id);
                                            this.form.get('index_value')?.setValue(this.adaptIntegerIndex(meter.index_value));

                                            // retrieve previous reading for this meter, if any
                                            try {
                                                const lastConsumption = await this.api.fetchLastConsumptionMeterReadingByMeterId(meter.id).toPromise();
                                                if(lastConsumption) {
                                                    this.lastConsumption = lastConsumption;
                                                    this.form.get('index_value')?.setValue(this.adaptIntegerIndex(this.lastConsumption.index_value));
                                                }
                                            }
                                            catch(response) {}
                                        }
                                    }
                                },
                                error: (error) => {
                                    console.error('Error fetching consumption meters', error);
                                }
                            });
                    },
                    error: (error) => {
                        console.error('Error fetching done consumption meter ids', error);
                    }
                });
        });

        this.app.center$.pipe(takeUntil(this.destroy$), skip(1)).subscribe((center) => {
            if(center) {
                this.router.navigate(['/bookings/pending']);
            }
        });
    }

    public ngOnDestroy() {
        this.destroy$.next();
        this.destroy$.complete();
    }

    private indexValidator(control: FormControl): ValidationErrors | null {
        return (this.lastConsumption && this.adaptStringIndex(control.value) < this.lastConsumption.index_value) ?
                {'lowerThanPrevious': { value: control.value }} : null;
    }

    private adaptStringIndex(value: string): number {
        return parseInt(value.replace(/[^0-9]/g, ''));
    }

    private adaptIntegerIndex(value: number): string {
        let str_value = value.toString().padStart(8, '0')
        const int_art = str_value.slice(0, 5);
        const dec_part = str_value.slice(-3);
        return `${int_art},${dec_part}`;
    }

    private sanitizeStringIndex(value: string): string {
        const numeric_value = value.replace(',', '.').replace(/[^0-9.]/g, '');
        const float_value = parseFloat(numeric_value).toFixed(3).replace('.', ',');
        const [integer_part, decimal_part] = float_value.split(',');
        const padded_integer_part = integer_part.padStart(5, '0');
        return `${padded_integer_part},${decimal_part}`;
    }

    public async onMeterChange() {
        const consumption_meter_id = this.form.controls.consumption_meter_id.value;
        const meter = this.consumptionMeters.find((meter) => meter.id == consumption_meter_id);

        if(meter) {
            this.form.get('index_value')?.setValue(this.adaptIntegerIndex(meter.index_value));
            try {
                const lastConsumption = await this.api.fetchLastConsumptionMeterReadingByMeterId(meter.id).toPromise();
                if(lastConsumption) {
                    this.lastConsumption = lastConsumption;
                    this.form.get('index_value')?.setValue(this.adaptIntegerIndex(this.lastConsumption.index_value));
                }
            }
            catch(response) {
                console.log('unexpected error', response);
            }
        }
    }

    public onIndexChange() {
        console.log(this.consumptionMeters);
        const new_value = this.form.get('index_value')?.value ?? '';
        if(new_value.length) {
            this.form.get('index_value')?.setValue(this.sanitizeStringIndex(new_value));
        }
    }

    public save() {
        if(this.loading) {
            return;
        }

        this.form.updateValueAndValidity();

        if(this.form.valid) {
            const new_index_value: number = this.adaptStringIndex(this.form.controls.index_value.value);

            const newMeterReading: Partial<ConsumptionMeterReading> = {
                booking_inspection_id: this.bookingInspectionId,
                consumption_meter_id: this.form.controls.consumption_meter_id.value,
                index_value: new_index_value
            };

            this.loading = true;
            this.api.createMeterReading(newMeterReading)
                .pipe(finalize(() => {
                    this.loading = false;
                }))
                .subscribe({
                    next: () => {
                        this.router.navigate([`/booking-inspection/${this.bookingInspectionId}`]);
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

    public translateTypeMeter(typeMeter: TypeMeter): string {
        return this.translation.translate(mapTypeMeterTranslationKey[typeMeter]);
    }
}
