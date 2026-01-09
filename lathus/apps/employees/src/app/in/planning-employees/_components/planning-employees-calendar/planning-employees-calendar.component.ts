import { Component, HostListener, OnInit } from '@angular/core';
import { AppService } from '../../../../_services/app.service';
import { ActivityMap } from '../../../../../type';

@Component({
    selector: 'planning-employees-calendar',
    templateUrl: 'planning-employees-calendar.component.html',
    styleUrls: ['planning-employees-calendar.component.scss']
})
export class PlanningEmployeesCalendarComponent implements OnInit {

    public activityMap: ActivityMap = {};

    private startX = 0;
    private currentX = 0;
    private isSwiping = false;
    private swipeThreshold = 100;

    @HostListener('touchstart', ['$event'])
    onTouchStart(event: TouchEvent) {
        event.preventDefault();
        this.startX = event.touches[0].clientX;
        this.isSwiping = true;
    }

    @HostListener('touchmove', ['$event'])
    onTouchMove(event: TouchEvent) {
        if (!this.isSwiping) return;
        event.preventDefault();
        this.currentX = event.touches[0].clientX;
        let deltaX = this.currentX - this.startX;
        if(deltaX > this.swipeThreshold) {
            deltaX = this.swipeThreshold;
        }
        else if(deltaX < -this.swipeThreshold) {
            deltaX = -this.swipeThreshold;
        }
        (event.target as HTMLElement).style.transform = `translateX(${deltaX}px)`;
    }

    @HostListener('touchend', ['$event'])
    onTouchEnd(event: TouchEvent) {
        if (!this.isSwiping) return;
        this.isSwiping = false;

        const deltaX = this.currentX - this.startX;
        const host = event.target as HTMLElement;

        if (deltaX < -this.swipeThreshold) {
            this.onNextDate();
        }
        else if (deltaX > this.swipeThreshold) {
            this.onPreviousDate();
        }

        host.style.transform = `translateX(0px)`;
    }

    constructor(
        private app: AppService
    ) {
    }

    ngOnInit() {
        this.app.activityMap$.subscribe((activityMap) => {
            this.activityMap = activityMap;
        });
    }

    private onPreviousDate() {
        console.log('onPreviousDate');
        this.app.setPreviousDate();
    }

    private onNextDate() {
        console.log('onNextDate');
        this.app.setNextDate();
    }
}
