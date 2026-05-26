import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { EnrollmentConfirmationComponent } from './confirmation/confirmation.component';
import { EnrollmentComponent } from './enrollment.component';
import { EnrollmentPreRegistrationComponent } from './pre-registration/pre-registration.component';
import { EnrollmentPreRegistrationReminderComponent } from './pre-registration-reminder/pre-registration-reminder.component';


const routes: Routes = [
    {
        path: 'preregistration',
        component: EnrollmentPreRegistrationComponent
    },
    {
        path: 'preregistration-reminder',
        component: EnrollmentPreRegistrationReminderComponent
    },
    {
        path: 'confirmation',
        component: EnrollmentConfirmationComponent
    },
    // single enrollment (to be loaded only when URL points exactly to /enrollment/:enrollment_id without sub route)
    {
        path: '',
        pathMatch: 'full',
        component: EnrollmentComponent
    }
];

@NgModule({
    imports: [RouterModule.forChild(routes)],
    exports: [RouterModule]
})
export class EnrollmentRoutingModule {}
