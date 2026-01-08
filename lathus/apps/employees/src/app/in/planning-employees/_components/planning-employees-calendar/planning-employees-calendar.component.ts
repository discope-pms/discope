import { Component, OnInit } from '@angular/core';

@Component({
    selector: 'planning-employees-calendar',
    templateUrl: 'planning-employees-calendar.component.html',
    styleUrls: ['planning-employees-calendar.component.scss']
})
export class PlanningEmployeesCalendarComponent implements OnInit  {

    constructor() {
    }

    ngOnInit() {
        console.log("test filters");
    }
}
