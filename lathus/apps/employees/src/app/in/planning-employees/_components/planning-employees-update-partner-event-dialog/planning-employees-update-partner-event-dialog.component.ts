import { Component, Inject, OnInit } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { TranslationService } from '../../../../_services/translation.service';
import { ApiService } from '../../../../_services/api.service';
import { CalendarService } from '../../_services/calendar.service';

export interface UpdatePartnerEventDialogData {
    calendar: CalendarService,
    id: number,
    name: string,
    eventType: 'camp_activity' | 'leave' | 'other' | 'rest' | 'time_off' | 'trainer' | 'training',
    description: string,
    eventDate: Date,
    timeSlotId: number,
    partnerId: number
}

@Component({
    selector: 'app-planning-employees-create-partner-event-dialog',
    templateUrl: 'planning-employees-update-partner-event-dialog.component.html',
    styleUrls: ['planning-employees-update-partner-event-dialog.component.scss']
})
export class PlanningEmployeesUpdatePartnerEventDialogComponent implements OnInit {

    private readonly calendar: CalendarService;
    private readonly id: number;
    private readonly name: string;
    private readonly eventType: 'camp_activity' | 'leave' | 'other' | 'rest' | 'time_off' | 'trainer' | 'training';
    private readonly description: string;
    private readonly eventDate: Date;
    private readonly timeSlotId: number;
    private readonly partnerId: number;

    public form: FormGroup;

    public eventTypeMap = {
        camp_activity: this.translateService.translate('ACTIVITY_DIALOG_EVENT_TYPE_CAMP_ACTIVITY'),
        leave: this.translateService.translate('ACTIVITY_DIALOG_EVENT_TYPE_LEAVE'),
        other: this.translateService.translate('ACTIVITY_DIALOG_EVENT_TYPE_OTHER'),
        rest: this.translateService.translate('ACTIVITY_DIALOG_EVENT_TYPE_REST'),
        time_off: this.translateService.translate('ACTIVITY_DIALOG_EVENT_TYPE_TIME_OFF'),
        trainer: this.translateService.translate('ACTIVITY_DIALOG_EVENT_TYPE_TRAINER'),
        training: this.translateService.translate('ACTIVITY_DIALOG_EVENT_TYPE_TRAINING'),
    };

    constructor(
        private api: ApiService,
        private translateService: TranslationService,
        private formBuilder: FormBuilder,
        private dialogRef: MatDialogRef<PlanningEmployeesUpdatePartnerEventDialogComponent>,
        @Inject(MAT_DIALOG_DATA) public data: UpdatePartnerEventDialogData
    ) {
        this.calendar = data.calendar;
        this.id = data.id;
        this.name = data.name;
        this.eventType = data.eventType;
        this.description = data.description;
        this.eventDate = data.eventDate;
        this.timeSlotId = data.timeSlotId;
        this.partnerId = data.partnerId;
    }

    ngOnInit() {
        this.form = this.formBuilder.group({
            'name': [this.name, Validators.required],
            'event_type': [this.eventType],
            'description': [this.description],
        });
    }

    public close() {
        this.dialogRef.close();
    }

    public closeAndSave() {
        const name: number = this.form.get('name')?.value;
        const eventType: number = this.form.get('event_type')?.value;
        const description: number = this.form.get('description')?.value;

        this.api.modelUpdate('sale\\booking\\PartnerEvent', this.id, {
            name: name,
            event_type: eventType,
            description: description,
            partner_id: this.partnerId,
            event_date: this.eventDate.toISOString(),
            time_slot_id: this.timeSlotId
        })
            .subscribe({
                next: () => {
                    this.dialogRef.close();
                },
                error: (error) => {
                    console.error('Error creating partner event:', error);
                }
            });
    }
}
