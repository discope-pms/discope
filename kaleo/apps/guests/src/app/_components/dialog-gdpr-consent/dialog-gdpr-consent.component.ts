import { Component, Inject, OnInit } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { MatDialog } from '@angular/material/dialog';
import { DialogGdprNoticeComponent } from '../dialog-gdpr-notice/dialog-gdpr-notice.component';

@Component({
    selector: 'dialog-gdpr-consent',
    templateUrl: './dialog-gdpr-consent.component.html',
    styleUrls: ['./dialog-gdpr-consent.component.scss']
})
export class DialogGdprConsentComponent implements OnInit  {
    public consent_checked: boolean = false;

    constructor(
            public dialog: MatDialog,
            public dialogRef: MatDialogRef<DialogGdprConsentComponent>,
            @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    public ngOnInit() {
    }

    public showNotice() {
        const dialogRef = this.dialog.open(DialogGdprNoticeComponent, {
                width: '500px',
                maxHeight: '750px',
            });

        dialogRef.afterClosed().subscribe(async (result) => {
                if(result) {

                }
            });
    }

    public submit() {
        if(this.consent_checked) {
            this.dialogRef.close();
        }
    }

}
