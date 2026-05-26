import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { PlanningEmployeesComponent } from './employees/employees.component';


const routes: Routes = [
    {
        path: 'employees',
        component: PlanningEmployeesComponent
    }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class PlanningRoutingModule {}
