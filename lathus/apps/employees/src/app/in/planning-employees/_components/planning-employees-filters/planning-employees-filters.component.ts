import { Component, OnDestroy, OnInit } from '@angular/core';
import { EnvService } from 'sb-shared-lib';
import { CalendarService } from '../../_services/calendar.service';
import { FilterDialogOpenData, PlanningEmployeesFiltersDialogComponent } from './_components/planning-employees-filters-dialog/planning-employees-filters-dialog.component';
import { MatDialog } from '@angular/material/dialog';
import { combineLatest, from, Subject } from 'rxjs';
import { takeUntil } from 'rxjs/operators';

@Component({
    selector: 'app-planning-employees-filters',
    templateUrl: 'planning-employees-filters.component.html',
    styleUrls: ['planning-employees-filters.component.scss']
})
export class PlanningEmployeesFiltersComponent implements OnInit, OnDestroy  {

    public dateFromFormatted = '';
    public dateToFormatted = '';

    public displayMultipleDays = false;

    private destroy$ = new Subject<void>();

    constructor(
        private calendar: CalendarService,
        private env: EnvService,
        private dialog: MatDialog
    ) {
    }

    async ngOnInit() {
        combineLatest([
            from(this.env.getEnv()),
            this.calendar.dateFrom$
        ])
            .pipe(takeUntil(this.destroy$))
            .subscribe(([env, dateFrom]: any) => {
                if (!env) return;

                this.dateFromFormatted = this.formatDate(dateFrom, env.locale);
            });

        combineLatest([
            from(this.env.getEnv()),
            this.calendar.dateTo$
        ])
            .pipe(takeUntil(this.destroy$))
            .subscribe(([env, dateTo]: any) => {
                if (!env) return;

                this.dateToFormatted = this.formatDate(dateTo, env.locale);
            });

        this.calendar.daysDisplayedQty$
            .pipe(takeUntil(this.destroy$))
            .subscribe((daysDisplayedQty) => {
                this.displayMultipleDays = daysDisplayedQty > 1;
            });
    }

    ngOnDestroy() {
        this.destroy$.next();
        this.destroy$.complete();
    }

    private formatDate(date: Date, locale: string): string {
        date = new Date(date.getTime());
        const formatter = new Intl.DateTimeFormat(locale.replace('_', '-'), { weekday: 'short', day: '2-digit', month: '2-digit' });
        return formatter.format(date);
    }

    public onFilter() {
        const data: FilterDialogOpenData = { calendar: this.calendar };

        this.dialog.open(PlanningEmployeesFiltersDialogComponent, {
            width: '100vw',
            height: '100vh',
            maxWidth: '100vw',
            maxHeight: '100vh',
            panelClass: 'full-screen-dialog',
            data: data
        });
    }

    public onPreviousDate() {
        this.calendar.setPreviousDate();
    }

    public onNextDate() {
        this.calendar.setNextDate();
    }
}
