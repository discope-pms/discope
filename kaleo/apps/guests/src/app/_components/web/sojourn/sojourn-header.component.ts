import { Component, Input, Output, OnInit, EventEmitter } from '@angular/core';

@Component({
    selector: 'sojourn-header',
    templateUrl: 'sojourn-header.component.html',
    styleUrls: ['sojourn-header.component.scss']
})
export class SojournHeaderComponent implements OnInit  {

    @Input() opened: boolean;
    @Input() dateFrom: string;
    @Input() dateTo: string;
    @Input() guestCount: number;
    @Input() nbPers: number;
    @Input() nbAdults: number;
    @Input() nbChildren: number;

    @Output() expand = new EventEmitter();

    constructor(
    ) {}

    public ngOnInit() {
    }

    public expandClicked() {
        this.expand.emit();
    }
}
