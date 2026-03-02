import { Component, Input, OnInit } from '@angular/core';

@Component({
    selector: 'booking-information',
    templateUrl: './booking-information.component.html',
    styleUrls: ['./booking-information.component.scss']
})
export class BookingInformationComponent implements OnInit  {

    @Input() bookingName: string;
    @Input() centerName: string;
    @Input() centerAddress: string;
    @Input() centerPhone: string;
    @Input() centerEmail: string;

    constructor(
    ) {}

    public ngOnInit() {
    }
}
