import { Component, EventEmitter, Input, OnInit, Output } from '@angular/core';
import { Booking } from '../../../type';
import { MatDialog } from '@angular/material/dialog';
import { DialogDownloadComponent } from '../dialog-download/dialog-download.component';
@Component({
    selector: 'sojourns-page',
    templateUrl: './sojourns-page.component.html',
    styleUrls: ['./sojourns-page.component.scss']
})
export class SojournsPageComponent implements OnInit  {

    @Input() booking: Booking;
    @Input() mapGroupCompletedGuestListItemQty: { [key: number]: number };
    @Input() isSubmittable: boolean;

    @Output() openGroup = new EventEmitter();
    @Output() submitGuestList = new EventEmitter();
    @Output() showHelp = new EventEmitter();

    constructor(public dialog: MatDialog) {}

    public async ngOnInit() {
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
