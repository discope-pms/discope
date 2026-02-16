import { Component, HostListener, OnInit } from '@angular/core';
import { Partner, ActivityMap } from '../../../../../type';
import { EnvService } from 'sb-shared-lib';
import { combineLatest } from 'rxjs';
import { CalendarService } from '../../../../_services/calendar.service';

@Component({
    selector: 'planning-employees-calendar',
    templateUrl: 'planning-employees-calendar.component.html',
    styleUrls: ['planning-employees-calendar.component.scss']
})
export class PlanningEmployeesCalendarComponent implements OnInit {

    public partnerList: Partner[] = [];
    public activityMap: ActivityMap = {};
    public daysIndexes: string[] = [];

    private locale: string|null = null;

    private startX = 0;
    private currentX = 0;
    private isSwiping = false;
    private swipeThreshold = 100;

    @HostListener('touchstart', ['$event'])
    onTouchStart(event: TouchEvent) {
        this.startX = event.touches[0].clientX;
        this.isSwiping = true;
    }

    @HostListener('touchmove', ['$event'])
    onTouchMove(event: TouchEvent) {
        if (!this.isSwiping) return;
        this.currentX = event.touches[0].clientX;
    }

    @HostListener('touchend', ['$event'])
    onTouchEnd(event: TouchEvent) {
        if (!this.isSwiping) return;
        this.isSwiping = false;

        const deltaX = this.currentX - this.startX;
        if (deltaX < -this.swipeThreshold) {
            this.onNextDate();
        }
        else if (deltaX > this.swipeThreshold) {
            this.onPreviousDate();
        }
    }

    constructor(
        private calendar: CalendarService,
        private env: EnvService
    ) {
    }

    ngOnInit() {
        combineLatest([this.calendar.dateFrom$, this.calendar.daysDisplayedQty$]).subscribe(
            ([dateFrom, daysDisplayedQty]) => {
                this.refreshDaysIndexes(dateFrom, daysDisplayedQty);
            }
        );

        this.calendar.partnerList$.subscribe((partnerList) => {
            this.partnerList = partnerList;
        });

        this.calendar.activityMap$.subscribe((activityMap) => {
            this.activityMap = activityMap;
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
}
