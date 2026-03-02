import { Component, Inject, OnInit } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { MatDialog } from '@angular/material/dialog';

@Component({
    selector: 'dialog-help',
    templateUrl: './dialog-help.component.html',
    styleUrls: ['./dialog-help.component.scss']
})
export class DialogHelpComponent implements OnInit  {
    public consent_checked: boolean = false;

    constructor(
            public dialog: MatDialog,
            public dialogRef: MatDialogRef<DialogHelpComponent>,
            @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    public ngOnInit() {

    }


}
