import { Component, OnInit, AfterViewInit, Input, Output, EventEmitter, ChangeDetectorRef, ViewChildren, QueryList, OnChanges, SimpleChanges } from '@angular/core';
import { FormControl, Validators } from '@angular/forms';

import { ApiService, ContextService, TreeComponent } from 'sb-shared-lib';
import { BookingLineGroup } from '../../../../_models/booking_line_group.model';
import { BookingLine } from '../../../../_models/booking_line.model';
import { Booking } from '../../../../_models/booking.model';
import { Observable, ReplaySubject } from 'rxjs';
import { debounceTime, map, mergeMap } from 'rxjs/operators';

import { BookingServicesBookingGroupLineDiscountComponent } from './_components/discount/discount.component';
import { BookingServicesBookingGroupLinePriceadapterComponent } from './_components/priceadapter/priceadapter.component';
import { BookingServicesBookingGroupLinePriceDialogComponent } from './_components/price.dialog/price.component';
import { MatDialog } from '@angular/material/dialog';

// declaration of the interface for the map associating relational Model fields with their components
interface BookingLineComponentsMap {
    manual_discounts_ids: QueryList<BookingServicesBookingGroupLineDiscountComponent>,
    auto_discounts_ids: QueryList<BookingServicesBookingGroupLinePriceadapterComponent>
};

interface vmModel {
    product: {
        name: string,
        formControl: FormControl,
        inputClue: ReplaySubject < any > ,
        filteredList: Observable < any > ,
        inputChange: (event: any) => void,
        focus: () => void,
        restore: () => void,
        reset: () => void,
        display: (type: any) => string
    },
    qty: {
        formControl: FormControl,
        change: () => void
    },
    description: {
        formControl: FormControl,
        change: () => void
    },
    service_date: {
        formControl: FormControl,
        change: () => void
    },
    time_slot_id: {
        formControl: FormControl,
        change: () => void
    },
    meal_location: {
        formControl: FormControl,
        change: () => void
    },
    qty_vars: {
        values: any,
        change: (index: number, event: any) => void,
        reset: () => void
    },
    total_price: {
        value: number
    }
}

@Component({
    selector: 'booking-services-booking-group-line',
    templateUrl: 'line.component.html',
    styleUrls: ['line.component.scss']
})
export class BookingServicesBookingGroupLineComponent extends TreeComponent<BookingLine, BookingLineComponentsMap> implements OnInit, OnChanges, AfterViewInit  {
    // server-model relayed by parent
    @Input() set model(values: any) { this.update(values) }
    @Input() group: BookingLineGroup;
    @Input() booking: Booking;
    @Input() time_slots: { id: number, name: string, code: 'B'|'AM'|'L'|'PM'|'D'|'EV' }[];
    @Output() updated = new EventEmitter();
    @Output() deleted = new EventEmitter();

    @ViewChildren(BookingServicesBookingGroupLineDiscountComponent) bookingServicesBookingGroupLineDiscountComponents: QueryList<BookingServicesBookingGroupLineDiscountComponent>;
    @ViewChildren(BookingServicesBookingGroupLinePriceadapterComponent) bookingServicesBookingGroupLinePriceadapterComponents: QueryList<BookingServicesBookingGroupLinePriceadapterComponent>;

    public ready: boolean = false;

    public vm: vmModel;

    constructor(
        private cd: ChangeDetectorRef,
        private api: ApiService,
        private context: ContextService,
        public dialog: MatDialog
    ) {
        super( new BookingLine() );

        this.vm = {
            product: {
                name:           '',
                formControl:    new FormControl(''),
                inputClue:      new ReplaySubject(1),
                filteredList:   new Observable(),
                inputChange:    (event:any) => this.productInputChange(event),
                focus:          () => this.productFocus(),
                restore:        () => this.productRestore(),
                reset:          () => this.productReset(),
                display:        (type:any) => this.productDisplay(type)
            },
            qty: {
                formControl:    new FormControl('', Validators.required),
                change:         () => this.qtyChange()
            },
            description: {
                formControl:    new FormControl(),
                change:         () => this.descriptionChange()
            },
            service_date: {
                formControl:    new FormControl(),
                change:         () => this.serviceDateChange()
            },
            time_slot_id: {
                formControl:    new FormControl(),
                change:         () => this.timeSlotIdChange()
            },
            meal_location: {
                formControl:    new FormControl(),
                change:         () => this.mealLocationIdChange()
            },
            qty_vars: {
                values:         {},
                change:         (index:number, event:any) => this.qtyVarsChange(index, event),
                reset:          () => this.qtyVarsReset()
            },
            total_price: {
                value:          0.0
            }
        };
    }

    /**
     *
     * We need to perform some processing here, in addition with update(), because some inputs are only available here?
     * @param changes
     */
    public ngOnChanges(changes: SimpleChanges) {
        if(changes.model) {
            if(!this.instance.qty_vars || !this.instance.qty_vars.length) {
                let factor:number = 1;
                if(this.instance.product_id?.product_model_id?.has_duration) {
                    factor = this.instance.product_id.product_model_id.duration;
                }
                else if(this.instance.is_rental_unit || this.instance.is_meal ) {
                    factor = Math.max(1, this.group.nb_nights);
                }
                else if(this.group.is_event) {
                    // regular products are repeated in case the group is an 'event'
                    factor = this.group.nb_nights + 1;
                }
                let values = new Array(factor);
                values.fill(0);
                this.vm.qty_vars.values = [];
                for(let val of values) {
                    this.vm.qty_vars.values.push(val);
                }
            }

            if(!this.instance.is_activity && !this.instance.is_meal) {
                this.vm.service_date.formControl.disable();
                this.vm.time_slot_id.formControl.disable();
            }
            else {
                this.vm.service_date.formControl.enable();
                this.vm.time_slot_id.formControl.enable();
            }
        }
    }

    public ngAfterViewInit() {
        // init local componentsMap
        let map:BookingLineComponentsMap = {
            manual_discounts_ids: this.bookingServicesBookingGroupLineDiscountComponents,
            auto_discounts_ids: this.bookingServicesBookingGroupLinePriceadapterComponents,
        };
        this.componentsMap = map;
    }


    public ngOnInit() {
        this.ready = true;

        // listen to the changes on FormControl objects
        this.vm.product.filteredList = this.vm.product.inputClue.pipe(
            debounceTime(300),
            map( (value:any) => (typeof value === 'string' ? value : (value == null)?'':value.name) ),
            mergeMap( async (name:string) => this.filterProducts(name) )
        );
    }

    public async update(values:any) {
        super.update(values);
        // assign VM values
        this.vm.product.name = (this.instance.name && typeof this.instance.name != 'object' && this.instance.name !== '[object Object]') ? this.instance.name : '';
        this.vm.total_price.value = this.instance.price;
        // qty
        this.vm.qty.formControl.setValue(this.instance.qty);
        // description
        this.vm.description.formControl.setValue(this.instance.description);
        // service_date
        this.vm.service_date.formControl.setValue(this.instance.service_date.toISOString().split('T')[0]);
        // time_slot_id
        this.vm.time_slot_id.formControl.setValue(this.instance.time_slot_id);
        // meal_location
        this.vm.meal_location.formControl.setValue(this.instance.meal_location);
        if(!this.instance.is_activity && !this.instance.is_meal) {
            this.vm.service_date.formControl.disable();
            this.vm.time_slot_id.formControl.disable();
        }
        else {
            this.vm.service_date.formControl.enable();
            this.vm.time_slot_id.formControl.enable();
        }
        // qty_vars
        if(this.instance.qty_vars && this.instance.qty_vars.length) {
            this.vm.qty_vars.values = JSON.parse(this.instance.qty_vars);
        }
        if(!this.instance.price_id) {
            this.vm.product.formControl.setErrors({'missing_price': 'Pas de liste de prix pour ce produit.'});
        }
        else {
            this.vm.product.formControl.setErrors(null);
        }
    }

    /**
     * Retrieves the number of persons to whom the service of the line will be delivered.
     * If the parent group has a matching age_range, the related qty is given. Otherwise, the method returns nb_pers from the group.
     */
    private getNbPers(): number {
        let nb_pers = this.group.nb_pers;
        if(this.instance.product_id.has_age_range) {
            for(let assignment of this.group.age_range_assignments_ids) {
                if(assignment.age_range_id == this.instance.product_id.age_range_id) {
                    nb_pers = assignment.qty;
                }
            }
        }
        return nb_pers;
    }

    /**
     * Computes the default value for a qty_var that hasn't been set by user.
     */
    public calcQtyVar(index: number) {
        return this.getNbPers() + this.vm.qty_vars.values[index];
    }

    /*
    #deprecated - unused
    public async ondeleteLine(line_id:number) {
        await this.api.update(this.instance.entity, [this.instance.id], {order_lines_ids: [-line_id]});
        this.instance.order_lines_ids.splice(this.instance.order_lines_ids.findIndex((e:any)=>e.id == line_id),1);
        // do not relay to parent
    }
    */

    private productInputChange(event:any) {
        this.vm.product.inputClue.next(event.target.value);
    }

    private productFocus() {
        this.vm.product.inputClue.next("");
    }

    private productDisplay(product:any): string {
        return (product && product.hasOwnProperty('name'))? product.name: '';
    }

    private productReset() {
        setTimeout( () => {
            this.vm.product.name = '';
        }, 100);
    }

    private productRestore() {
        this.vm.product.formControl.setErrors(null);
        if(this.instance.product_id && this.instance.product_id.hasOwnProperty('name') && this.instance.product_id.name !== null) {
            this.vm.product.name = this.instance.product_id.name;
        }
        else {
            this.vm.product.name = '';
        }
    }

    public async onchangeProduct(event:any) {
        console.log('BookingEditCustomerComponent::productChange', event)

        // from mat-autocomplete
        if(event && event.option && event.option.value) {
            let product = event.option.value;
            if(product.hasOwnProperty('name') && (typeof product.name === 'string' || product.name instanceof String) && product.name !== '[object Object]') {
                this.vm.product.name = product.name;
            }
            // notify back-end about the change
            try {
                await this.api.call('?do=sale_booking_update-bookingline-product', {
                        id: this.instance.id,
                        product_id: product.id
                    });
                this.vm.product.formControl.setErrors(null);
                // relay change to parent component
                this.updated.emit();
            }
            catch(response) {
                this.vm.product.formControl.setErrors({'missing_price': 'Pas de liste de prix pour ce produit.'});
                this.api.errorFeedback(response);
            }
        }
    }


    private async descriptionChange() {
        if(this.instance.description != this.vm.description.formControl.value) {
            // notify back-end about the change
            try {
                await this.api.update(this.instance.entity, [this.instance.id], {description: this.vm.description.formControl.value});
                // do not relay change to parent component
                this.instance.description = this.vm.description.formControl.value;
            }
            catch(response) {
                this.api.errorFeedback(response);
            }
        }
    }

    private async serviceDateChange() {
        if(this.instance.service_date == this.vm.service_date.formControl.value) {
            return;
        }

        // notify back-end about the change
        try {
            const service_date = new Date(this.vm.service_date.formControl.value);
            await this.api.update(this.instance.entity, [this.instance.id], {service_date: service_date.getTime() / 1000});
            // relay change to parent component
            this.updated.emit();
        }
        catch(response) {
            this.vm.service_date.formControl.setValue(this.instance.service_date.toISOString().split('T')[0]);
            this.api.errorFeedback(response);
        }
    }

    private async timeSlotIdChange() {
        if(this.instance.time_slot_id == this.vm.time_slot_id.formControl.value) {
            return;
        }

        // notify back-end about the change
        try {
            await this.api.update(this.instance.entity, [this.instance.id], {time_slot_id: this.vm.time_slot_id.formControl.value});
            // relay change to parent component
            this.updated.emit();
        }
        catch(response) {
            this.vm.time_slot_id.formControl.setValue(this.instance.time_slot_id);
            this.api.errorFeedback(response);
        }
    }

    public async mealLocationIdChange() {
        if(this.instance.meal_location == this.vm.meal_location.formControl.value) {
            return;
        }

        // notify back-end about the change
        try {
            await this.api.update(this.instance.entity, [this.instance.id], {meal_location: this.vm.meal_location.formControl.value});
            // relay change to parent component
            this.updated.emit();
        }
        catch(response) {
            this.vm.meal_location.formControl.setValue(this.instance.meal_location);
            this.api.errorFeedback(response);
        }
    }

    private async qtyChange() {
        if(this.instance.qty != this.vm.qty.formControl.value) {
            // notify back-end about the change
            try {
                await this.api.update(this.instance.entity, [this.instance.id], {qty: this.vm.qty.formControl.value});
                // relay change to parent component
                this.updated.emit();
            }
            catch(response) {
                this.api.errorFeedback(response);
            }
        }
    }

    private async qtyVarsChange(index:number, $event:any) {
        let value:number = parseInt($event.srcElement.value, 10);

        this.vm.qty_vars.values[index] = (value-this.getNbPers());

        // update line
        let qty_vars = JSON.stringify(Object.values(this.vm.qty_vars.values));
        // notify back-end about the change
        try {
            await this.api.update(this.instance.entity, [this.instance.id], {qty_vars: qty_vars});
            // relay change to parent component
            this.updated.emit();
        }
        catch(response) {
            this.api.errorFeedback(response);
        }
    }

    private async qtyVarsReset() {
        let qty_vars_qty = this.group.nb_nights;
        const product_model = this.instance.product_id.product_model_id;
        if(product_model.type === 'service' && product_model.service_type === 'schedulable' && !product_model.is_repeatable) {
            qty_vars_qty = product_model.has_duration ? product_model.duration : 1;
        }

        this.vm.qty_vars.values = new Array(qty_vars_qty);
        this.vm.qty_vars.values.fill(0);

        let qty_vars = JSON.stringify(Object.values(this.vm.qty_vars.values));
        // notify back-end about the change
        try {
            await this.api.update(this.instance.entity, [this.instance.id], {qty_vars: qty_vars});
            // relay change to parent component
            this.updated.emit();
        }
        catch(response) {
            this.api.errorFeedback(response);
        }
    }

    /**
     * Limit products to the ones available for currently selected center (groups of the product matches the product groups of the center)
     */
    private async filterProducts(name: string) {
        let filtered:any[] = [];
        try {
            let domain = [
                    ['is_pack', '=', false],
                    ['is_activity', '=', false]
                ];

            if(name && name.length) {
                domain.push(['name', 'ilike', '%'+name+'%']);
            }

            const data:any[] = await this.api.fetch('?get=sale_catalog_product_collect', {
                    center_id: this.booking.center_id.id,
                    domain: JSON.stringify(domain),
                    date_from: this.booking.date_from.toISOString(),
                    date_to: this.booking.date_to.toISOString()
                });
            filtered = data;
        }
        catch(response) {
            console.log(response);
        }
        return filtered;
    }


    /**
     * Add a manual discount
     */
    public async oncreateDiscount() {
        try {
            const adapter = await this.api.create("sale\\booking\\BookingPriceAdapter", {
                booking_id: this.booking.id,
                booking_line_group_id: this.group.id,
                booking_line_id: this.instance.id
            });

            let discount = {id: adapter.id, type: 'percent', value: 0};
            this.instance.manual_discounts_ids.push(discount);
        }
        catch(response) {
            this.api.errorFeedback(response);
        }
    }

    public async ondeleteDiscount(discount_id: any) {
        try {
            this.instance.manual_discounts_ids.splice(this.instance.manual_discounts_ids.findIndex((e:any)=>e.id == discount_id),1);
            await this.api.update(this.instance.entity, [this.instance.id], {manual_discounts_ids: [-discount_id]});
            this.updated.emit();
        }
        catch(response) {
            this.api.errorFeedback(response);
        }
    }

    public async onupdateDiscount(discount_id:any) {
        this.updated.emit();
    }

    public getOffsetDate(offset:number) {
        let date = new Date(this.group.date_from.getTime());
        date.setDate(date.getDate() + offset);
        return date;
    }

    public getPossibleServiceDates(): string[] {
        const nights_indexes = Array.from(Array(this.group.nb_nights + 1).keys());
        if(this.instance.product_id.product_model_id.has_duration && this.instance.product_id.product_model_id.duration > 1) {
            nights_indexes.splice(-1 * this.instance.product_id.product_model_id.duration + 1);
        }

        const dates: string[] = [];
        nights_indexes.forEach((night_index) => {
            const date = new Date(this.group.date_from.getTime() + night_index * 86400 * 1000);
            dates.push(date.toISOString().split('T')[0]);
        });

        return dates;
    }

    public getDateAfterServiceDate(index: number): string {
        const date = new Date(this.instance.service_date.getTime() + index * 86400 * 1000);
        return date.toISOString().split('T')[0];
    }

    public getTimeSlotName(): string {
        let time_slot_name = '';
        for(let time_slot of this.getPossibleTimeSlots()) {
            if(this.instance.time_slot_id === time_slot.id) {
                time_slot_name = time_slot.name;
                break;
            }
        }

        return time_slot_name;
    }

    public getPossibleTimeSlots(): { id: number, name: string }[] {
        if(this.instance.product_id.product_model_id.time_slots_ids && this.instance.product_id.product_model_id.time_slots_ids.length > 0) {
            return this.instance.product_id.product_model_id.time_slots_ids;
        }

        let time_slot_codes: ('B'|'AM'|'L'|'PM'|'D'|'EV')[] = [];
        if(this.instance.product_id.product_model_id.is_meal) {
            // If is meal allow Breakfast, Lunch and Diner
            time_slot_codes = ['B', 'L', 'D'];
        }
        else if(this.instance.product_id.product_model_id.is_snack) {
            // If is snack allow Morning and Afternoon
            time_slot_codes = ['AM', 'PM'];
        }
        else {
            // Else allow Morning, Afternoon and Evening
            time_slot_codes = ['AM', 'PM', 'EV'];
        }

        return this.time_slots.filter((time_slot) => time_slot_codes.includes(time_slot.code));
    }

    public openPriceEdition() {
        if(this.group.is_locked) {
            return;
        }
        const dialogRef = this.dialog.open(BookingServicesBookingGroupLinePriceDialogComponent, {
                width: '500px',
                height: '500px',
                data: {
                    line: this.instance
                }
            });

        dialogRef.afterClosed().subscribe(async (result) => {
            if(result) {
                if(this.instance.unit_price != result.unit_price || this.instance.vat != result.vat_rate) {
                    try {
                        await this.api.update(this.instance.entity, [this.instance.id], {unit_price: result.unit_price, vat_rate: result.vat_rate});
                        // relay change to parent component
                        this.updated.emit();
                    }
                    catch(response) {
                        this.api.errorFeedback(response);
                    }
                }
            }
        });
    }
}
