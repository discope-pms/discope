import { Component, OnInit } from '@angular/core';
import { AppService } from '../../_services/app.service';
import { TypeDisplay } from '../../../type';

@Component({
    selector: 'planning-employees',
    templateUrl: 'planning-employees.component.html',
    styleUrls: ['planning-employees.component.scss']
})
export class PlanningEmployeesComponent implements OnInit  {

    public displayType: TypeDisplay = 'day';

    constructor(
        private app: AppService
    ) {
    }

    ngOnInit() {
        this.app.displayType$.subscribe((displayType) => {
            this.displayType = displayType;
        });
    }
}
