import { Component, Inject, OnInit } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { TranslationService } from '../../../../_services/translation.service';
import { ApiService } from '../../../../_services/api.service';
import { CalendarService } from '../../_services/calendar.service';

export interface CreatePartnerEventDialogData {
    calendar: CalendarService,
    eventDate: Date,
    timeSlotId: number,
    partnerId: number
}

@Component({
    selector: 'app-planning-employees-create-partner-event-dialog',
    templateUrl: 'planning-employees-create-partner-event-dialog.component.html',
    styleUrls: ['planning-employees-create-partner-event-dialog.component.scss']
})
export class PlanningEmployeesCreatePartnerEventDialogComponent implements OnInit {

    private readonly calendar: CalendarService;
    private readonly eventDate: Date;
    private readonly timeSlotId: number;
    private readonly partnerId: number;

    public form: FormGroup;

    public eventTypeMap = {
        camp_activity: this.translateService.translate('PARTNER_EVENT_TYPE_CAMP_ACTIVITY'),
        leave: this.translateService.translate('PARTNER_EVENT_TYPE_LEAVE'),
        other: this.translateService.translate('PARTNER_EVENT_TYPE_OTHER'),
        rest: this.translateService.translate('PARTNER_EVENT_TYPE_REST'),
        time_off: this.translateService.translate('PARTNER_EVENT_TYPE_TIME_OFF'),
        trainer: this.translateService.translate('PARTNER_EVENT_TYPE_TRAINER'),
        training: this.translateService.translate('PARTNER_EVENT_TYPE_TRAINING'),
    };

    constructor(
        private api: ApiService,
        private translateService: TranslationService,
        private formBuilder: FormBuilder,
        private dialogRef: MatDialogRef<PlanningEmployeesCreatePartnerEventDialogComponent>,
        @Inject(MAT_DIALOG_DATA) public data: CreatePartnerEventDialogData
    ) {
        this.calendar = data.calendar;
        this.eventDate = data.eventDate;
        this.timeSlotId = data.timeSlotId;
        this.partnerId = data.partnerId;
    }

    ngOnInit() {
        this.form = this.formBuilder.group({
            'name': ['', Validators.required],
            'event_type': ['other'],
            'description': [''],
        });
    }

    public close() {
        this.dialogRef.close();
    }

    public closeAndSave() {
        const name: number = this.form.get('name')?.value;
        const eventType: number = this.form.get('event_type')?.value;
        const description: number = this.form.get('description')?.value;

        this.api.modelCreate('sale\\booking\\PartnerEvent', {
            name: name,
            event_type: eventType,
            description: description,
            partner_id: this.partnerId,
            event_date: this.eventDate.toISOString(),
            time_slot_id: this.timeSlotId
        })
            .subscribe({
                next: () => {
                    this.calendar.refresh();
                    this.dialogRef.close();
                },
                error: (error) => {
                    console.error('Error creating partner event:', error);
                }
            });
    }
}
