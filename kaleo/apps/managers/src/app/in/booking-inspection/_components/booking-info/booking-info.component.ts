import { Component, Input, OnInit } from '@angular/core';

@Component({
    selector: 'app-booking-info',
    templateUrl: './booking-info.component.html',
    styleUrls: ['./booking-info.component.scss']
})
export class BookingInfoComponent implements OnInit {

    @Input() dateFrom: string;
    @Input() dateTo: string;
    @Input() customerId: number;
    @Input() customerDisplayName: string;

    constructor() { }

    public ngOnInit() {
    }
}
