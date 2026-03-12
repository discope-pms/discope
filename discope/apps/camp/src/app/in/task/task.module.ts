import { NgModule } from '@angular/core';
import { DateAdapter, MAT_DATE_LOCALE } from '@angular/material/core';
import { Platform } from '@angular/cdk/platform';

import { SharedLibModule, CustomDateAdapter } from 'sb-shared-lib';

import { TaskRoutingModule } from './task-routing.module';

import { TaskComponent } from './task.component';

@NgModule({
    imports: [
        SharedLibModule,
        TaskRoutingModule
    ],
    declarations: [
        TaskComponent
    ],
    providers: [
        { provide: DateAdapter, useClass: CustomDateAdapter, deps: [MAT_DATE_LOCALE, Platform] }
    ]
})
export class AppInTaskModule {}
