import { NgModule } from '@angular/core';
import { DateAdapter, MAT_DATE_LOCALE } from '@angular/material/core';
import { Platform } from '@angular/cdk/platform';

import { SharedLibModule, CustomDateAdapter } from 'sb-shared-lib';
import { CenterRoutingModule } from './center-routing.module';

import { ConsumptionMetersComponent } from './consumption-meters/consumption-meters.component';
import { ConsumptionMeterNewComponent } from './consumption-meter/new/consumption-meter-new.component';
import { ConsumptionMeterEditComponent } from './consumption-meter/edit/consumption-meter-edit.component';
import { ConsumptionMeterFormComponent } from './consumption-meter/_components/consumption-meter-form/consumption-meter-form.component';
import { ProductSelectComponent } from './consumption-meter/_components/consumption-meter-form/_components/product-select/product-select.component';
import { ConsumptionMetersItemComponent } from './_components/consumption-meters-item.component';
import { AppSharedComponentsModule } from '../_components/shared-components.module';
import { AppSharedPipesModule } from '../../_pipes/shared-pipes.module';

@NgModule({
    imports: [
        SharedLibModule,
        CenterRoutingModule,
        AppSharedComponentsModule,
        AppSharedPipesModule
    ],
    declarations: [
        ConsumptionMetersComponent,
        ConsumptionMeterNewComponent,
        ConsumptionMeterEditComponent,
        ConsumptionMeterFormComponent,
        ProductSelectComponent,
        ConsumptionMetersItemComponent
    ],
    providers: [
        { provide: DateAdapter, useClass: CustomDateAdapter, deps: [MAT_DATE_LOCALE, Platform] }
    ]
})
export class AppCenterModule { }
