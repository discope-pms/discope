import { Component, OnInit } from '@angular/core';
import { AppService } from '../../../../_services/app.service';
import { EnvService } from 'sb-shared-lib';
import {formatDate} from "@angular/common";

@Component({
    selector: 'planning-employees-filters',
    templateUrl: 'planning-employees-filters.component.html',
    styleUrls: ['planning-employees-filters.component.scss']
})
export class PlanningEmployeesFiltersComponent implements OnInit  {

    private date: Date = new Date();
    public dateFormatted = '';

    private locale: string|null = null;

    constructor(
        private app: AppService,
        private env: EnvService
    ) {
    }

    async ngOnInit() {
        this.app.dateFrom$.subscribe((dateFrom) => {
            this.date = dateFrom;
            if(this.locale) {
                this.dateFormatted = this.formatDate(this.date, this.locale);
            }
        });

        this.env.getEnv().then((env: any) => {
            this.locale = env.locale;
            this.dateFormatted = this.formatDate(this.date, env.locale);
        });
    }

    private formatDate(date: Date, locale: string): string {
        date = new Date(date.getTime());
        const formatter = new Intl.DateTimeFormat(locale, { weekday: 'short', day: '2-digit', month: '2-digit' });
        return formatter.format(date);
    }

    public onAdd() {
        console.log('on add');
    }

    public onPreviousDate() {
        this.app.setPreviousDate();
    }

    public onNextDate() {
        this.app.setNextDate();
    }
}
