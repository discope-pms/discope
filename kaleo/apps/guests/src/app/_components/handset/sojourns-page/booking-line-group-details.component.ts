import { Component, EventEmitter, Input, OnChanges, OnInit, Output, SimpleChanges } from '@angular/core';

@Component({
    selector: 'booking-line-group-details',
    templateUrl: './booking-line-group-details.component.html',
    styleUrls: ['./booking-line-group-details.component.scss']
})
export class BookingLineGroupDetailsComponent implements OnInit, OnChanges {

    @Input() dateFrom: string;
    @Input() dateTo: string;
    @Input() completionPercentage: number = 0;

    @Output() openGroup = new EventEmitter();

    public completionPercentageRounded: number = 0;

    constructor() {
    }

    public ngOnInit() {
    }

    public ngOnChanges(changes: SimpleChanges) {
        if(changes.completionPercentage) {
            this.completionPercentageRounded = Math.round(this.completionPercentage);
        }
    }
}
