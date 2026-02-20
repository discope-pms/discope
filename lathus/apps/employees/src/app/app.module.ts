import { NgModule, LOCALE_ID } from '@angular/core';

import { BrowserModule } from '@angular/platform-browser';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';

import { DateAdapter, MatNativeDateModule, MAT_DATE_LOCALE } from '@angular/material/core';
import { Platform, PlatformModule } from '@angular/cdk/platform';

import { SharedLibModule, AuthInterceptorService, CustomDateAdapter } from 'sb-shared-lib';
import { NgxMaterialTimepickerModule } from 'ngx-material-timepicker';

import { AppRoutingModule } from './app-routing.module';
import { AppRootComponent } from './app.root.component';
import { AppComponent } from './in/app.component';
import { LayoutComponent } from './in/_layout/layout/layout.component';
import { SignInComponent } from './in/auth/sign-in/sign-in.component';
import { AppSharedComponentsModule } from './in/_components/shared-components.module';

import { AppSharedPipesModule } from './_pipes/shared-pipes.module';
import { TranslationService } from './_services/translation.service';

import { HttpErrorInterceptorService } from './_services/HttpErrorInterceptorService';

/* HTTP requests interception dependencies */
import { HTTP_INTERCEPTORS } from '@angular/common/http';

import { registerLocaleData } from '@angular/common';
import localeFr from '@angular/common/locales/fr';
import { MAT_SNACK_BAR_DEFAULT_OPTIONS } from '@angular/material/snack-bar';

registerLocaleData(localeFr);

@NgModule({
    declarations: [
        AppRootComponent,
        AppComponent,
        LayoutComponent,
        SignInComponent
    ],
    imports: [
        AppRoutingModule,
        BrowserModule,
        BrowserAnimationsModule,
        SharedLibModule,
        AppSharedComponentsModule,
        MatNativeDateModule,
        PlatformModule,
        AppSharedPipesModule,
        NgxMaterialTimepickerModule.setOpts('fr-BE', 'latn'),
        AppSharedComponentsModule
    ],
    providers: [
        { provide: HTTP_INTERCEPTORS, useClass: AuthInterceptorService, multi: true },
        { provide: MAT_SNACK_BAR_DEFAULT_OPTIONS, useValue: {duration: 4000, horizontalPosition: 'start' }},
        { provide: MAT_DATE_LOCALE, useValue: 'fr-BE' },
        { provide: LOCALE_ID, useValue: 'fr-BE' },
        { provide: DateAdapter, useClass: CustomDateAdapter, deps: [MAT_DATE_LOCALE, Platform] },
        TranslationService,
        HttpErrorInterceptorService
    ],
    exports: [],
    bootstrap: [AppRootComponent]
})
export class AppModule {
}
