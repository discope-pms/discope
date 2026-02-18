import { Component, OnInit } from '@angular/core';
import { EnvService } from 'sb-shared-lib';
import { CalendarService } from '../../_services/calendar.service';
import { PlanningEmployeesFiltersDialogComponent } from './_components/planning-employees-filters-dialog/planning-employees-filters-dialog.component';
import { MatDialog } from '@angular/material/dialog';

@Component({
    selector: 'app-planning-employees-filters',
    templateUrl: 'planning-employees-filters.component.html',
    styleUrls: ['planning-employees-filters.component.scss']
})
export class PlanningEmployeesFiltersComponent implements OnInit  {

    private dateFrom: Date = new Date();
    public dateFromFormatted = '';

    private dateTo: Date = new Date();
    public dateToFormatted = '';

    public displayMultipleDays = false;

    private locale: string|null = null;

    constructor(
        private calendar: CalendarService,
        private env: EnvService,
        private dialog: MatDialog
    ) {
    }

    async ngOnInit() {
        this.calendar.dateFrom$.subscribe((dateFrom) => {
            this.dateFrom = dateFrom;
            if(this.locale) {
                this.dateFromFormatted = this.formatDate(this.dateFrom, this.locale);
            }
        });

        this.calendar.dateTo$.subscribe((dateTo) => {
            this.dateTo = dateTo;
            if(this.locale) {
                this.dateToFormatted = this.formatDate(this.dateTo, this.locale);
            }
        });

        this.calendar.daysDisplayedQty$.subscribe((daysDisplayedQty) => {
            this.displayMultipleDays = daysDisplayedQty > 1;
        });

        this.env.getEnv().then((env: any) => {
            this.locale = env.locale;
            this.dateFromFormatted = this.formatDate(this.dateFrom, env.locale);
        });
    }

    private formatDate(date: Date, locale: string): string {
        date = new Date(date.getTime());
        const formatter = new Intl.DateTimeFormat(locale.replace('_', '-'), { weekday: 'short', day: '2-digit', month: '2-digit' });
        return formatter.format(date);
    }

    public onFilter() {
        this.dialog.open(PlanningEmployeesFiltersDialogComponent, {
            width: '100vw',
            height: '100vh',
            maxWidth: '100vw',
            maxHeight: '100vh',
            panelClass: 'full-screen-dialog'
        });
    }

    public onPreviousDate() {
        this.calendar.setPreviousDate();
    }

    public onNextDate() {
        this.calendar.setNextDate();
    }
}
