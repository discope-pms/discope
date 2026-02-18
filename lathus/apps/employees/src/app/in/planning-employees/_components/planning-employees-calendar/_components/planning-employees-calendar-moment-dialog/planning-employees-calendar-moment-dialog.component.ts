import {Component, Inject, OnDestroy, OnInit} from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { CalendarService } from '../../../../_services/calendar.service';
import { Employee, TimeSlot } from '../../../../../../../type';
import { EnvService } from 'sb-shared-lib';
import { combineLatest, from, Subject } from "rxjs";
import { takeUntil } from "rxjs/operators";

export interface MomentDialogOpenData {
    employee: Employee,
    dayIndex: string,
    timeSlotCode: 'AM'|'PM'|'EV'
}

@Component({
    selector: 'app-planning-employees-calendar-moment-dialog',
    templateUrl: 'planning-employees-calendar-moment-dialog.component.html',
    styleUrls: ['planning-employees-calendar-moment-dialog.component.scss']
})
export class PlanningEmployeesCalendarMomentDialogComponent implements OnInit, OnDestroy {

    public employee: Employee;
    private dayIndex: string;
    private timeSlotCode: string;

    public timeDetailsText = '';

    public dateFromFormatted = '';

    private destroy$ = new Subject<void>();

    constructor(
        private env: EnvService,
        private calendar: CalendarService,
        private dialogRef: MatDialogRef<PlanningEmployeesCalendarMomentDialogComponent>,
        @Inject(MAT_DIALOG_DATA) public data: MomentDialogOpenData
    ) {
        this.employee = data.employee;
        this.dayIndex = data.dayIndex;
        this.timeSlotCode = data.timeSlotCode;
    }

    ngOnInit() {
        combineLatest([
            from(this.env.getEnv()),
            this.calendar.timeSlotList$
        ])
            .pipe(takeUntil(this.destroy$))
            .subscribe(([env, timeSlotList]: any) => {
                if(!env || !timeSlotList.length) return;

                const timeSlot: TimeSlot = timeSlotList.find((ts: TimeSlot) => ts.code === this.timeSlotCode);
                if(timeSlot) {
                    this.timeDetailsText = this.formatDate(new Date(this.dayIndex), env.locale) + ' - ' + timeSlot.name.toLowerCase();
                }
            });

        // TODO: Display info employee, day and moment

        // TODO: List activities

        // TODO: List partner event

        // Open :
        // TODO: Open modification dialog for activities
        // TODO: Open modification dialog for partner events
    }

    ngOnDestroy() {
        this.destroy$.next();
        this.destroy$.complete();
    }

    private formatDate(date: Date, locale: string): string {
        date = new Date(date.getTime());
        const formatter = new Intl.DateTimeFormat(locale.replace('_', '-'), { weekday: 'long', day: '2-digit', month: '2-digit' });
        return formatter.format(date);
    }

    public close() {
        this.dialogRef.close();
    }
}
