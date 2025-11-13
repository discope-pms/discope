import { NgModule } from '@angular/core';
import { DateAdapter, MAT_DATE_LOCALE } from '@angular/material/core';
import { Platform } from '@angular/cdk/platform';

import { SharedLibModule, CustomDateAdapter } from 'sb-shared-lib';

import { EnrollmentRoutingModule } from './enrollment-routing.module';

import { EnrollmentConfirmationComponent } from './confirmation/confirmation.component';
import { EnrollmentComponent } from './enrollment.component';
import { EnrollmentPreRegistrationComponent } from './pre-registration/pre-registration.component';
import { EnrollmentPreRegistrationReminderComponent } from './pre-registration-reminder/pre-registration-reminder.component';

@NgModule({
    imports: [
        SharedLibModule,
        EnrollmentRoutingModule
    ],
    declarations: [
        EnrollmentComponent,
        EnrollmentPreRegistrationComponent,
        EnrollmentPreRegistrationReminderComponent,
        EnrollmentConfirmationComponent
    ],
    providers: [
        { provide: DateAdapter, useClass: CustomDateAdapter, deps: [MAT_DATE_LOCALE, Platform] }
    ]
})
export class AppInEnrollmentModule {}
