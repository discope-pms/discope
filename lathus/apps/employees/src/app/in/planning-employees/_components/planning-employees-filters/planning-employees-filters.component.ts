import { Component, OnInit } from '@angular/core';
import { AppService } from '../../../../_services/app.service';
import { EnvService } from 'sb-shared-lib';
import {formatDate} from "@angular/common";
import {combineLatest} from "rxjs";

@Component({
    selector: 'planning-employees-filters',
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
        private app: AppService,
        private env: EnvService
    ) {
    }

    async ngOnInit() {
        this.app.dateFrom$.subscribe((dateFrom) => {
            this.dateFrom = dateFrom;
            if(this.locale) {
                this.dateFromFormatted = this.formatDate(this.dateFrom, this.locale);
            }
        });

        this.app.dateTo$.subscribe((dateTo) => {
            this.dateTo = dateTo;
            if(this.locale) {
                this.dateToFormatted = this.formatDate(this.dateTo, this.locale);
            }
        });

        this.app.daysDisplayedQty$.subscribe((daysDisplayedQty) => {
            this.displayMultipleDays = daysDisplayedQty > 1;
        });

        this.env.getEnv().then((env: any) => {
            this.locale = env.locale;
            this.dateFromFormatted = this.formatDate(this.dateFrom, env.locale);
        });
    }

    private formatDate(date: Date, locale: string): string {
        date = new Date(date.getTime());
        const formatter = new Intl.DateTimeFormat(locale, { weekday: 'short', day: '2-digit', month: '2-digit' });
        return formatter.format(date);
    }

    public onFilter() {
        console.log('on open filters');
    }

    public onPreviousDate() {
        this.app.setPreviousDate();
    }

    public onNextDate() {
        this.app.setNextDate();
    }
}
