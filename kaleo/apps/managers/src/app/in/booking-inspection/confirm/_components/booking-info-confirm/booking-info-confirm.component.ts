import { Component, Input, OnInit } from '@angular/core';

@Component({
    selector: 'app-booking-info-confirm',
    templateUrl: 'booking-info-confirm.component.html',
    styleUrls: ['booking-info-confirm.component.scss']
})
export class BookingInfoConfirmComponent implements OnInit {

    @Input() bookingName: string;
    @Input() centerName: string;
    @Input() date: string;
    @Input() customerId: number;
    @Input() customerDisplayName: string;

    constructor() {
    }

    public ngOnInit() {
    }
}
