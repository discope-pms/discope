import { NgModule } from '@angular/core';
import { DateAdapter, MAT_DATE_LOCALE } from '@angular/material/core';
import { Platform } from '@angular/cdk/platform';

import { SharedLibModule, CustomDateAdapter } from 'sb-shared-lib';
import { BookingInspectionRoutingModule } from './booking-inspection-routing.module';

import { BookingInspectionComponent } from './booking-inspection.component';
import { BookingInspectionConfirmComponent } from './confirm/booking-inspection-confirm.component';
import { ConsumptionMeterReadingNewComponent } from './consumption-meter-reading/consumption-meter-reading-new.component';
import { AppSharedComponentsModule } from '../_components/shared-components.module';
import { BookingInfoComponent } from './_components/booking-info/booking-info.component';
import { BookingInfoConfirmComponent } from './confirm/_components/booking-info-confirm/booking-info-confirm.component';
import { MeterReadingComponent } from './_components/meter-reading/meter-reading.component';
import { MeterReadingConfirmComponent } from './confirm/_components/meter-reading-confirm/meter-reading-confirm.component';
import { ContactEmailSelectComponent } from './confirm/_components/contact-email-select/contact-email-select.component';
import { RecipientListFormComponent } from './confirm/_components/recipient-list-form/recipient-list-form.component';
import { AppSharedPipesModule } from '../../_pipes/shared-pipes.module';

@NgModule({
    imports: [
        SharedLibModule,
        BookingInspectionRoutingModule,
        AppSharedComponentsModule,
        AppSharedPipesModule
    ],
    declarations: [
        BookingInspectionComponent,
        BookingInspectionConfirmComponent,
        ConsumptionMeterReadingNewComponent,
        BookingInfoComponent,
        BookingInfoConfirmComponent,
        MeterReadingComponent,
        MeterReadingConfirmComponent,
        ContactEmailSelectComponent,
        RecipientListFormComponent
    ],
    providers: [
        { provide: DateAdapter, useClass: CustomDateAdapter, deps: [MAT_DATE_LOCALE, Platform] }
    ]
})
export class AppBookingInspectionModule { }
