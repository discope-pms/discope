import { Component, Input, OnInit, OnChanges, SimpleChanges, Output, EventEmitter } from '@angular/core';
import { Booking, CustomerIdentity } from '../../../../../type';

interface BookingItem {
    id: number,
    name: string,
    date_from: string,
    date_to: string,
    customer_id: number,
    customer_display_name: string
}

@Component({
    selector: 'app-booking-list',
    templateUrl: 'booking-list.component.html',
    styleUrls: ['booking-list.component.scss']
})
export class BookingListComponent implements OnInit, OnChanges {

    @Input() bookingList: Booking[] = [];

    @Output() itemClick = new EventEmitter();

    public bookingItemList: BookingItem[] = [];

    constructor() {
    }

    public ngOnInit() {
    }

    public ngOnChanges(changes: SimpleChanges) {
        if(changes.bookingList) {
            const bookingItemList: BookingItem[] = [];
            this.bookingList.forEach(booking => {
                const customerIdentity = booking.customer_identity_id as CustomerIdentity;

                bookingItemList.push({
                    id: booking.id,
                    name: booking.name,
                    date_from: booking.date_from,
                    date_to: booking.date_to,
                    customer_id: customerIdentity.id,
                    customer_display_name: customerIdentity.display_name
                });
            });

            this.bookingItemList = bookingItemList;
        }
    }

}
