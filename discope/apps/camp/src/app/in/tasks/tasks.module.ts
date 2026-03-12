import { NgModule } from '@angular/core';
import { DateAdapter, MAT_DATE_LOCALE } from '@angular/material/core';
import { Platform } from '@angular/cdk/platform';

import { SharedLibModule, CustomDateAdapter } from 'sb-shared-lib';

import { TaskRoutingModule } from './tasks-routing.module';

import { TasksComponent } from './tasks.component';
import { TasksWaitingComponent } from './waiting/waiting.component';

@NgModule({
    imports: [
        SharedLibModule,
        TaskRoutingModule
    ],
    declarations: [
        TasksComponent,
        TasksWaitingComponent
    ],
    providers: [
        { provide: DateAdapter, useClass: CustomDateAdapter, deps: [MAT_DATE_LOCALE, Platform] }
    ]
})
export class AppInTasksModule {}
