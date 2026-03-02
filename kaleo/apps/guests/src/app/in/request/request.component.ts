import { Component, OnInit } from '@angular/core';
import { FormControl, Validators } from '@angular/forms';
import { ActivatedRoute} from '@angular/router';
import { ApiService } from 'src/app/_services/ApiService';

/**
 * This Component is associated with the route `/request/:booking_id`
 */
@Component({
    selector: 'app-request',
    templateUrl: 'request.component.html',
    styleUrls: ['request.component.scss']
})
export class AppRequestComponent implements OnInit  {

    public loading: boolean = true;
    public has_error: boolean = false;
    public is_sent: boolean = false;

    public booking_id: number = 0;
    public emailFormControl = new FormControl('', [Validators.required, Validators.email]);

    public get email_address() { return this.emailFormControl.value; }

    constructor(
        private route: ActivatedRoute,
        private api: ApiService
    ) {}

    public ngOnInit() {
        console.debug('AppRequestComponent::ngOnInit');
        // extract booking_id from URL
        this.route.params.subscribe(async params => {
                if(params.hasOwnProperty('booking_id')) {
                    this.booking_id = parseInt(params.booking_id);
                    this.loading = false;
                }
            });
    }

    public ngAfterViewInit() {
        console.debug('AppRequestComponent::ngAfterViewInit');
    }

    public async sendLink() {
        if(!this.emailFormControl.valid) {
            return;
        }
        this.loading = true;
        try {
            await this.api.requestAccess(this.booking_id, this.email_address);
            this.is_sent = true;
        }
        catch(response) {
            console.debug('error', response);
            this.has_error = true;
        }
        this.loading = false;
    }

    public async retry() {
        this.emailFormControl.setValue('');
        this.has_error = false;
    }
}