import { Component, OnInit } from '@angular/core';
import { AppService } from '../../_services/app.service';

@Component({
    selector: 'planning-employees',
    templateUrl: 'planning-employees.component.html',
    styleUrls: ['planning-employees.component.scss']
})
export class PlanningEmployeesComponent implements OnInit  {

    constructor(
        private app: AppService
    ) {
    }

    ngOnInit() {
        console.log("test");
    }
}
