import { Component, HostListener, OnInit } from '@angular/core';
import { AppService } from '../../../../_services/app.service';
import { Partner, ActivityMap } from '../../../../../type';
import { combineLatest } from 'rxjs';

@Component({
    selector: 'planning-employees-calendar',
    templateUrl: 'planning-employees-calendar.component.html',
    styleUrls: ['planning-employees-calendar.component.scss']
})
export class PlanningEmployeesCalendarComponent implements OnInit {

    public partnerList: Partner[] = [];
    public activityMap: ActivityMap = {};
    public daysIndexes: string[] = [];

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
        private app: AppService
    ) {
    }

    ngOnInit() {
        combineLatest([this.app.dateFrom$, this.app.daysDisplayedQty$]).subscribe(
            ([dateFrom, daysDisplayedQty]) => {
                this.refreshDaysIndexes(dateFrom, daysDisplayedQty);
            }
        );

        this.app.partnerList$.subscribe((partnerList) => {
            this.partnerList = partnerList;
        });

        this.app.activityMap$.subscribe((activityMap) => {
            this.activityMap = activityMap;
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

    private onPreviousDate() {
        this.app.setPreviousDate();
    }

    private onNextDate() {
        this.app.setNextDate();
    }
}
