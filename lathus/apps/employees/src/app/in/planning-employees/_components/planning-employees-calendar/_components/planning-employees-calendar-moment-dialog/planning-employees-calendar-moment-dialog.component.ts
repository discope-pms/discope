import { Component, Inject, OnDestroy, OnInit } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { CalendarService } from '../../../../_services/calendar.service';
import { Employee, TimeSlot } from '../../../../../../../type';
import { EnvService } from 'sb-shared-lib';
import { combineLatest, from, Subject } from 'rxjs';
import { takeUntil } from 'rxjs/operators';
import { ApiService } from '../../../../../../_services/api.service';

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
        private api: ApiService,
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

    public getActivityColor(activity: any): string {
        if(activity.is_partner_event) {
            const mapPartnerEventColors: any = {
                camp_activity: '#7A8F78',
                leave: '#BFA58A',
                time_off: '#8C6E5E',
                other: '#6C7A91',
                rest: '#6F5B4D',
                trainer: '#C27A5A',
                training: '#8F4E3A'
            };

            return mapPartnerEventColors[activity.event_type];
        }
        else if(activity.product_model_id.activity_color) {
            return activity.product_model_id.activity_color;
        }

        return '#BAA9A2';
    }

    public close() {
        this.dialogRef.close();
    }
}
