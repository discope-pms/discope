import { Component, EventEmitter, Input, Output, OnInit, SimpleChanges } from '@angular/core';
import { Booking, BookingLineGroup, GuestListItem, GuestUser } from '../../../type';
import { AuthService } from '../../_services/AuthService';
import { MatDialog } from '@angular/material/dialog';
import { DialogDownloadComponent } from 'src/app/_components/dialog-download/dialog-download.component';
@Component({
    selector: 'app-web',
    templateUrl: 'app-web.component.html',
    styleUrls: ['app-web.component.scss']
})
export class AppWebComponent implements OnInit  {

    @Input() booking: Booking;
    @Input() completionPercentage: number;
    @Input() isSubmittable: boolean;

    @Output() deleteGuestListItem = new EventEmitter();
    @Output() addGuestListItem = new EventEmitter();
    @Output() updateGuestListItem = new EventEmitter();
    @Output() submitGuestList = new EventEmitter();
    @Output() showHelp = new EventEmitter();

    public bookingLinesGroups: BookingLineGroup[] = [];

    public guestListItems: GuestListItem[] = [];

    public guestUser: GuestUser;

    public centerAddress: string;

    public loading: boolean = false;

    constructor(
            public dialog: MatDialog,
            private auth: AuthService
        ) {}

    public ngOnInit() {
        this.auth.getObservable().subscribe((guestUser: GuestUser) => {
            this.guestUser = guestUser;
        });
    }

    public ngOnChanges(changes: SimpleChanges) {
        if(changes.booking && this.booking) {
            const center = this.booking.center_id;
            this.centerAddress = `${center.address_street}, ${center.address_zip} ${center.address_city}, ${center.address_country}`;

            this.bookingLinesGroups = this.booking.booking_lines_groups_ids;

            this.guestListItems = Object.values(this.booking.guest_list_id.guest_list_items_ids);
        }
    }

    public requestDownload() {
        if(!this.booking || this.booking.guest_list_id.status === 'pending') {
            return ;
        }

        const dialogRef = this.dialog.open(DialogDownloadComponent, {
                width: '500px',
                autoFocus: false
            });
    }

}
