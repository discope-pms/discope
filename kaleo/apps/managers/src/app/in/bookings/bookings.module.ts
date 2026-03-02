import { NgModule } from '@angular/core';
import { DateAdapter, MAT_DATE_LOCALE } from '@angular/material/core';
import { Platform } from '@angular/cdk/platform';

import { SharedLibModule, CustomDateAdapter } from 'sb-shared-lib';
import { BookingsRoutingModule } from './bookings-routing.module';

import { BookingItemComponent } from './_components/booking-list/_components/booking-item.component';
import { BookingListComponent } from './_components/booking-list/booking-list.component';

import { BookingsPendingComponent } from './pending/bookings-pending.component';
import { BookingsUpcomingComponent } from './upcoming/bookings-upcoming.component';
import { AppSharedComponentsModule } from '../_components/shared-components.module';
import { AppSharedPipesModule } from '../../_pipes/shared-pipes.module';

@NgModule({
    imports: [
        SharedLibModule,
        BookingsRoutingModule,
        AppSharedComponentsModule,
        AppSharedPipesModule
    ],
    declarations: [
        BookingItemComponent,
        BookingListComponent,
        BookingsPendingComponent,
        BookingsUpcomingComponent
    ],
    providers: [
        { provide: DateAdapter, useClass: CustomDateAdapter, deps: [MAT_DATE_LOCALE, Platform] }
    ]
})
export class AppBookingsModule { }
