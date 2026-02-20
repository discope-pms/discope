import { NgModule } from '@angular/core';
import { PreloadAllModules, RouterModule, Routes } from '@angular/router';
import { AppComponent } from './in/app.component';
import { SignInComponent } from './in/auth/sign-in/sign-in.component';
import { LayoutComponent } from './in/_layout/layout/layout.component';

const routes: Routes = [
    /*
        Route for the default path '/', which will match AppComponent
        Redirects to '/planning-employees'
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
                path: 'planning-employees',
                loadChildren: () => import('./in/planning-employees/planning-employees.module').then(m => m.AppPlanningEmployeesModule)
            },
            {
                path: 'user-settings',
                loadChildren: () => import('./in/user-settings/user-settings.module').then(m => m.AppUserSettingsModule)
            }
        ]
    },

    /*
        Wildcard route for any other paths
        Redirects to '/planning-employees'
    */
    {
        path: '**',
        redirectTo: 'planning-employees'
    }
];

@NgModule({
    imports: [
        RouterModule.forRoot(routes, { preloadingStrategy: PreloadAllModules, onSameUrlNavigation: 'reload', useHash: true })
    ],
    exports: [RouterModule]
})
export class AppRoutingModule { }
