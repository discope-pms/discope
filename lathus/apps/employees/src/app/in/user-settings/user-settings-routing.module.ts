import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { UserSettingsComponent } from './user-settings.component';

const routes: Routes = [
    {
        path: '',
        component: UserSettingsComponent,
        data: { title: 'TITLE_USER_SETTINGS', back: true }
    }
];

@NgModule({
    imports: [RouterModule.forChild(routes)],
    exports: [RouterModule]
})
export class UserSettingsRoutingModule {}
