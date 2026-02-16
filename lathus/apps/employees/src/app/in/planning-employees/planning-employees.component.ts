import {Component, HostListener, OnInit} from '@angular/core';
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
        this.handleScreenWidthChange(window.innerWidth);
    }

    @HostListener('window:resize', ['$event'])
    onWindowResize() {
        this.handleScreenWidthChange(window.innerWidth);
    }

    private handleScreenWidthChange(width: number) {
        const widthWithoutEmployeeCol = width - 150;

        let daysDisplayedQty = Math.floor(widthWithoutEmployeeCol / 250);
        if(daysDisplayedQty < 1) {
            daysDisplayedQty = 1;
        }

        this.app.setDaysDisplayedQty(daysDisplayedQty);
    }
}
