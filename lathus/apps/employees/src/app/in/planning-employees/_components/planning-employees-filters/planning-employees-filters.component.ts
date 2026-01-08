import { Component, OnInit } from '@angular/core';

@Component({
    selector: 'planning-employees-filters',
    templateUrl: 'planning-employees-filters.component.html',
    styleUrls: ['planning-employees-filters.component.scss']
})
export class PlanningEmployeesFiltersComponent implements OnInit  {

    constructor() {
    }

    ngOnInit() {
        console.log("test filters");
    }
}
