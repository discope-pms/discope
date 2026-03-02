import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { finalize, skip, takeUntil } from 'rxjs/operators';
import { forkJoin, Subject } from 'rxjs';
import { ApiService } from '../../_services/api.service';
import { Booking, BookingInspection, ConsumptionMeter, ConsumptionMeterReading, CustomerIdentity, TypeMeter } from '../../../type';
import { TranslationService } from '../../_services/translation.service';
import { AppService } from '../../_services/app.service';

interface MeterReading {
    id: number,
    typeMeter: TypeMeter,
    meterNumber: string,
    indexValue: number
}

@Component({
    selector: 'app-booking-inspection',
    templateUrl: 'booking-inspection.component.html',
    styleUrls: ['booking-inspection.component.scss']
})
export class BookingInspectionComponent implements OnInit  {

    public titleStr: string = 'Booking';

    public checkStr: 'check in'|'check out' = 'check in';

    public booking = {
        dateFrom: '',
        dateTo: '',
        customerIdentityId: 0,
        customerDisplayName: ''
    };

    public meterReadingList: MeterReading[] = [];

    public bookingInspection: BookingInspection|null = null;

    public loading: boolean = true;

    private destroy$ = new Subject<void>();

    constructor(
        private router: Router,
        private route: ActivatedRoute,
        private api: ApiService,
        private translation: TranslationService,
        private app: AppService
    ) {}

    public ngOnInit() {
        this.route.params.pipe(takeUntil(this.destroy$)).subscribe(params => {
            this.loading = true;

            const bookingInspectionId = parseInt(params['booking_inspection_id']);

            forkJoin({
                bookingInspection: this.api.fetchBookingInspection(bookingInspectionId),
                consumptionMeterReadings: this.api.fetchConsumptionMeterReadingsByBookingInspectionId(bookingInspectionId)
            })
                .pipe(takeUntil(this.destroy$))
                .pipe(finalize(() => {
                    this.loading = false;
                }))
                .subscribe({
                    next: ({ bookingInspection, consumptionMeterReadings }) => {
                        if(bookingInspection) {
                            this.handleBookingInspection(bookingInspection);
                        }
                        this.handleConsumptionMeterReadings(consumptionMeterReadings);
                    },
                    error: (error) => {
                        console.error('Error fetching booking inspection and/or consumption meter readings', error);
                    }
                });
        });

        this.app.center$.pipe(takeUntil(this.destroy$), skip(1)).subscribe((center) => {
            if(center) {
                this.router.navigate(['/bookings/pending']);
            }
        });
    }

    private handleBookingInspection(bookingInspection: BookingInspection) {
        this.bookingInspection = bookingInspection;

        const booking = bookingInspection.booking_id as Booking;
        const customerIdentity = booking.customer_identity_id as CustomerIdentity;

        this.booking = {
            dateFrom: this.formatDate(booking.date_from),
            dateTo: this.formatDate(booking.date_to),
            customerIdentityId: customerIdentity.id,
            customerDisplayName: customerIdentity.display_name
        };

        this.titleStr = this.translation.translateWithVar('BOOKING_INSPECTION_TITLE', { bookingName: booking.name });
        this.checkStr = bookingInspection.type_inspection === 'checkedin' ? 'check in' : 'check out';
    }

    private formatDate(isoDateString: string): string {
        const date = new Date(isoDateString);
        const formatter = new Intl.DateTimeFormat('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
        return formatter.format(date);
    }

    private handleConsumptionMeterReadings(consumptionMeterReadings: ConsumptionMeterReading[]) {
        this.meterReadingList = consumptionMeterReadings.map((consMeterReading) => {
            const consumptionMeter = consMeterReading.consumption_meter_id as ConsumptionMeter;

            return {
                id: consMeterReading.id,
                typeMeter: consumptionMeter.type_meter,
                meterNumber: consumptionMeter.meter_number,
                indexValue: consMeterReading.index_value
            };
        });
    }

    public ngOnDestroy() {
        this.destroy$.next();
        this.destroy$.complete();
    }

    public goToConfirm() {
        if(this.bookingInspection) {
            this.router.navigate([`/booking-inspection/${this.bookingInspection.id}/confirm`]);
        }
    }

    public goToNew() {
        if(this.bookingInspection) {
            this.router.navigate([`/booking-inspection/${this.bookingInspection.id}/consumption-meter-reading/new`]);
        }
    }

    public deleteMeterReading(id: number) {
        this.api.deleteConsumptionMeterReading(id)
            .pipe(takeUntil(this.destroy$))
            .subscribe({
                next: () => {
                    if(!this.bookingInspection) {
                        return;
                    }

                    this.loading = true;
                    this.api.fetchConsumptionMeterReadingsByBookingInspectionId(this.bookingInspection.id).subscribe(
                        (consumptionMeterReadings) => {
                            if(consumptionMeterReadings) {
                                this.handleConsumptionMeterReadings(consumptionMeterReadings);
                            }
                        },
                        (error) => {
                            console.error('Error fetching booking inspection', error);
                        },
                        () => {
                            this.loading = false;
                        });
                },
                error: (error) => {
                    console.error('Error deleting consumption meter reading', error);
                }
            });
    }
}
