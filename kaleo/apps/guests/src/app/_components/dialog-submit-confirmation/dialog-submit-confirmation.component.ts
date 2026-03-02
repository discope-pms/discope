import { Component, Inject, OnInit } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { FormBuilder, FormControl, FormGroup } from '@angular/forms';

@Component({
    selector: 'dialog-submit-confirmation',
    templateUrl: './dialog-submit-confirmation.component.html',
    styleUrls: ['./dialog-submit-confirmation.component.scss']
})
export class DialogSubmitConfirmationComponent implements OnInit  {

    constructor(
            public dialogRef: MatDialogRef<DialogSubmitConfirmationComponent>,
            @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    public ngOnInit() {
    }

}
