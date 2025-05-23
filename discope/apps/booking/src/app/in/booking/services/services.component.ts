import { Component, OnInit, AfterViewInit, ElementRef, ViewChild, Renderer2  } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { BookingApiService } from 'src/app/in/booking/_services/booking.api.service';
import { ContextService, EqualUIService } from 'sb-shared-lib';

class Booking {
    constructor(
        public id: number = 0,
        public name: string = '',
        public display_name: string = '',
        public created: Date = new Date(),
        public status: string = ''
    ) {}
}

export interface BookedServicesDisplaySettings {
    store_folded_settings: boolean;
    identification_folded: boolean;
    products_folded: boolean;
    activities_folded: boolean;
    accommodations_folded: boolean;
    meals_folded: boolean;
    activities_show: boolean;
    meals_show: boolean;
}

@Component({
    selector: 'booking-services',
    templateUrl: 'services.component.html',
    styleUrls: ['services.component.scss']
})
export class BookingServicesComponent implements OnInit, AfterViewInit  {

    public booking: any = new Booking();
    public booking_id: number = 0;

    public display_settings: BookedServicesDisplaySettings = {
        store_folded_settings: false,
        identification_folded: true,
        products_folded: true,
        activities_folded: true,
        accommodations_folded: true,
        meals_folded: true,
        meals_show: true,
        activities_show: true
    };

    public ready: boolean = false;

    @ViewChild('actionButtonContainer') actionButtonContainer: ElementRef;

    public status:any = {
        'quote': 'Devis',
        'option': 'Option',
        'confirmed': 'Confirmée',
        'validated': 'Validée',
        'checkedin': 'En cours',
        'checkedout': 'Terminée',
        'invoiced': 'Facturée',
        'debit_balance': 'Solde débiteur',
        'credit_balance': 'Solde créditeur',
        'balanced': 'Soldée'
    }

    constructor(
        private api: BookingApiService,
        private route: ActivatedRoute,
        private context:ContextService,
        private eq:EqualUIService,
        private renderer: Renderer2
    ) {}

    /**
     * Set up callbacks when component DOM is ready.
     */
    public async ngAfterViewInit() {
        await this.refreshActionButton();
        this.ready = true;
    }

    public ngOnInit() {
        console.debug('BookingEditComponent init');

        // when action is performed, we need to reload booking object
        // #memo - context change triggers sidemenu panes updates
        this.context.getObservable().subscribe( async (descriptor:any) => {
            if(this.ready) {
                // reload booking
                await this.load( Object.getOwnPropertyNames(new Booking()) );
                this.refreshActionButton();
                // force reloading child component
                let booking_id = this.booking_id;
                this.booking_id = 0;
                setTimeout( () => {
                    this.booking_id = booking_id;
                }, 250);
            }
        });

        // fetch the booking ID from the route
        this.route.params.subscribe( async (params) => {
            console.debug('BookingEditComponent : received routeParams change', params);
            if(params && params.hasOwnProperty('booking_id')) {
                this.booking_id = <number> params['booking_id'];

                try {
                    // load booking object
                    await this.load( Object.getOwnPropertyNames(new Booking()) );

                    // relay change to context (to display sidemenu panes according to current object)
                    this.context.change({
                        context_only: true,   // do not change the view
                        context: {
                            entity: 'sale\\booking\\Booking',
                            type: 'form',
                            purpose: 'view',
                            domain: ['id', '=', this.booking_id]
                        }
                    });
                }
                catch(response) {
                    console.warn(response);
                }
            }
        });

        this.loadDisplaySettings();
    }

    private async refreshActionButton() {
        let $button = await this.eq.getActionButton('sale\\booking\\Booking', 'form.default', ['id', '=', this.booking_id]);
        // remove previous button, if any
        for (let child of this.actionButtonContainer.nativeElement.children) {
            this.renderer.removeChild(this.actionButtonContainer.nativeElement, child);
        }
        if($button.length) {
            this.renderer.appendChild(this.actionButtonContainer.nativeElement, $button[0]);
        }
    }

    /**
     * Assign values based on selected booking and load sub-objects required by the view.
     *
     */
    private async load(fields:any) {
        try {
            const data:any = await this.api.read("sale\\booking\\Booking", [this.booking_id], fields);
            if(data && data.length) {
                // update local object
                for(let field of Object.keys(data[0])) {
                    this.booking[field] = data[0][field];
                }
                // assign booking to Booking API service (for conditioning calls)
                this.api.setBooking(this.booking);
            }
        }
        catch(response) {
            console.log('unexpected error');
        }
    }

    private async loadDisplaySettings() {
        try {
            // #todo - use EnvService from sb-shared-lib
            this.display_settings = await this.api.fetch('?get=sale_booking_booked-services-settings');
            if(this.display_settings.store_folded_settings) {
                this.setDisplaySettingsFromLocalStorage();
            }
        }
        catch(response) {
            this.api.errorFeedback(response);
        }
    }

    private setDisplaySettingsFromLocalStorage() {
        const stored_map_bookings_booked_services_settings: string | null = localStorage.getItem('map_bookings_booked_services_settings');
        if(stored_map_bookings_booked_services_settings === null) {
            return;
        }

        const map_bookings_booked_services_settings: {[key: number]: BookedServicesDisplaySettings} = JSON.parse(stored_map_bookings_booked_services_settings);
        if(!map_bookings_booked_services_settings[this.booking_id]) {
            return;
        }

        const booked_services_settings = map_bookings_booked_services_settings[this.booking_id];
        for(let key of Object.keys(this.display_settings)) {
            this.display_settings[key as keyof BookedServicesDisplaySettings] = booked_services_settings[key as keyof BookedServicesDisplaySettings];
        }
    }
}
