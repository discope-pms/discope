import { NgModule } from '@angular/core';
import { DateAdapter, MAT_DATE_LOCALE } from '@angular/material/core';
import { Platform } from '@angular/cdk/platform';

import { SharedLibModule, CustomDateAdapter } from 'sb-shared-lib';
import { PlanningModule } from './planning-employees-routing.module';

import { PlanningEmployeesComponent } from './planning-employees.component';
import { PlanningEmployeesFiltersComponent } from './_components/planning-employees-filters/planning-employees-filters.component';
import {PlanningEmployeesCalendarDayComponent} from "./_components/planning-employees-calendar-day/planning-employees-calendar-day.component";
import { AppSharedPipesModule } from '../../_pipes/shared-pipes.module';
import {AppSharedComponentsModule} from "../_components/shared-components.module";

@NgModule({
    imports: [
        SharedLibModule,
        PlanningModule,
        AppSharedPipesModule,
        AppSharedComponentsModule
    ],
    declarations: [
        PlanningEmployeesComponent,
        PlanningEmployeesFiltersComponent,
        PlanningEmployeesCalendarDayComponent
    ],
    providers: [
        { provide: DateAdapter, useClass: CustomDateAdapter, deps: [MAT_DATE_LOCALE, Platform] }
    ]
})
export class AppPlanningEmployeesModule { }
