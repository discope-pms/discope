import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { TaskComponent } from './task.component';


const routes: Routes = [
    // single task (to be loaded only when URL points exactly to /task/:task_id without sub route)
    {
        path: '',
        pathMatch: 'full',
        component: TaskComponent
    }
];

@NgModule({
    imports: [RouterModule.forChild(routes)],
    exports: [RouterModule]
})
export class TaskRoutingModule {}
