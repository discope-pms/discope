import { Component, Inject, OnDestroy, OnInit } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialog, MatDialogRef } from '@angular/material/dialog';
import { CalendarService } from '../../../../_services/calendar.service';
import { ActivityMapActivity, Employee, TimeSlot } from '../../../../../../../type';
import { EnvService } from 'sb-shared-lib';
import { combineLatest, from, Subject } from 'rxjs';
import { takeUntil } from 'rxjs/operators';
import {
    ActivityDialogData,
    PlanningEmployeesActivityDialogComponent
} from '../../../planning-employees-activity-dialog/planning-employees-activity-dialog.component';

export interface MomentDialogOpenData {
    calendar: CalendarService,
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

    private readonly calendar: CalendarService;

    public readonly employee: Employee;
    public dayIndex: string;
    public date: Date;
    private readonly timeSlotCode: 'AM'|'PM'|'EV';

    public timeDetailsText = '';

    public activities: any[] = [];

    private destroy$ = new Subject<void>();

    constructor(
        private env: EnvService,
        private dialog: MatDialog,
        private dialogRef: MatDialogRef<PlanningEmployeesCalendarMomentDialogComponent>,
        @Inject(MAT_DIALOG_DATA) public data: MomentDialogOpenData
    ) {
        this.calendar = data.calendar;
        this.employee = data.employee;
        this.dayIndex = data.dayIndex;
        this.date = new Date(data.dayIndex);
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
                    this.timeDetailsText = this.formatDate(this.date, env.locale) + ' - ' + timeSlot.name.toLowerCase();
                }
            });

        this.calendar.activityMap$.subscribe(activityMap => {
            let activities: any[] = [];
            if(activityMap?.[this.employee.id]?.[this.dayIndex]?.[this.timeSlotCode]?.length) {
                activities = activityMap?.[this.employee.id]?.[this.dayIndex]?.[this.timeSlotCode];
            }
            this.activities = activities;
        });
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

    public openActivity(activity: ActivityMapActivity) {
        const data: ActivityDialogData = { calendar: this.calendar, activity };

        this.dialog.open(PlanningEmployeesActivityDialogComponent, {
            width: '100vw',
            height: '100vh',
            maxWidth: '100vw',
            maxHeight: '100vh',
            panelClass: 'full-screen-dialog',
            data: data
        });
    }
}
