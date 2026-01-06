import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { PlanningEmployeesComponent } from './employees/planning-employees.component';

const routes: Routes = [
    {
        path: 'employees',
        component: PlanningEmployeesComponent,
        data: { showCenterSettingsBtn: true }
    }
];

@NgModule({
    imports: [RouterModule.forChild(routes)],
    exports: [RouterModule]
})
export class PlanningModule {}
