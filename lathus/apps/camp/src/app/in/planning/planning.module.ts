import { NgModule } from '@angular/core';
import { DateAdapter, MAT_DATE_LOCALE } from '@angular/material/core';
import { Platform } from '@angular/cdk/platform';


import { SharedLibModule, CustomDateAdapter } from 'sb-shared-lib';

import { PlanningRoutingModule } from './planning-routing.module';


import { PlanningEmployeesComponent } from './employees/employees.component';

import { LayoutModule } from '@angular/cdk/layout';
import { OverlayModule } from '@angular/cdk/overlay';

@NgModule({
  imports: [
    SharedLibModule,
    PlanningRoutingModule,
    LayoutModule,
    OverlayModule
  ],
  declarations: [
    PlanningEmployeesComponent
  ],
  providers: [
    { provide: DateAdapter, useClass: CustomDateAdapter, deps: [MAT_DATE_LOCALE, Platform] }
  ]
})
export class AppInPlanningModule { }
