import { Component, OnInit } from '@angular/core';

@Component({
    selector: 'planning-employees',
    templateUrl: './employees.component.html',
    styleUrls: ['./employees.component.scss']
})
export class PlanningEmployeesComponent implements OnInit {

    ngOnInit() {
        window.location.href = '/booking/#/planning/employees';
    }
}
