import { BreakpointState } from '@angular/cdk/layout';
import { Component, OnInit } from '@angular/core';
import { Observable } from 'rxjs';
import { ResponsiveService } from 'src/app/_services/ResponsiveService';
import { AuthService } from 'src/app/_services/AuthService';
import { ApiService } from '../_services/ApiService';
import { Booking, GuestListItem } from '../../type';
import { MatDialog } from '@angular/material/dialog';
import { GuestListIncompleteDialogComponent } from '../_components/guestlist/guest-list-incomplete-dialog.component';
import { DialogGdprConsentComponent } from '../_components/dialog-gdpr-consent/dialog-gdpr-consent.component';
import { DialogSubmitConfirmationComponent } from '../_components/dialog-submit-confirmation/dialog-submit-confirmation.component';
import { DialogHelpComponent } from '../_components/dialog-help/dialog-help.component';


/**
 * This Component acts as a switch to redirect to the component that is adapted to the current display layout, based on defined breakpoints.
 */
@Component({
    selector: 'app',
    templateUrl: 'app.component.html',
    styleUrls: ['app.component.scss']
})
export class AppComponent implements OnInit  {
    public loading: boolean = false;
    public has_auth_error: boolean = false;
    public booking: Booking;

    public completion_percentage: number = 0;
    public is_submittable: boolean = false;


    public isHandset$: Observable<BreakpointState>;
    public isWeb$: Observable<BreakpointState>;

    public gdpr_consent: boolean = false;

    constructor(
            private api: ApiService,
            private auth: AuthService,
            private responsiveService: ResponsiveService,
            public dialog: MatDialog
        ) {}

    public async ngOnInit() {
        console.debug('AppComponent::ngOnInit');

        this.isHandset$ = this.responsiveService.isHandset();
        this.isWeb$ = this.responsiveService.isWeb();
        this.loading = true;

        try {
            const guestUser = await this.auth.authenticate();
            this.loading = false;
            await this.loadBooking(guestUser.booking_id);
        }
        catch(err) {
            // display authentication error message
            this.has_auth_error = true;
            this.loading = false;
        }

        // retrieve previously given GDPR consent, if any
        this.gdpr_consent = (localStorage.getItem('gdpr_consent') === 'true');

        if(!this.gdpr_consent) {
            const dialogRef = this.dialog.open(DialogGdprConsentComponent, {
                    width: '500px',
                    disableClose: true
                });

            dialogRef.afterClosed().subscribe(async (result) => {
                    this.gdpr_consent = true;
                    localStorage.setItem('gdpr_consent', this.gdpr_consent.toString());
                });
        }
    }

    private async loadBooking(id?: number) {
        if(!id) {
            id = this.booking.id;
        }

        this.booking = await this.api.getBooking({ id });
        this.updateFormStatus();
    }

    private updateFormStatus() {
        let nbPersTotal = 0;
        this.booking.booking_lines_groups_ids.forEach(group => {
            nbPersTotal += group.nb_pers;
        });

        let qty_completed = 0;
        let qty_coordinator = 0;
        let is_submittable = true;
        Object.values(this.booking.guest_list_id.guest_list_items_ids).forEach(guest => {
            let is_guest_complete = true;

            if(guest['date_of_birth'] === null) {
                is_guest_complete = is_guest_complete && false;
                is_submittable = false;
            }
            else {
                const age = this.computeAge(new Date(guest['date_of_birth'] * 1000));
                if(age >= 15 && guest['citizen_identification']?.length == 0) {
                    is_guest_complete = is_guest_complete && false;
                    is_submittable = false;
                }
            }
            if(guest['firstname'] === null || guest['firstname']?.length == 0) {
                is_guest_complete = is_guest_complete && false;
                is_submittable = false;
            }
            if(guest['lastname'] === null || guest['lastname']?.length == 0) {
                is_guest_complete = is_guest_complete && false;
                is_submittable = false;
            }

            if(is_guest_complete) {
                if(guest['is_coordinator']) {
                    qty_coordinator++;
                }
                qty_completed++;
            }
        });

        this.is_submittable = is_submittable && (qty_coordinator >= 1);
        this.completion_percentage = Math.round(qty_completed / nbPersTotal * 100);
    }

    private computeAge(birthDate: Date) {
        const today = new Date();
        const birthYear = birthDate.getFullYear();
        let age = today.getFullYear() - birthYear;

        // Check if the birthday has occurred this year
        const birthMonth = birthDate.getMonth();
        const birthDay = birthDate.getDate();

        if (
            today.getMonth() < birthMonth ||
            (today.getMonth() === birthMonth && today.getDate() < birthDay)
        ) {
            age--;
        }

        return age;
    }

    public showHelp() {
        const dialogRef = this.dialog.open(DialogHelpComponent, {
                width: '500px',
                autoFocus: false
            });
    }

    public async deleteGuestListItem(ids: number|number[]) {
        if(typeof ids === 'number') {
            ids = [ids];
        }

        await this.api.deleteListItems(ids);

        ids.forEach(id => {
            delete(this.booking.guest_list_id.guest_list_items_ids[id]);
        });

        await this.loadBooking();
    }

    public async addGuestListItem(bookingLineGroupId: number) {
        await this.api.createListItem(this.booking.guest_list_id.id, bookingLineGroupId);

        await this.loadBooking();
    }

    public async updateGuestListItem({ id, values }: { id: number|number[], values: Partial<GuestListItem> }) {
        if(typeof id === 'number') {
            await this.api.updateListItem(id, values);
        }
        else {
            await this.api.updateListItems(id, values);
        }

        if(Object.keys(values).length > 1 || typeof id !== 'number') {
            await this.loadBooking();
        }
        else {
            Object.entries(values).forEach(([field, value]) => {
                if(field == 'date_of_birth') {
                    if(value) {
                        const date = new Date(<string>value);
                        this.booking.guest_list_id.guest_list_items_ids[id]['date_of_birth'] = date.getTime() / 1000;
                    }
                }
                else {
                    // @ts-ignore
                    this.booking.guest_list_id.guest_list_items_ids[id][field] = value;
                }
            });

            this.updateFormStatus();
        }
    }

    public async submit() {
        let confirm_submission = () => {
            this.loading = false;
            const dialogRef = this.dialog.open(DialogSubmitConfirmationComponent, {
                    width: '500px',
                    disableClose: true
                });

        };
        // #memo - the completion percentage is an indicator for the user only, the is_submittable flag is what really matters
        if(this.completion_percentage < 100) {
            const dialogRef = this.dialog.open(GuestListIncompleteDialogComponent, {
                width: '500px'
            });

            dialogRef.afterClosed().subscribe(async (result) => {
                if(result) {
                    this.loading = true;
                    await this.api.submit(this.booking.guest_list_id.id);
                    await this.loadBooking();
                    setTimeout(confirm_submission, 300);
                }
            });
        }
        else {
            this.loading = true;
            await this.api.submit(this.booking.guest_list_id.id);
            await this.loadBooking();
            setTimeout(confirm_submission, 300);
        }
    }
}
