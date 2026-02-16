import { Component, HostListener, OnDestroy, OnInit } from '@angular/core';
import { CalendarService } from '../../_services/calendar.service';

@Component({
    selector: 'planning-employees',
    templateUrl: 'planning-employees.component.html',
    styleUrls: ['planning-employees.component.scss'],
    providers: [CalendarService]
})
export class PlanningEmployeesComponent implements OnInit, OnDestroy  {

    constructor(
        private calendar: CalendarService
    ) {
    }

    ngOnInit() {
        this.handleScreenWidthChange(window.innerWidth);
    }

    ngOnDestroy() {
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

        this.calendar.setDaysDisplayedQty(daysDisplayedQty);
    }
}
