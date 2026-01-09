import { Component, HostListener, OnInit } from '@angular/core';
import { AppService } from '../../../../_services/app.service';
import { Partner, ActivityMapDay, ActivityMap } from '../../../../../type';

@Component({
    selector: 'planning-employees-calendar-day',
    templateUrl: 'planning-employees-calendar-day.component.html',
    styleUrls: ['planning-employees-calendar-day.component.scss']
})
export class PlanningEmployeesCalendarDayComponent implements OnInit {

    public dateIndex: string = (new Date()).toISOString().split('T')[0];
    public partnerList: Partner[] = [];
    public activityMap: ActivityMap = {};

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
        this.app.dateFrom$.subscribe((dateFrom) => {
            this.dateIndex = dateFrom.toISOString().split('T')[0];
        });

        this.app.partnerList$.subscribe((partnerList) => {
            this.partnerList = partnerList;
        });

        this.app.activityMap$.subscribe((activityMap) => {
            this.activityMap = activityMap;
        });
    }

    private onPreviousDate() {
        this.app.setPreviousDate();
    }

    private onNextDate() {
        this.app.setNextDate();
    }
}
