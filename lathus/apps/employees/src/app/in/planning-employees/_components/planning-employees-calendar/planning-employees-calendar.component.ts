import { AfterViewInit, Component, ElementRef, OnDestroy, OnInit } from '@angular/core';
import { ActivityMap, Employee } from '../../../../../type';
import { EnvService } from 'sb-shared-lib';
import { combineLatest } from 'rxjs';
import { CalendarService } from '../../_services/calendar.service';

@Component({
    selector: 'planning-employees-calendar',
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
        private el: ElementRef
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
    }

    ngOnInit() {
        combineLatest([this.calendar.dateFrom$, this.calendar.daysDisplayedQty$]).subscribe(
            ([dateFrom, daysDisplayedQty]) => {
                this.refreshDaysIndexes(dateFrom, daysDisplayedQty);
            }
        );

        combineLatest([this.calendar.employeeList$, this.calendar.selectedEmployeesIds$]).subscribe(
            ([employeeList, selectedEmployeesIds]) => {
                this.employeeList = employeeList.filter(e => selectedEmployeesIds.includes(e.id));
            }
        );

        this.calendar.activityMap$.subscribe((activityMap) => {
            this.activityMap = activityMap;
        });

        this.calendar.loading$.subscribe((loading) => {
            this.loading = loading;
        })

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
