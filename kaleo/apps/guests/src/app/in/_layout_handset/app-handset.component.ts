import { Component, EventEmitter, Input, OnInit, Output, SimpleChanges } from '@angular/core';
import { Booking, BookingLineGroup, GuestListItem } from '../../../type';

@Component({
    selector: 'app-handset',
    templateUrl: 'app-handset.component.html',
    styleUrls: ['app-handset.component.scss']
})
export class AppHandsetComponent implements OnInit  {

    @Input() booking: Booking;
    @Input() completionPercentage: number;
    @Input() isSubmittable: boolean;

    @Output() deleteGuestListItem = new EventEmitter();
    @Output() addGuestListItem = new EventEmitter();
    @Output() updateGuestListItem = new EventEmitter();
    @Output() submitGuestList = new EventEmitter();
    @Output() showHelp = new EventEmitter();

    public selectedGroup: BookingLineGroup|null = null;
    public selectedGuest: GuestListItem|null = null;

    public mapGroupGuestListItem: { [key: number]: GuestListItem[] };
    public mapGroupCompletedGuestListItemQty: { [key: number]: number };

    constructor(
    ) {}

    public ngOnInit() {
    }

    public ngOnChanges(changes: SimpleChanges) {
        if(changes.booking && this.booking) {
            this.updateMapGroupGuestListItem(Object.values(this.booking.guest_list_id.guest_list_items_ids));
        }
    }

    public openGroup(bookingLineGroupId: number) {
        this.selectedGroup = this.booking.booking_lines_groups_ids.find(g => g.id === bookingLineGroupId) ?? null;
    }

    public closeGroup() {
        this.selectedGroup = null;
    }

    public editGuestListItem(guestListItemId: number) {
        this.selectedGuest = Object.values(this.booking.guest_list_id.guest_list_items_ids).find(g => g.id === guestListItemId) ?? null;
    }

    public closeGuest() {
        this.selectedGuest = null;
    }

    private updateMapGroupGuestListItem(guestList: GuestListItem[]) {
        const mapGroupGuestListItem: { [key: number]: GuestListItem[] } = {};

        this.booking.booking_lines_groups_ids.forEach(group => {
            mapGroupGuestListItem[group.id] = [];
        })

        guestList.forEach((guest) => {
            mapGroupGuestListItem[guest.booking_line_group_id].push(guest);
        });

        this.mapGroupGuestListItem = mapGroupGuestListItem;

        this.updateMapCompletedGroupGuestListItemQty();
    }

    private updateMapCompletedGroupGuestListItemQty() {
        const mapGroupCompletedGuestListItemQty: { [key: number]: number } = {};
        Object.entries(this.mapGroupGuestListItem).forEach(([id, guestListItems]) => {
            const toCompleteFields: Array<'firstname'|'lastname'|'date_of_birth'> = ['firstname', 'lastname', 'date_of_birth'];
            let completedGuestsQty = 0;
            Object.values(guestListItems).forEach(guest => {
                let isGuestComplete = true;
                for(let field of toCompleteFields) {
                    if(field === 'date_of_birth') {
                        if(guest[field] === null) {
                            isGuestComplete = false;
                            break;
                        }
                    }
                    else {
                        if(guest[field] === null || guest[field]?.length === 0) {
                            isGuestComplete = false;
                            break;
                        }
                    }
                }

                if(isGuestComplete) {
                    completedGuestsQty++;
                }
            });

            mapGroupCompletedGuestListItemQty[parseInt(id)] = completedGuestsQty;
        });

        this.mapGroupCompletedGuestListItemQty = mapGroupCompletedGuestListItemQty;
    }
}
