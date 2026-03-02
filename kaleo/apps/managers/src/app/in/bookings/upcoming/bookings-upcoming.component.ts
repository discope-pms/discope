import { Component, OnInit } from '@angular/core';
import { Booking, Center } from '../../../../type';
import { ApiService } from '../../../_services/api.service';
import { AppService } from '../../../_services/app.service';
import { Subject } from 'rxjs';
import { finalize, takeUntil } from 'rxjs/operators';
import { Router } from '@angular/router';

@Component({
    selector: 'app-bookings-upcoming',
    templateUrl: 'bookings-upcoming.component.html',
    styleUrls: ['bookings-upcoming.component.scss']
})
export class BookingsUpcomingComponent implements OnInit  {

    public bookingList: Booking[] = [];

    public loading: boolean = true;

    public lastBookingDate: string|null = null;

    private selectedCenter: Center|null = null;

    private destroy$ = new Subject<void>();

    constructor(
        private router: Router,
        private api: ApiService,
        private app: AppService
    ) {}

    public ngOnInit() {
        this.app.center$.pipe(takeUntil(this.destroy$)).subscribe((center) => {
            this.selectedCenter = center;

            if (!center) return;

            this.loading = true;
            this.api.fetchUpcomingBookings(center.id)
                .pipe(takeUntil(this.destroy$))
                .pipe(finalize(() => {
                    this.loading = false;
                }))
                .subscribe({
                    next: (data) => {
                        this.bookingList = data;

                        if(data.length > 0) {
                            this.lastBookingDate = data[data.length - 1].date_to;
                        }
                        else {
                            this.lastBookingDate = null;
                        }
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
        this.api.fetchCheckedinBookingInspectionByBookingId(bookingId).subscribe(
            (bookingInspection) => {
                if(!bookingInspection) {
                    this.api.createBookingInspection({ booking_id: bookingId, type_inspection: 'checkedin' })
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
                }
            },
            (error) => {
                console.error('Error fetching checkedout booking inspection:', error);
            });
    }

    public downloadUpcomingBookingsPdf() {
        if(this.selectedCenter) {
            this.loading = true;
            this.api.getUpcomingBookingsPdf(this.selectedCenter.id, this.lastBookingDate ?? '')
                .pipe(takeUntil(this.destroy$))
                .pipe(finalize(() => {
                    this.loading = false;
                }))
                .subscribe({
                    next: (data) => {
                        const downloadURL = window.URL.createObjectURL(data);
                        const link = document.createElement('a');
                        link.href = downloadURL;
                        link.target = "_blank";
                        // link.download = 'booking-arrivals-' + this.selectedCenter?.name + '.pdf';
                        link.click();
                    },
                    error: (error) => {
                        if (error.status === 404) {
                            console.log('No booking');
                        }
                        else {
                            console.error('Error fetching upcoming pdf:', error);
                        }
                    }
                });
        }
    }
}
