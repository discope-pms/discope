import { Component, Input, OnInit } from '@angular/core';

@Component({
    selector: 'booking-details',
    templateUrl: './booking-details.component.html',
    styleUrls: ['./booking-details.component.scss']
})
export class BookingDetailsComponent implements OnInit {

    @Input() bookingName: string;
    @Input() centerName: string;
    @Input() centerPhone: string;
    @Input() centerEmail: string;

    constructor() {
    }

    public ngOnInit() {
    }
}
