import { NgModule } from '@angular/core';
import { PreloadAllModules, RouterModule, Routes } from '@angular/router';
import { AppComponent } from './in/app.component';
import { SignInComponent } from './in/auth/sign-in/sign-in.component';
import { UserSettingsComponent } from './in/user-settings/user-settings.component';
import { LayoutComponent } from './in/_layout/layout/layout.component';

const routes: Routes = [
    /*
        Route for the default path '/', which will match AppComponent
        Redirects to '/bookings/pending'
    */
    {
        path: '',
        component: AppComponent,
        pathMatch: 'full'
    },

    /*
        Routes specific to current app
    */
    {
        path: 'auth/sign-in',
        component: SignInComponent
    },

    /*
       Routes under the LayoutComponent
       The empty path here ensures it matches for any routes without an initial segment
    */
    {
        path: '',
        component: LayoutComponent,
        children: [
            {
                path: 'user-settings',
                component: UserSettingsComponent
            },
            {
                path: 'center/:center_id',
                loadChildren: () => import('./in/center/center.module').then(m => m.AppCenterModule)
            },
            {
                path: 'bookings',
                loadChildren: () => import('./in/bookings/bookings.module').then(m => m.AppBookingsModule)
            },
            {
                path: 'booking-inspection/:booking_inspection_id',
                loadChildren: () => import('./in/booking-inspection/booking-inspection.module').then(m => m.AppBookingInspectionModule)
            }
        ]
    },

    /*
        Wildcard route for any other paths
        Redirects to '/bookings/pending'
    */
    {
        path: '**',
        component: AppComponent
    }
];

@NgModule({
    imports: [
        RouterModule.forRoot(routes, { preloadingStrategy: PreloadAllModules, onSameUrlNavigation: 'reload', useHash: true })
    ],
    exports: [RouterModule]
})
export class AppRoutingModule { }
