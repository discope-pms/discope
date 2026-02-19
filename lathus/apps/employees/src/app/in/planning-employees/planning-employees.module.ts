import { NgModule } from '@angular/core';
import { DateAdapter, MAT_DATE_LOCALE } from '@angular/material/core';
import { Platform } from '@angular/cdk/platform';

import { SharedLibModule, CustomDateAdapter } from 'sb-shared-lib';
import { PlanningEmployeesRoutingModule } from './planning-employees-routing.module';

import { AppSharedPipesModule } from '../../_pipes/shared-pipes.module';
import { AppSharedComponentsModule } from '../_components/shared-components.module';

import { PlanningEmployeesComponent } from './planning-employees.component';
import { PlanningEmployeesFiltersComponent } from './_components/planning-employees-filters/planning-employees-filters.component';
import { PlanningEmployeesCalendarComponent } from './_components/planning-employees-calendar/planning-employees-calendar.component';
import { PlanningEmployeesUnassignedComponent } from './_components/planning-employees-unassigned/planning-employees-unassigned.component';
import { PlanningEmployeesActivityDialogComponent } from './_components/planning-employees-activity-dialog/planning-employees-activity-dialog.component';
import { PlanningEmployeesCalendarActivityCardComponent } from './_components/planning-employees-calendar/_components/planning-employees-calendar-activity-card/planning-employees-calendar-activity-card.component';
import { PlanningEmployeesFiltersDialogComponent } from './_components/planning-employees-filters/_components/planning-employees-filters-dialog/planning-employees-filters-dialog.component';
import { PlanningEmployeesCalendarMomentDialogComponent } from './_components/planning-employees-calendar/_components/planning-employees-calendar-moment-dialog/planning-employees-calendar-moment-dialog.component';

@NgModule({
    imports: [
        SharedLibModule,
        PlanningEmployeesRoutingModule,
        AppSharedPipesModule,
        AppSharedComponentsModule
    ],
    declarations: [
        PlanningEmployeesComponent,
        PlanningEmployeesFiltersComponent,
        PlanningEmployeesCalendarComponent,
        PlanningEmployeesUnassignedComponent,
        PlanningEmployeesActivityDialogComponent,
        PlanningEmployeesCalendarActivityCardComponent,
        PlanningEmployeesFiltersDialogComponent,
        PlanningEmployeesCalendarMomentDialogComponent
    ],
    providers: [
        { provide: DateAdapter, useClass: CustomDateAdapter, deps: [MAT_DATE_LOCALE, Platform] }
    ]
})
export class AppPlanningEmployeesModule { }
