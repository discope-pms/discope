import { NgModule } from '@angular/core';
import { DateAdapter, MAT_DATE_LOCALE } from '@angular/material/core';
import { Platform } from '@angular/cdk/platform';

import { SharedLibModule, CustomDateAdapter } from 'sb-shared-lib';
import { UserSettingsRoutingModule } from './user-settings-routing.module';

import { UserSettingsComponent } from './user-settings.component';
import { AppSharedPipesModule } from '../../_pipes/shared-pipes.module';
import { AppSharedComponentsModule } from '../_components/shared-components.module';

@NgModule({
    imports: [
        SharedLibModule,
        UserSettingsRoutingModule,
        AppSharedPipesModule,
        AppSharedComponentsModule
    ],
    declarations: [
        UserSettingsComponent
    ],
    providers: [
        { provide: DateAdapter, useClass: CustomDateAdapter, deps: [MAT_DATE_LOCALE, Platform] }
    ]
})
export class AppUserSettingsModule { }
