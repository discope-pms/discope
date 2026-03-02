import { NgModule } from '@angular/core';
import { DateAdapter, MAT_DATE_LOCALE } from '@angular/material/core';
import { Platform } from '@angular/cdk/platform';

import { SharedLibModule, CustomDateAdapter } from 'sb-shared-lib';
import { SelectCenterHeaderComponent } from '../_layout/layout/_components/select-center-header.component';
import { ContentLayoutComponent } from '../_layout/content-layout/content-layout.component';
import { IconButtonComponent } from './icon-button/icon-button.component';
import { EmptyTableMessageComponent } from './empty-table-message/empty-table-message.component';

const sharedComponents = [
    SelectCenterHeaderComponent, ContentLayoutComponent, IconButtonComponent, EmptyTableMessageComponent
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
