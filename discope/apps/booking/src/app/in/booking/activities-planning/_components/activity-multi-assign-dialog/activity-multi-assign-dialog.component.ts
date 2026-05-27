import { Component, Inject, OnInit } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { FormControl } from '@angular/forms';
import { Observable, ReplaySubject } from 'rxjs';
import { debounceTime, map, mergeMap } from 'rxjs/operators';
import { BookingApiService } from '../../../_services/booking.api.service';
import { Booking } from '../../_models/booking.model';
import { BookingLineGroup } from '../../_models/booking-line-group.model';
import { Product } from '../../_models/product.model';
import { Planning } from '../../activities-planning.component';

export interface BookingActivitiesPlanningActivityMultiAssignDialogData {
    center_id: number,
    rate_class_id: number,
    booking: Booking,
    groups: BookingLineGroup[],
    planning: Planning,
    selectedItemsMap: {[day: string]: {[timeSlot: string]: {[groupNum: number]: boolean}}};
    mapTimeSlotIdCode: {[key: number]: 'AM'|'PM'|'EV'};
}

interface vmModel {
    product: {
        name: string,
        formControl: FormControl,
        inputClue: ReplaySubject <any> ,
        filteredList: Observable <any> ,
        inputChange: (event: any) => void,
        focus: () => void,
        restore: () => void,
        display: (type: any) => string
    }
}

@Component({
    selector: 'booking-activities-planning-activity-multi-assign-dialog',
    templateUrl: 'activity-multi-assign-dialog.component.html',
    styleUrls: ['activity-multi-assign-dialog.component.scss']
})
export class BookingActivitiesPlanningActivityMultiAssignDialogComponent implements OnInit {

    private center_id: number;
    private rate_class_id: number;
    private booking: Booking;
    private groups: BookingLineGroup[];
    private planning: Planning = {};
    private selectedItemsMap: {[day: string]: {[timeSlot: string]: {[groupNum: number]: boolean}}};
    private mapTimeSlotIdCode: {[key: number]: 'AM'|'PM'|'EV'};

    public vm: vmModel;

    public product: Product|null = null;

    constructor(
        private api: BookingApiService,
        private dialogRef: MatDialogRef<BookingActivitiesPlanningActivityMultiAssignDialogComponent>,
        @Inject(MAT_DIALOG_DATA) public data: BookingActivitiesPlanningActivityMultiAssignDialogData
    ) {
        this.center_id = data.center_id;
        this.rate_class_id = data.rate_class_id;
        this.booking = data.booking;
        this.groups = data.groups;
        this.planning = data.planning;
        this.selectedItemsMap = data.selectedItemsMap;
        this.mapTimeSlotIdCode = data.mapTimeSlotIdCode;

        this.vm = {
            product: {
                name: '',
                formControl: new FormControl(''),
                inputClue: new ReplaySubject(1),
                filteredList: new Observable(),
                inputChange: (event:any) => this.productInputChange(event),
                focus: () => this.productFocus(),
                restore: () => this.productRestore(),
                display: (type:any) => this.productDisplay(type)
            }
        };
    }

    public ngOnInit() {
        console.log('init BookingActivitiesPlanningActivityMultiAssignDialogComponent');

        this.vm.product.filteredList = this.vm.product.inputClue.pipe(
            debounceTime(300),
            map((value:any) => (typeof value === 'string' ? value : ((value == null) ? '' : value.name))),
            mergeMap(async (name:string) => this.filterProducts(name))
        );
    }

    private async filterProducts(name: string): Promise<any> {
        let filtered: any[] = [];
        try {
            const productCollectParams: any = {
                center_id: this.center_id,
                rate_class_id: this.rate_class_id
            };
            if(name && name.length) {
                productCollectParams.name = name;
            }

            filtered = await this.api.fetch('?get=sale_catalog_product_activity-collect', productCollectParams);
        }
        catch(response) {
            console.log(response);
        }
        return filtered;
    }

    private productInputChange(event:any) {
        this.vm.product.inputClue.next(event.target.value);
    }

    private productFocus() {
        this.vm.product.inputClue.next('');
    }

    private productRestore() {
        this.vm.product.formControl.setErrors(null);
        this.vm.product.name = this.product ? this.product.name : '';
    }

    private productDisplay(product: any): string {
        return (product && product.hasOwnProperty('name')) ? product.name : '';
    }

    public async onchangeProduct(event: any) {
        console.log('BookingActivitiesPlanningActivityDetailsComponent::productChange');

        // from mat-autocomplete
        if(event && event.option && event.option.value) {
            this.vm.product.name = event.option.value.name;
            this.product = event.option.value;
        }
    }

    public clear() {
        this.product = null;
    }

    public close() {
        this.dialogRef.close();
    }

    public async closeAndSave() {
        for(let dayIndex in this.selectedItemsMap) {
            for(let timeSlotCode in this.selectedItemsMap[dayIndex]) {
                for(let groupNum in this.selectedItemsMap[dayIndex][timeSlotCode]) {
                    if(this.selectedItemsMap[dayIndex][timeSlotCode][groupNum]) {
                        let timeSlotId: number = null;
                        for(let [id, code] of Object.entries(this.mapTimeSlotIdCode)) {
                            if(timeSlotCode === code) {
                                timeSlotId = +id;
                            }
                        }

                        let group = this.groups.find(g => g.activity_group_num === +groupNum);

                        try {
                            let tsc = timeSlotCode as 'AM'|'PM';
                            if(this.planning?.[dayIndex]?.[tsc]?.[groupNum]) {
                                const activity = this.planning[dayIndex][tsc][groupNum];

                                if(activity.activity_booking_line_id) {
                                    await this.api.update('sale\\booking\\BookingLineGroup', [group.id], {booking_lines_ids: [-activity.activity_booking_line_id.id]});
                                }
                                else {
                                    await this.api.remove('sale\\booking\\BookingActivity', [activity.id], true);
                                }
                            }

                            if(this.product) {
                                await this.api.create('sale\\booking\\BookingActivity', {
                                    booking_id: this.booking.id,
                                    booking_line_group_id: group.id,
                                    product_id: this.product.id,
                                    activity_date: (new Date(dayIndex)).getTime() / 1000,
                                    time_slot_id: timeSlotId
                                });
                            }
                        }
                        catch(response: any) {
                            this.api.errorFeedback(response);
                        }
                    }
                }
            }
        }

        this.dialogRef.close({ reload: true });
    }
}
