import { Component, Input, Output, OnInit, EventEmitter, SimpleChanges } from '@angular/core';
import { GuestListItem } from '../../../../type';
import { countries } from '../../../../assets/data/countries';
import { MatSlideToggleChange } from '@angular/material/slide-toggle';

interface GuestListItemExtended extends GuestListItem {
    date_of_birth_string: string|null
}

@Component({
    selector: 'sojourn-guest-table',
    templateUrl: 'sojourn-guest-table.component.html',
    styleUrls: ['sojourn-guest-table.component.scss']
})
export class SojournGuestTableComponent implements OnInit  {

    @Input() guestList: GuestListItem[];
    @Input() disabled: boolean;
    @Input() selectedGuestListItemIds: number[];

    @Output() deleteGuestListItem = new EventEmitter();
    @Output() updateGuestListItem = new EventEmitter();
    @Output() editGuestListItem = new EventEmitter();
    @Output() selectedGuestListItemIdsChange = new EventEmitter();

    public selectionAll: boolean = false;
    public selectionGuests: { [key: number]: boolean } = {};
    public guestListExtended: GuestListItemExtended[];
    public countryOptions: { [key: string]: string } = countries;
    public citizenIdentificationInputDisabled: { [key: number]: boolean } = {};

    constructor() {
    }

    public ngOnInit() {
    }

    async ngOnChanges(changes: SimpleChanges) {
        if(changes.guestList) {
            this.selectionGuests = {};
            this.guestList.forEach(guest => {
                this.selectionGuests[guest.id] = false;

                let isLessThan15YearOld = false;
                if(guest.date_of_birth) {
                    const date_of_birth = (typeof guest.date_of_birth == 'string') ? (new Date(guest.date_of_birth)) : (new Date(guest.date_of_birth * 1000));
                    isLessThan15YearOld = this.isLessThanFifteenYearsOld(new Date(date_of_birth));
                }

                this.citizenIdentificationInputDisabled[guest.id] = guest.address_country !== 'BE' || isLessThan15YearOld;
            });

            this.guestListExtended = [];
            this.guestList.forEach(guest => {
                let dateString: string|null = null;
                if(guest.date_of_birth) {
                    const date_of_birth = (typeof guest.date_of_birth == 'string') ? (new Date(guest.date_of_birth)) : (new Date(guest.date_of_birth * 1000));
                    dateString = date_of_birth.toISOString().split('T')[0];
                }

                this.guestListExtended.push({
                    ...guest,
                    date_of_birth_string: dateString
                });
            });
        }
    }

    private isLessThanFifteenYearsOld(dateOfBirth: Date|null) {
        if(!dateOfBirth) {
            return false;
        }

        const today = new Date();
        const fifteenYearsAgo = new Date();
        fifteenYearsAgo.setFullYear(today.getFullYear() - 15);

        return dateOfBirth > fifteenYearsAgo;
    }

    public selectionAllChange() {
        this.selectionAll = !this.selectionAll;

        this.guestList.forEach(guest => {
            this.selectionGuests[guest.id] = this.selectionAll;
        });

        this.updateSelectedGuestIds();
    }

    public selectionGuestChange(id: number) {
        this.selectionGuests[id] = !this.selectionGuests[id];

        this.selectionAll = Object.values(this.selectionGuests).every(x => x);

        this.updateSelectedGuestIds();
    }

    private updateSelectedGuestIds() {
        const selectedGuestIds: number[] = [];
        Object.entries(this.selectionGuests).forEach(([id, isSelected]) => {
            if(isSelected) {
                selectedGuestIds.push(parseInt(id));
            }
        });

        this.selectedGuestListItemIdsChange.emit(selectedGuestIds);
    }

    public editClicked(id: number) {
        this.editGuestListItem.emit(id);
    }

    public deleteClicked(id: number) {
        this.deleteGuestListItem.emit(id);
    }

    public updateStringFieldValue(id: number, field: 'firstname'|'lastname'|'citizen_identification', focusEvent: FocusEvent) {
        if(focusEvent.target === null) {
            return;
        }

        const target = focusEvent.target as HTMLInputElement;

        this.updateFieldValue(id, field, target.value);
    }

    public updateToggleFieldValue(id: number, field: 'is_coordinator', event: MatSlideToggleChange) {
        console.log(event);
        this.updateFieldValue(id, field, event.checked ? 1 : 0);
    }

    public updateSelectFieldValue(id: number, field: 'address_country', value: string) {
        if(field === 'address_country') {
            const notBelgium = value !== 'BE';
            this.citizenIdentificationInputDisabled[id] = notBelgium;
            if(notBelgium) {
                const values: Partial<GuestListItem> = {
                    address_country: value,
                    citizen_identification: ''
                };
                this.updateGuestListItem.emit({ id, values });
                return;
            }
        }

        this.updateFieldValue(id, field, value);
    }

    public updateDateOfBirthFieldValue(id: number, event: any) {
        if(event.target === null) {
            return;
        }

        const date = event.target.value as Date;
        if(this.isLessThanFifteenYearsOld(date)) {
            this.updateGuestListItem.emit({
                id,
                values: {
                    date_of_birth: date?.toISOString(),
                    citizen_identification: ''
                }
            });
            return;
        }

        this.updateFieldValue(id, 'date_of_birth', date?.toISOString());

        if(this.citizenIdentificationInputDisabled[id]) {
            this.citizenIdentificationInputDisabled[id] = false;
        }
    }

    private updateFieldValue(id: number, field: 'firstname'|'lastname'|'is_coordinator'|'citizen_identification'|'address_country'|'date_of_birth', value: string|number) {
        const values: Partial<GuestListItem> = {};
        // @ts-ignore
        values[field] = value;

        this.updateGuestListItem.emit({ id, values });
    }
}
