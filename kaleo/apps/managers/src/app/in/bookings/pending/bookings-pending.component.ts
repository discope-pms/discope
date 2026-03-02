import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { Booking } from '../../../../type';
import { ApiService } from '../../../_services/api.service';
import { AppService } from '../../../_services/app.service';
import { Subject } from 'rxjs';
import { finalize, takeUntil } from 'rxjs/operators';

@Component({
    selector: 'app-bookings-pending',
    templateUrl: 'bookings-pending.component.html',
    styleUrls: ['bookings-pending.component.scss']
})
export class BookingsPendingComponent implements OnInit  {

    public bookingList: Booking[] = [];

    public loading: boolean = true;

    private destroy$: Subject<void> = new Subject<void>();

    constructor(
        private router: Router,
        private api: ApiService,
        private app: AppService
    ) {}

    public ngOnInit() {
        this.app.center$.pipe(takeUntil(this.destroy$)).subscribe((center) => {
            if (!center) return;

            this.loading = true;
            this.api.fetchPendingBookings(center.id).pipe(takeUntil(this.destroy$))
                .pipe(takeUntil(this.destroy$))
                .pipe(finalize(() => {
                    this.loading = false;
                }))
                .subscribe({
                    next: (data) => {
                        this.bookingList = data;
                    },
                    error: (error) => {
                        console.error('Error fetching pending bookings:', error);
                    }
                });
        });
    }

    public ngOnDestroy() {
        this.destroy$.next();
        this.destroy$.complete();
    }

    public goTo(bookingId: number) {
        this.loading = true;
        this.api.fetchCheckedoutBookingInspectionByBookingId(bookingId)
            .subscribe({
                next: (bookingInspection) => {
                    if(!bookingInspection) {
                        this.api.createBookingInspection({ booking_id: bookingId, type_inspection: 'checkedout' })
                            .pipe(finalize(() => {
                                this.loading = false;
                            }))
                            .subscribe({
                                next: (bookingInspection) => {
                                    this.router.navigate([`/booking-inspection/${bookingInspection.id}`]);
                                },
                                error: (error) => {
                                    console.error('Error creating booking inspection:', error);
                                }
                            });
                    }
                    else {
                        this.router.navigate([`/booking-inspection/${bookingInspection.id}`]);
                        this.loading = false;
                    }
                },
                error: (error) => {
                    console.error('Error fetching checkedin booking inspection:', error);
                    this.loading = false;
                }
            });
    }

    public goToUpcomingBookings() {
        this.router.navigate(['/bookings/upcoming']);
    }
}
