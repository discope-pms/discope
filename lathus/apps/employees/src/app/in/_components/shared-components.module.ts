import { NgModule } from '@angular/core';
import { DateAdapter, MAT_DATE_LOCALE } from '@angular/material/core';
import { Platform } from '@angular/cdk/platform';

import { SharedLibModule, CustomDateAdapter } from 'sb-shared-lib';
import { IconButtonComponent } from './icon-button/icon-button.component';

const sharedComponents = [
    IconButtonComponent
];

@NgModule({
    imports: [
        SharedLibModule
    ],
    declarations: [
        ...sharedComponents
    ],
    providers: [
        { provide: DateAdapter, useClass: CustomDateAdapter, deps: [MAT_DATE_LOCALE, Platform] }
    ],
    exports: [
        ...sharedComponents
    ]
})
export class AppSharedComponentsModule { }
