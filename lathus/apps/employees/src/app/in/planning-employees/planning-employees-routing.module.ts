import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { PlanningEmployeesComponent } from './planning-employees.component';

const routes: Routes = [
    {
        path: 'employees',
        component: PlanningEmployeesComponent,
        data: { title: 'TITLE_PLANNING_EMPLOYEES' }
    }
];

@NgModule({
    imports: [RouterModule.forChild(routes)],
    exports: [RouterModule]
})
export class PlanningModule {}
