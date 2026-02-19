import { Component, EventEmitter, Input, OnDestroy, OnInit, Output } from '@angular/core';
import { combineLatest, Subject } from 'rxjs';
import { CalendarService } from '../../_services/calendar.service';
import { takeUntil } from 'rxjs/operators';
import { ActivityMap, ActivityMapActivity } from '../../../../../type';

@Component({
    selector: 'app-planning-employees-unassigned',
    templateUrl: 'planning-employees-unassigned.component.html',
    styleUrls: ['planning-employees-unassigned.component.scss']
})
export class PlanningEmployeesUnassignedComponent implements OnInit, OnDestroy  {

    @Input() opened: boolean;

    @Output() open = new EventEmitter();
    @Output() close = new EventEmitter();

    public daysIndexes: string[] = [];

    public unassignedActivityMap: ActivityMap

    private destroy$ = new Subject<void>();

    constructor(
        private calendar: CalendarService
    ) {
    }

    ngOnInit() {
        combineLatest([this.calendar.dateFrom$, this.calendar.daysDisplayedQty$])
            .pipe(takeUntil(this.destroy$))
            .subscribe(([dateFrom, daysDisplayedQty]) => {
                this.refreshDaysIndexes(dateFrom, daysDisplayedQty);
            });

        const timeSlotCodes: ('AM'|'PM'|'EV')[] = ['AM', 'PM', 'EV'];
        combineLatest([this.calendar.activityMap$, this.calendar.productModelsIdsToDisplay$])
            .pipe(takeUntil(this.destroy$))
            .subscribe(([activityMap, productModelsIdsToDisplay]) => {
                const unassignedActivityMap: ActivityMap = {};
                if(activityMap['0']) {
                    unassignedActivityMap['0'] = JSON.parse(JSON.stringify(activityMap['0']));

                    for(let userId in unassignedActivityMap) {
                        const userActivityMap = activityMap[userId];
                        for(let dateIndex in userActivityMap) {
                            const dateActivityMap = userActivityMap[dateIndex];
                            for(let timeSlotCode of timeSlotCodes) {
                                if(dateActivityMap[timeSlotCode] !== undefined) {
                                    const activitiesToDisplay: ActivityMapActivity[] = [];
                                    for(let activity of dateActivityMap[timeSlotCode]) {
                                        // don't show activity if the product model is not selected
                                        if(productModelsIdsToDisplay.includes(activity.product_model_id.id)) {
                                            activitiesToDisplay.push(activity);
                                        }
                                    }

                                    unassignedActivityMap[userId][dateIndex][timeSlotCode] = activitiesToDisplay;
                                }
                            }
                        }
                    }
                }

                this.unassignedActivityMap = unassignedActivityMap;
            });
    }

    ngOnDestroy() {
        this.destroy$.next();
        this.destroy$.complete();
    }

    public refreshDaysIndexes(dateFrom: Date, days: number) {
        const daysIndexes: string[] = [];
        for(let i = 0; i < days; i++) {
            const date = new Date(dateFrom.getTime() + i * 24 * 60 * 60 * 1000);
            daysIndexes.push(date.toISOString().split('T')[0]);
        }

        this.daysIndexes = daysIndexes;
    }

    public toggleOpen() {
        if(this.opened) {
            this.close.emit();
        }
        else {
            this.open.emit();
        }
    }
}
