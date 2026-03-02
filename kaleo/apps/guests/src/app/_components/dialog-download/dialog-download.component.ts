import { Component, Inject, OnInit } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { MatDialog } from '@angular/material/dialog';

@Component({
    selector: 'dialog-download',
    templateUrl: './dialog-download.component.html',
    styleUrls: ['./dialog-download.component.scss']
})
export class DialogDownloadComponent implements OnInit  {

    constructor(
            public dialog: MatDialog,
            @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    public ngOnInit() {

    }

    public download() {
        window.open('/?get=sale_booking_guests_export-xls', '_blank');
        this.dialog.closeAll();
    }

}
