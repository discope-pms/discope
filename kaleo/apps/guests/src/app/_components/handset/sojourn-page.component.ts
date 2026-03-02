import { Component, EventEmitter, Input, OnInit, Output } from '@angular/core';
import { BookingLineGroup, GuestListItem } from '../../../type';

@Component({
    selector: 'sojourn-page',
    templateUrl: './sojourn-page.component.html',
    styleUrls: ['./sojourn-page.component.scss']
})
export class SojournPageComponent implements OnInit  {

    @Input() title: string;
    @Input() group: BookingLineGroup;
    @Input() guestList: GuestListItem[];
    @Input() disabled: boolean;

    @Output() back = new EventEmitter();
    @Output() editGuestListItem = new EventEmitter();
    @Output() deleteGuestListItem = new EventEmitter();
    @Output() addGuestListItem = new EventEmitter();

    constructor(
    ) {}

    public async ngOnInit() {
    }
}
