import { Component, EventEmitter, Input, OnInit, Output } from '@angular/core';
import { FormArray, FormGroup } from '@angular/forms';

@Component({
    selector: 'app-recipient-list-form',
    templateUrl: 'recipient-list-form.component.html',
    styleUrls: ['recipient-list-form.component.scss']
})
export class RecipientListFormComponent implements OnInit {

    @Input() form: FormGroup;
    @Input() formArray: FormArray;
    @Input() bookingContactEmailList: string[];

    @Output() removeIndex = new EventEmitter();

    constructor() {
    }

    public ngOnInit() {
    }
}
