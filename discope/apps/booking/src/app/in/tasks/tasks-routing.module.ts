import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { TasksComponent } from './tasks.component';
import { TasksWaitingComponent } from './waiting/waiting.component';


const routes: Routes = [
    {
        path: 'waiting',
        pathMatch: 'full',
        component: TasksWaitingComponent
    },
    // single task (to be loaded only when URL points exactly to /task/:task_id without sub route)
    {
        path: '',
        pathMatch: 'full',
        component: TasksComponent
    }
];

@NgModule({
    imports: [RouterModule.forChild(routes)],
    exports: [RouterModule]
})
export class TaskRoutingModule {}
