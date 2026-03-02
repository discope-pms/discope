import { Component, Inject, OnInit } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { FormBuilder, FormControl, FormGroup } from '@angular/forms';

@Component({
    selector: 'dialog-gdpr-notice',
    templateUrl: './dialog-gdpr-notice.component.html',
    styleUrls: ['./dialog-gdpr-notice.component.scss']
})
export class DialogGdprNoticeComponent implements OnInit  {

    constructor(
            public dialogRef: MatDialogRef<DialogGdprNoticeComponent>,
            @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    public ngOnInit() {
    }

}
