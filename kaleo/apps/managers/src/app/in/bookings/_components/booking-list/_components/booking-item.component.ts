import { Component, Input, OnInit } from '@angular/core';

@Component({
    selector: 'app-booking-item',
    templateUrl: 'booking-item.component.html',
    styleUrls: ['booking-item.component.scss']
})
export class BookingItemComponent implements OnInit {

    @Input() name: string;
    @Input() date_from: string;
    @Input() date_to: string;
    @Input() customer_id: number;
    @Input() customer_display_name: string;

    public dateFrom: Date;
    public dateTo: Date;
    public nb_nights: number;

    constructor() {
    }

    public ngOnInit() {

        this.dateFrom = new Date(this.date_from);
        this.dateTo = new Date(this.date_to);

        const start = new Date(this.dateFrom.getFullYear(), this.dateFrom.getMonth(), this.dateFrom.getDate());
        const end = new Date(this.dateTo.getFullYear(), this.dateTo.getMonth(), this.dateTo.getDate());
        const time_diff = end.getTime() - start.getTime();

        this.nb_nights = Math.floor(time_diff / (1000 * 60 * 60 * 24));
    }

    public formatDate(date: Date): string {
        const formatter = new Intl.DateTimeFormat('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
        return formatter.format(date);
    }
}
