import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { BookingInspectionComponent } from './booking-inspection.component';
import { BookingInspectionConfirmComponent } from './confirm/booking-inspection-confirm.component';
import { ConsumptionMeterReadingNewComponent } from './consumption-meter-reading/consumption-meter-reading-new.component';

const routes: Routes = [
    {
        path: '',
        component: BookingInspectionComponent
    },
    {
        path: 'confirm',
        component: BookingInspectionConfirmComponent
    },
    {
        path: 'consumption-meter-reading/new',
        component: ConsumptionMeterReadingNewComponent
    }
];

@NgModule({
    imports: [RouterModule.forChild(routes)],
    exports: [RouterModule]
})
export class BookingInspectionRoutingModule {}
