import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { ApiService } from '../../../_services/api.service';
import { finalize, skip, takeUntil } from 'rxjs/operators';
import { forkJoin, Subject } from 'rxjs';
import {
    Booking,
    BookingContact,
    BookingInspection,
    Center,
    ConsumptionMeter,
    ConsumptionMeterReading,
    CustomerIdentity,
    MeterUnit,
    TypeMeter
} from '../../../../type';
import { FormArray, FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { AppService } from '../../../_services/app.service';

interface MeterReading {
    id: number,
    typeMeter: TypeMeter,
    meterUnit: MeterUnit,
    indexValue: number,
    unitPrice: number
}

@Component({
    selector: 'app-booking-inspection-confirm',
    templateUrl: 'booking-inspection-confirm.component.html',
    styleUrls: ['booking-inspection-confirm.component.scss']
})
export class BookingInspectionConfirmComponent implements OnInit  {

    public loading: boolean = true;

    public checkStr: 'check in'|'check out' = 'check in';

    public booking = {
        name: '',
        center_name: '',
        date_from: '',
        customer_identity_id: 0,
        customer_display_name: ''
    };

    public meterReadingList: MeterReading[] = [];

    public bookingInspection: BookingInspection|null = null;

    public bookingContactEmailList: string[] = [];

    public formEmailList: FormGroup = new FormGroup({
        emails: new FormArray([new FormControl('')])
    });

    public formArrayEmails: FormArray = new FormArray([new FormControl('')]);

    private destroy$ = new Subject<void>();

    constructor(
        private formBuilder: FormBuilder,
        private router: Router,
        private route: ActivatedRoute,
        private api: ApiService,
        private app: AppService
    ) {}

    public ngOnInit() {
        this.initFormEmails();

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
                    next: ({bookingInspection, consumptionMeterReadings}) => {
                        if(bookingInspection) {
                            this.handleBookingInspection(bookingInspection);

                            const booking = bookingInspection.booking_id as Booking;
                            this.loadBookingContactEmailList(booking.id);
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

    private initFormEmails() {
        this.formArrayEmails = this.formBuilder.array([this.createEmailControl()]);

        this.formEmailList = this.formBuilder.group({
            emails: this.formArrayEmails
        });
    }

    private handleBookingInspection(bookingInspection: BookingInspection) {
        this.bookingInspection = bookingInspection;

        const booking = bookingInspection.booking_id as Booking;
        const center = booking.center_id as Center;
        const customerIdentity = booking.customer_identity_id as CustomerIdentity;

        this.booking = {
            name: booking.name,
            center_name: center.name,
            date_from: this.formatDate(booking.date_from),
            customer_identity_id: customerIdentity.id,
            customer_display_name: customerIdentity.display_name
        };

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
                meterUnit: consumptionMeter.meter_unit,
                indexValue: consMeterReading.index_value,
                unitPrice: consMeterReading.unit_price,
            };
        });
    }

    private loadBookingContactEmailList(bookingId: number) {
        this.api.fetchBookingContactsByBookingId(bookingId).subscribe(
            (contacts) => {
                const emailList: string[] = [];
                let defaultContact: BookingContact|null = null;
                for(let contact of contacts) {
                    if(
                        contact.partner_identity_id
                        && typeof contact.partner_identity_id !== 'number'
                        && typeof contact.partner_identity_id.email === 'string'
                        && contact.partner_identity_id.email.length > 0
                    ) {
                        emailList.push(contact.partner_identity_id.email);

                        if(!defaultContact) {
                            defaultContact = contact;
                        }
                        else if(defaultContact.type !== 'booking' && contact.type === 'booking') {
                            defaultContact = contact;
                        }
                    }
                }

                this.bookingContactEmailList = emailList;

                if(defaultContact && typeof defaultContact.partner_identity_id !== 'number') {
                    this.formArrayEmails.controls[0].setValue(defaultContact.partner_identity_id.email);
                }
            },
            (error) => {
                console.error('Error fetching booking contacts', error);
            }
        );
    }

    public ngOnDestroy() {
        this.destroy$.next();
        this.destroy$.complete();
    }

    public createEmailControl(): FormControl {
        return this.formBuilder.control('', [Validators.required, Validators.email]);
    }

    public addEmailControl() {
        const formArrayEmails = this.formEmailList.get('emails') as FormArray;
        formArrayEmails.push(this.createEmailControl());

        this.formArrayEmails = formArrayEmails;

        setTimeout(() => {
            const elLastEmailContainer = document.getElementById(
                'booking-inspection-confirm__email-container-'+ (this.formArrayEmails.length - 1)
            );

            elLastEmailContainer?.scrollIntoView({ behavior: 'smooth' });
        }, 0);
    }

    public removeEmailControl(index: number) {
        const formArrayEmails = this.formEmailList.get('emails') as FormArray;
        formArrayEmails.removeAt(index)
    }

    public send() {
        this.formEmailList.updateValueAndValidity();
        if(this.formEmailList.valid) {
            const emails: string[] = [...new Set(
                this.formArrayEmails.controls.map(control => control.value)
            )];

            if(this.bookingInspection) {
                this.loading = true;
                this.api.doBookingInspectionSubmit(this.bookingInspection.id, emails)
                    .pipe(finalize(() => {
                        this.loading = false;
                    }))
                    .subscribe({
                        next: () => {
                            this.router.navigate(['/bookings/pending']);
                        },
                        error: (error) => {
                            console.error('Error submitting booking inspection', error);
                        }
                    });
            }
            else {
                console.error('Booking inspection not correctly loaded');
            }
        }
        else {
            this.formEmailList.markAllAsTouched();
        }
    }
}
