import { NgModule, LOCALE_ID } from '@angular/core';

import { BrowserModule } from '@angular/platform-browser';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';

import { DateAdapter, MatNativeDateModule, MAT_DATE_LOCALE } from '@angular/material/core';
import { Platform, PlatformModule } from '@angular/cdk/platform';

import { SharedLibModule, AuthInterceptorService, CustomDateAdapter } from 'sb-shared-lib';
import { NgxMaterialTimepickerModule } from 'ngx-material-timepicker';

import { AppRoutingModule } from './app-routing.module';
import { AppRootComponent } from './app.root.component';
import { AppComponent } from './in/app.component';
import { AuthComponent } from './in/auth.component';
import { AppRequestComponent } from './in/request/request.component';
import { AppHandsetComponent } from './in/_layout_handset/app-handset.component';
import { AppWebComponent } from './in/_layout_web/app-web.component';
import { BookingInformationComponent } from './_components/web/booking-information.component';
import { SojournsComponent } from './_components/web/sojourns.component';
import { SojournHeaderComponent } from './_components/web/sojourn/sojourn-header.component';
import { SojournGuestTableComponent } from './_components/web/sojourn/sojourn-guest-table.component';
import { GuestFormComponent } from './_components/guest/guest-form.component';
import { GuestFormDialogComponent } from './_components/web/guest/guest-form-dialog.component';
import { GuestBulkFormDialogComponent } from './_components/web/guest/guest-bulk-form-dialog.component';
import { SojournsPageComponent } from './_components/handset/sojourns-page.component';
import { SojournPageComponent } from './_components/handset/sojourn-page.component';
import { GuestFormPageComponent } from './_components/handset/guest-form-page.component';
import { BookingDetailsComponent } from './_components/handset/sojourns-page/booking-details.component';
import { BookingLineGroupDetailsComponent } from './_components/handset/sojourns-page/booking-line-group-details.component';
import { HandsetPageHeaderComponent } from './_components/handset/header/handset-page-header.component';
import { GuestListItemComponent } from './_components/handset/sojourn-page/guest-list-item.component';
import { GuestListIncompleteDialogComponent } from './_components/guestlist/guest-list-incomplete-dialog.component';
import { DialogGdprConsentComponent } from './_components/dialog-gdpr-consent/dialog-gdpr-consent.component';
import { DialogGdprNoticeComponent } from './_components/dialog-gdpr-notice/dialog-gdpr-notice.component';
import { DialogSubmitConfirmationComponent } from './_components/dialog-submit-confirmation/dialog-submit-confirmation.component';
import { DialogHelpComponent } from './_components/dialog-help/dialog-help.component';
import { DialogDownloadComponent } from './_components/dialog-download/dialog-download.component';
import { SelectOptionPopupComponent } from './_components/select-option-popup/select-option-popup.component';
import { TranslatePipe } from './_pipes/TranslatePipe';
import { TranslateWithVarPipe } from './_pipes/TranslateWithVarPipe';
import { TranslationService } from './_services/TranslationService';

/* HTTP requests interception dependencies */
import { HTTP_INTERCEPTORS } from '@angular/common/http';

import { registerLocaleData } from '@angular/common';
import localeFr from '@angular/common/locales/fr';
import { MAT_SNACK_BAR_DEFAULT_OPTIONS } from '@angular/material/snack-bar';

registerLocaleData(localeFr);

@NgModule({
    declarations: [
        AppRootComponent,
        AppComponent,
        AuthComponent,
        AppRequestComponent,
        AppHandsetComponent,
        AppWebComponent,
        TranslatePipe,
        TranslateWithVarPipe,
        BookingInformationComponent,
        SojournsComponent,
        SojournHeaderComponent,
        SojournGuestTableComponent,
        GuestFormComponent,
        SelectOptionPopupComponent,
        GuestFormDialogComponent,
        GuestBulkFormDialogComponent,
        SojournsPageComponent,
        SojournPageComponent,
        GuestFormPageComponent,
        BookingDetailsComponent,
        BookingLineGroupDetailsComponent,
        HandsetPageHeaderComponent,
        GuestListItemComponent,
        GuestListIncompleteDialogComponent,
        DialogGdprConsentComponent,
        DialogGdprNoticeComponent,
        DialogHelpComponent,
        DialogDownloadComponent,
        DialogSubmitConfirmationComponent
    ],
    imports: [
        AppRoutingModule,
        BrowserModule,
        BrowserAnimationsModule,
        SharedLibModule,
        MatNativeDateModule,
        PlatformModule,
        NgxMaterialTimepickerModule.setLocale('fr-BE')
    ],
    providers: [
        // add HTTP interceptor to inject AUTH header to any outgoing request
        { provide: HTTP_INTERCEPTORS, useClass: AuthInterceptorService, multi: true },
        { provide: MAT_SNACK_BAR_DEFAULT_OPTIONS, useValue: { duration: 4000, horizontalPosition: 'start' }},
        { provide: MAT_DATE_LOCALE, useValue: 'fr-BE' },
        { provide: LOCALE_ID, useValue: 'fr-BE' },
        { provide: DateAdapter, useClass: CustomDateAdapter, deps: [MAT_DATE_LOCALE, Platform]},
        TranslationService
    ],
    bootstrap: [AppRootComponent]
})
export class AppModule {
}
