import { NgModule } from '@angular/core';
import { DateAdapter, MAT_DATE_LOCALE } from '@angular/material/core';
import { Platform } from '@angular/cdk/platform';

import { SharedLibModule, CustomDateAdapter } from 'sb-shared-lib';
import { PlanningModule } from './planning-routing.module';

import { PlanningEmployeesComponent } from './employees/planning-employees.component';
import { AppSharedPipesModule } from '../../_pipes/shared-pipes.module';

@NgModule({
    imports: [
        SharedLibModule,
        PlanningModule,
        AppSharedPipesModule
    ],
    declarations: [
        PlanningEmployeesComponent
    ],
    providers: [
        { provide: DateAdapter, useClass: CustomDateAdapter, deps: [MAT_DATE_LOCALE, Platform] }
    ]
})
export class AppPlanningModule { }
