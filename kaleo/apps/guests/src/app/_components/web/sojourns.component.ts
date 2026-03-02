import { Component, EventEmitter, Input, OnInit, Output, SimpleChanges } from '@angular/core';
import { BookingLineGroup, GuestListItem } from '../../../type';
import { MatDialog } from '@angular/material/dialog';
import { GuestFormDialogComponent } from './guest/guest-form-dialog.component';
import { GuestBulkFormDialogComponent } from './guest/guest-bulk-form-dialog.component';

type SelectionActions = 'choose-action' | 'delete' | 'bulk-assign'

@Component({
    selector: 'sojourns',
    templateUrl: './sojourns.component.html',
    styleUrls: ['./sojourns.component.scss']
})
export class SojournsComponent implements OnInit  {

    @Input() bookingLinesGroups: BookingLineGroup[];
    @Input() guestListItems: GuestListItem[];
    @Input() disabled: boolean;

    @Output() deleteGuestListItem = new EventEmitter();
    @Output() addGuestListItem = new EventEmitter();
    @Output() updateGuestListItem = new EventEmitter();

    public openedGroupId = 0;

    public mapGroupGuestListItem: { [key: number]: GuestListItem[] };

    public selectedGuestListItemIds: number[] = [];
    public selectedAction: SelectionActions = 'choose-action';
    public selectionActionOptions: { value: SelectionActions, label: string }[] = [
            { value: 'choose-action', label: 'CHOOSE_SELECTION_ACTION' },
            { value: 'delete', label: 'DELETE_SELECTION_ACTION' },
            /* { value: 'bulk-assign', label: 'BULK_ASSIGN_SELECTION_ACTION' } */
        ];

    constructor(
            public dialog: MatDialog
        ) {}

    public ngOnInit() {
    }

    public ngOnChanges(changes: SimpleChanges) {
        if(changes.bookingLinesGroups && this.bookingLinesGroups.length === 1) {
            this.openedGroupId = this.bookingLinesGroups[0].id;
        }
        if(changes.guestListItems) {
            this.updateMapGroupGuestListItem(this.guestListItems);
        }
    }

    public expandClicked(groupId: number) {
        if(this.openedGroupId === groupId) {
            this.openedGroupId = 0;
        }
        else {
            this.openedGroupId = groupId;
        }
    }

    public openEditGuestDialog(guestId: number) {
        let guest: GuestListItem|null = null;
        for(const g of this.guestListItems) {
            if(g.id === guestId) {
                guest = {...g};
                break;
            }
        }

        if(guest !== null) {
            const dialogRef = this.dialog.open(GuestFormDialogComponent, {
                width: '500px',
                data: guest
            });

            const guestId = guest.id;
            dialogRef.afterClosed().subscribe(result => {
                if(result) {
                    this.updateGuestListItem.emit({ id: guestId, values: result });
                }
            });
        }
    }

    public openBulkEditGuestDialog(guestIds: number[]) {

        if(guestIds.length === 0) {
            return;
        }

        let guest: GuestListItem|null = null;
        for(const g of this.guestListItems) {
            if(g.id === guestIds[0]) {
                guest = {...g};
                break;
            }
        }

        const dialogRef = this.dialog.open(GuestBulkFormDialogComponent, {
                width: '500px',
                data: {
                    address_street: guest?.address_street,
                    address_zip: guest?.address_zip,
                    address_city: guest?.address_city,
                    address_country: guest?.address_country
                }
            });

        dialogRef.afterClosed().subscribe(result => {
                if(result) {
                    this.updateGuestListItem.emit({ id: guestIds, values: result });
                }
                this.selectedAction = 'choose-action';
            });
    }

    public doSelectionAction() {
        switch (this.selectedAction) {
            case 'delete':
                this.deleteGuestListItem.emit(this.selectedGuestListItemIds);
                break;
            case 'bulk-assign':
                this.openBulkEditGuestDialog(this.selectedGuestListItemIds);
                break;
        }
    }

    private updateMapGroupGuestListItem(guestList: GuestListItem[]) {
        const mapGroupGuestListItem: { [key: number]: GuestListItem[] } = {};

        this.bookingLinesGroups.forEach(group => {
            mapGroupGuestListItem[group.id] = [];
        })

        guestList.forEach((guest) => {
            mapGroupGuestListItem[guest.booking_line_group_id].push(guest);
        });

        this.mapGroupGuestListItem = mapGroupGuestListItem;
    }
}
