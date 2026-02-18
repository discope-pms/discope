import {Component, Input} from '@angular/core';

@Component({
    selector: 'app-planning-employees-calendar-activity-card',
    templateUrl: 'planning-employees-calendar-activity-card.component.html',
    styleUrls: ['planning-employees-calendar-activity-card.component.scss']
})
export class PlanningEmployeesCalendarActivityCardComponent {

    @Input() activity: any;
    @Input() padding = '5px';

    constructor() {
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
