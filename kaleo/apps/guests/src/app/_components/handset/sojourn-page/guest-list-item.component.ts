import { Component, EventEmitter, Input, OnChanges, OnInit, Output, SimpleChanges } from '@angular/core';
import { GuestListItem } from '../../../../type';
import { countries } from '../../../../assets/data/countries';

@Component({
    selector: 'guest-list-item',
    templateUrl: 'guest-list-item.component.html',
    styleUrls: ['guest-list-item.component.scss']
})
export class GuestListItemComponent implements OnInit, OnChanges {

    @Input() number: number;
    @Input() guest: GuestListItem;
    @Input() disabled: boolean;

    @Output() editGuestListItem = new EventEmitter();
    @Output() deleteGuestListItem = new EventEmitter();

    public mapCountries = countries;
    public guestDateOfBirth: string = '';

    constructor(
    ) {}

    public ngOnInit() {
    }

    public ngOnChanges(changes: SimpleChanges) {
        if(changes.guest && this.guest && this.guest.date_of_birth) {
            const date_of_birth = (typeof this.guest.date_of_birth == 'string') ? (new Date(this.guest.date_of_birth)) : (new Date(this.guest.date_of_birth * 1000));
            this.guestDateOfBirth = date_of_birth.toISOString()
                .slice(0, 10)
                .split('-')
                .reverse()
                .join('/');
        }
    }

    protected readonly countries = countries;
}
