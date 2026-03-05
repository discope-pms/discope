import { Component, Inject, OnInit } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { TranslationService } from '../../../../_services/translation.service';
import { ApiService } from '../../../../_services/api.service';
import { CalendarService } from '../../_services/calendar.service';

export interface CreatePartnerEventSetDialogData {
    calendar: CalendarService,
    eventDate: Date,
    partnerId: number
}

@Component({
    selector: 'app-planning-employees-create-partner-event-dialog',
    templateUrl: 'planning-employees-create-partner-event-set-dialog.component.html',
    styleUrls: ['planning-employees-create-partner-event-set-dialog.component.scss']
})
export class PlanningEmployeesCreatePartnerEventSetDialogComponent implements OnInit {

    private readonly calendar: CalendarService;
    private readonly eventDate: Date;
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
        private dialogRef: MatDialogRef<PlanningEmployeesCreatePartnerEventSetDialogComponent>,
        @Inject(MAT_DIALOG_DATA) public data: CreatePartnerEventSetDialogData
    ) {
        this.calendar = data.calendar;
        this.eventDate = data.eventDate;
        this.partnerId = data.partnerId;
    }

    ngOnInit() {
        const dateTo = new Date(this.eventDate);
        dateTo.setDate(dateTo.getDate() + 1);

        this.form = this.formBuilder.group({
            'name': ['', Validators.required],
            'event_type': ['other'],
            'description': [''],
            'date_from': [new Date(this.eventDate)],
            'date_to': [dateTo]
        });
    }

    public close() {
        this.dialogRef.close();
    }

    public closeAndSave() {
        const name: number = this.form.get('name')?.value;
        const eventType: number = this.form.get('event_type')?.value;
        const description: number = this.form.get('description')?.value;
        const dateFrom: Date = this.form.get('date_from')?.value;
        const dateTo: Date = this.form.get('date_to')?.value;

        this.api.modelCreate('sale\\booking\\PartnerEventSet', {
            name: name,
            event_type: eventType,
            description: description,
            partner_id: this.partnerId,
            event_date: this.eventDate.toISOString(),
            date_from: this.handleDate(dateFrom),
            date_to: this.handleDate(dateTo)
        })
            .subscribe({
                next: () => {
                    this.calendar.refresh();
                    this.dialogRef.close();
                },
                error: (error) => {
                    console.error('Error creating partner event set:', error);
                }
            });
    }

    private handleDate(date: Date) {
        const yyyy = date.getFullYear();
        const mm = String(date.getMonth() + 1).padStart(2, '0');
        const dd = String(date.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
    }
}
