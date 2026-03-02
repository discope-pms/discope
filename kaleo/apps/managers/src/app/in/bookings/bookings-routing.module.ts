import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { BookingsPendingComponent } from './pending/bookings-pending.component';
import { BookingsUpcomingComponent } from './upcoming/bookings-upcoming.component';

const routes: Routes = [
    {
        path: 'pending',
        component: BookingsPendingComponent,
        data: { showCenterSettingsBtn: true }
    },
    {
        path: 'upcoming',
        component: BookingsUpcomingComponent,
        data: { showCenterSettingsBtn: true }
    }
];

@NgModule({
    imports: [RouterModule.forChild(routes)],
    exports: [RouterModule]
})
export class BookingsRoutingModule {}
