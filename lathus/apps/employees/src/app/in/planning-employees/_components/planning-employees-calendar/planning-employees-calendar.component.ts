import { AfterViewInit, Component, ElementRef, OnDestroy, OnInit } from '@angular/core';
import { ActivityMap, ActivityMapActivity, Employee } from '../../../../../type';
import { EnvService } from 'sb-shared-lib';
import {combineLatest, Subject} from 'rxjs';
import { CalendarService } from '../../_services/calendar.service';
import { MomentDialogOpenData, PlanningEmployeesCalendarMomentDialogComponent } from './_components/planning-employees-calendar-moment-dialog/planning-employees-calendar-moment-dialog.component';
import {MatDialog} from "@angular/material/dialog";
import {takeUntil} from "rxjs/operators";

@Component({
    selector: 'app-planning-employees-calendar',
    templateUrl: 'planning-employees-calendar.component.html',
    styleUrls: ['planning-employees-calendar.component.scss']
})
export class PlanningEmployeesCalendarComponent implements OnInit, AfterViewInit, OnDestroy {

    public employeeList: Employee[] = [];
    public activityMap: ActivityMap = {};
    public daysIndexes: string[] = [];

    public loading = true;

    private locale: string|null = null;

    private startX = 0;
    private currentX = 0;
    private isSwiping = false;
    private swipeThresholdX = 100;

    private startY = 0;
    private currentY = 0;
    private startOnTop = false;

    private destroy$ = new Subject<void>();

    private onTouchStart = (event: TouchEvent) => {
        this.startX = event.touches[0].clientX;
        this.currentX = event.touches[0].clientX;

        this.startY = event.touches[0].clientY;
        this.currentY = event.touches[0].clientY;
        this.startOnTop = this.el.nativeElement.scrollTop === 0;

        this.isSwiping = true;
    }

    private onTouchMove = (event: TouchEvent) => {
        if(!this.isSwiping) return;
        this.currentX = event.touches[0].clientX;
        this.currentY = event.touches[0].clientY;
    }

    private onTouchEnd = (event: TouchEvent) => {
        if(!this.isSwiping) return;
        this.isSwiping = false;

        const deltaX = this.currentX - this.startX;
        if(deltaX < -this.swipeThresholdX) {
            this.onNextDate();
        }
        else if (deltaX > this.swipeThresholdX) {
            this.onPreviousDate();
        }

        if(this.startOnTop) {
            const changeY = this.startY - this.currentY;
            const changeX = this.startX - this.currentX;
            if(this.startOnTop && this.isPullDown(changeY, changeX)) {
                this.calendar.refresh();
            }
        }
    }

    private isPullDown(dY: number, dX: number) {
        return (
            dY < 0 &&
            ((Math.abs(dX) <= 100 && Math.abs(dY) >= 100) ||
                (Math.abs(dX) / Math.abs(dY) <= 0.3 && dY >= 60))
        );
    }

    constructor(
        private calendar: CalendarService,
        private env: EnvService,
        private el: ElementRef,
        private dialog: MatDialog
    ) {
    }

    ngAfterViewInit() {
        const el = this.el.nativeElement;
        el.addEventListener('touchstart', this.onTouchStart, { passive: true });
        el.addEventListener('touchmove', this.onTouchMove, { passive: true });
        el.addEventListener('touchend',  this.onTouchEnd,  { passive: true });
    }

    ngOnDestroy() {
        const el = this.el.nativeElement;
        el.removeEventListener('touchstart', this.onTouchStart);
        el.removeEventListener('touchmove',  this.onTouchMove);
        el.removeEventListener('touchend',   this.onTouchEnd);

        this.destroy$.next();
        this.destroy$.complete();
    }

    ngOnInit() {
        combineLatest([this.calendar.dateFrom$, this.calendar.daysDisplayedQty$])
            .pipe(takeUntil(this.destroy$))
            .subscribe(([dateFrom, daysDisplayedQty]) => {
                this.refreshDaysIndexes(dateFrom, daysDisplayedQty);
            });

        combineLatest([this.calendar.employeeList$, this.calendar.employeesIdsToDisplay$])
            .pipe(takeUntil(this.destroy$))
            .subscribe(([employeeList, employeesIdsToDisplay]) => {
                this.employeeList = employeeList.filter(e => employeesIdsToDisplay.includes(e.id));
            }
        );

        const timeSlotCodes: ('AM'|'PM'|'EV')[] = ['AM', 'PM', 'EV'];
        combineLatest([this.calendar.activityMap$, this.calendar.productModelsIdsToDisplay$])
            .pipe(takeUntil(this.destroy$))
            .subscribe(([activityMap, productModelsIdsToDisplay]) => {
                const activityMapToDisplay: ActivityMap = JSON.parse(JSON.stringify(activityMap));

                for(let userId in activityMapToDisplay) {
                    const userActivityMap = activityMap[userId];
                    for(let dateIndex in userActivityMap) {
                        const dateActivityMap = userActivityMap[dateIndex];
                        for(let timeSlotCode of timeSlotCodes) {
                            if(dateActivityMap[timeSlotCode] !== undefined) {
                                const allItems = dateActivityMap[timeSlotCode];
                                const allActivities = allItems.filter((a: any) => !a.is_partner_event);
                                const allPartnerEvents = allItems.filter((a: any) => a.is_partner_event);

                                const activitiesToDisplay: ActivityMapActivity[] = [];
                                for(let activity of allActivities) {
                                    // don't show activity if the product model is not selected
                                    if(productModelsIdsToDisplay.includes(activity.product_model_id.id)) {
                                        activitiesToDisplay.push(activity);
                                    }
                                }

                                for(let partnerEvent of allPartnerEvents) {
                                    let partnerHasActivity = false;
                                    for(let activity of allActivities) {
                                        if(partnerEvent.camp_group_id && activity.camp_group_id === partnerEvent.camp_group_id) {
                                            partnerHasActivity = true;
                                        }
                                    }

                                    // don't show camp partner event if employee handles the activity
                                    if(!partnerHasActivity) {
                                        activitiesToDisplay.push(partnerEvent);
                                    }
                                }

                                activityMapToDisplay[userId][dateIndex][timeSlotCode] = activitiesToDisplay;
                            }
                        }
                    }
                }

                this.activityMap = activityMapToDisplay;
            }
        );

        this.calendar.loading$
            .pipe(takeUntil(this.destroy$))
            .subscribe((loading) => {
                this.loading = loading;
            });

        this.env.getEnv().then((env: any) => {
            this.locale = env.locale;
        });
    }

    public refreshDaysIndexes(dateFrom: Date, days: number) {
        const daysIndexes: string[] = [];
        for(let i = 0; i < days; i++) {
            const date = new Date(dateFrom.getTime() + i * 24 * 60 * 60 * 1000);
            daysIndexes.push(date.toISOString().split('T')[0]);
        }

        this.daysIndexes = daysIndexes;
    }

    public formatDate(dateIndex: string): string {
        if(!this.locale) {
            return '';
        }
        const date = new Date(dateIndex);
        const formatter = new Intl.DateTimeFormat(this.locale.replace('_', '-'), { weekday: 'short', day: '2-digit', month: '2-digit' });
        return formatter.format(date);
    }

    private onPreviousDate() {
        this.calendar.setPreviousDate();
    }

    private onNextDate() {
        this.calendar.setNextDate();
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

    public openMoment(employeeId: number, dayIndex: string, timeSlotCode: 'AM'|'PM'|'EV') {
        const employee = this.employeeList.find(e => e.id === employeeId);
        if(!employee) {
            return;
        }

        const data: MomentDialogOpenData = { calendar: this.calendar, employee, dayIndex, timeSlotCode };

        const dialog = this.dialog.open(PlanningEmployeesCalendarMomentDialogComponent, {
            width: '100vw',
            height: '100vh',
            maxWidth: '100vw',
            maxHeight: '100vh',
            panelClass: 'full-screen-dialog',
            data: data
        });

        dialog.afterClosed().subscribe(() => {
            this.calendar.refresh();
        });
    }
}
