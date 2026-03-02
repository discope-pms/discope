import { Component, OnInit } from '@angular/core';
import { MatDialogRef } from '@angular/material/dialog';

@Component({
    selector: 'guest-list-incomplete-dialog',
    templateUrl: 'guest-list-incomplete-dialog.component.html',
    styleUrls: ['guest-list-incomplete-dialog.component.scss'],
})
export class GuestListIncompleteDialogComponent implements OnInit {

    constructor(
        public dialogRef: MatDialogRef<GuestListIncompleteDialogComponent>,
    ) {}

    public ngOnInit() {
    }

    public cancel() {
        this.dialogRef.close();
    }

    public submit() {
        this.dialogRef.close(true);
    }
}
