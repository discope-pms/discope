import { Component, OnInit } from '@angular/core';
import { AppService } from '../../../../_services/app.service';
import { ActivityMap } from '../../../../../type';

@Component({
    selector: 'planning-employees-calendar',
    templateUrl: 'planning-employees-calendar.component.html',
    styleUrls: ['planning-employees-calendar.component.scss']
})
export class PlanningEmployeesCalendarComponent implements OnInit {

    public activityMap: ActivityMap = {};

    constructor(
        private app: AppService
    ) {
    }

    ngOnInit() {
        this.app.activityMap$.subscribe((activityMap) => {
            this.activityMap = activityMap;
        });
    }
}
