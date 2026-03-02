import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { ConsumptionMetersComponent } from './consumption-meters/consumption-meters.component';
import { ConsumptionMeterEditComponent } from './consumption-meter/edit/consumption-meter-edit.component';
import { ConsumptionMeterNewComponent } from './consumption-meter/new/consumption-meter-new.component';

const routes: Routes = [
    {
        path: 'consumption-meters',
        component: ConsumptionMetersComponent
    },
    {
        path: 'consumption-meter/new',
        component: ConsumptionMeterNewComponent
    },
    {
        path: 'consumption-meter/:consumption_meter_id',
        component: ConsumptionMeterEditComponent
    }
];

@NgModule({
    imports: [RouterModule.forChild(routes)],
    exports: [RouterModule]
})
export class CenterRoutingModule {}
