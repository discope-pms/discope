import { Component, HostListener, OnInit } from '@angular/core';
import { CalendarService } from './_services/calendar.service';

@Component({
    selector: 'planning-employees',
    templateUrl: 'planning-employees.component.html',
    styleUrls: ['planning-employees.component.scss'],
    providers: [CalendarService]
})
export class PlanningEmployeesComponent implements OnInit  {

    constructor(
        private calendar: CalendarService
    ) {
    }

    ngOnInit() {
        this.calendar.init();

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

        this.calendar.setDaysDisplayedQty(daysDisplayedQty);
    }
}
