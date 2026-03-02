import { Component, EventEmitter, Inject, Input, OnInit, Output } from '@angular/core';


@Component({
    selector: 'select-option-popup',
    templateUrl: './select-option-popup.component.html',
    styleUrls: ['./select-option-popup.component.scss']
})
export class SelectOptionPopupComponent implements OnInit  {

    @Input() options: {label: string, value: any}[];

    @Output() select = new EventEmitter();

    constructor() {
    }

    public ngOnInit() {
    }

    public onSelect(option: any): void {
        this.select.emit(option);
    }

}