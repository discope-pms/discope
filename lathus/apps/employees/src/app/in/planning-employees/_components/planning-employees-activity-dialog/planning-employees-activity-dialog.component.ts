import { Component, Inject, OnInit } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialog, MatDialogRef } from '@angular/material/dialog';
import { ActivityMapActivity } from '../../../../../type';
import { from } from 'rxjs';
import { EnvService } from 'sb-shared-lib';
import { FormBuilder, FormGroup } from '@angular/forms';
import { CalendarService } from '../../_services/calendar.service';
import { ApiService } from '../../../../_services/api.service';
import { TranslationService } from "../../../../_services/translation.service";
import { PlanningEmployeesUpdatePartnerEventDialogComponent, UpdatePartnerEventDialogData } from '../planning-employees-update-partner-event-dialog/planning-employees-update-partner-event-dialog.component';

export interface ActivityDialogData {
    calendar: CalendarService,
    activity: ActivityMapActivity
}

@Component({
    selector: 'app-planning-employees-activity-dialog',
    templateUrl: 'planning-employees-activity-dialog.component.html',
    styleUrls: ['planning-employees-activity-dialog.component.scss']
})
export class PlanningEmployeesActivityDialogComponent implements OnInit {

    private calendar: CalendarService;

    public activity: ActivityMapActivity;
    public dateFormated = '';

    public employees: { id: number, name: string }[] = [];

    public userGroup: 'animator' | 'manager' | 'organizer' = 'animator';

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
        private env: EnvService,
        private dialog: MatDialog,
        private translateService: TranslationService,
        private formBuilder: FormBuilder,
        private dialogRef: MatDialogRef<PlanningEmployeesActivityDialogComponent>,
        @Inject(MAT_DIALOG_DATA) public data: ActivityDialogData
    ) {
        this.calendar = data.calendar;
        this.activity = data.activity;
    }

    ngOnInit() {
        if(!this.activity.is_partner_event) {
            this.form = this.formBuilder.group({
                employee: [this.activity.employee_id ?? 0]
            });
        }
        else {
            this.form = this.formBuilder.group({});
        }

        this.form.get('employee')?.disable();

        from(this.env.getEnv()).subscribe((env: any) => {
            if(!this.activity?.is_partner_event) {
                this.dateFormated = this.formatDate(new Date(this.activity.activity_date.split('T')[0]), env.locale);
            }
            else {
                this.dateFormated = this.formatDate(new Date(this.activity.event_date.split('T')[0]), env.locale);
            }
        });

        this.calendar.employeeList$.subscribe(employeeList => {
            if(!this.activity?.is_partner_event) {
                this.employees = [
                    { id: 0, name: 'Non assigné' },
                    ...employeeList.filter(e => e.activity_product_models_ids.includes(this.activity.product_model_id.id))
                        .map(e => { return { id: e.id, name: e.name }; })
                ];
            }
        });

        this.calendar.userGroup$.subscribe(userGroup => {
            if(userGroup) {
                this.userGroup = userGroup;
            }
            if(userGroup !== 'animator') {
                this.form.get('employee')?.enable();
            }
        });
    }

    private formatDate(date: Date, locale: string): string {
        date = new Date(date.getTime());
        const formatter = new Intl.DateTimeFormat(locale.replace('_', '-'), { weekday: 'long', day: '2-digit', month: '2-digit' });
        return formatter.format(date);
    }

    public onModifyPartnerEvent() {
        const data: UpdatePartnerEventDialogData = {
            calendar: this.calendar,
            id: this.activity.id,
            name: this.activity.name,
            eventType: this.activity.event_type || 'leave',
            description: this.activity.description || '',
            eventDate: new Date(this.activity.event_date),
            timeSlotId: this.activity.time_slot_id,
            partnerId: 0
        };

        if(typeof this.activity.partner_id === 'object' && this.activity.partner_id?.id) {
            data.partnerId = this.activity.partner_id.id;
        }
        else if(typeof this.activity.partner_id === 'number') {
            data.partnerId = this.activity.partner_id;
        }

        const dialog = this.dialog.open(PlanningEmployeesUpdatePartnerEventDialogComponent, {
            width: '100vw',
            height: '100vh',
            maxWidth: '100vw',
            maxHeight: '100vh',
            panelClass: 'full-screen-dialog',
            data: data
        });

        dialog.afterClosed().subscribe(() => {
            this.api.modelCollect('sale\\booking\\PartnerEvent', ['name', 'event_type', 'description'], ['id', '=', this.activity.id])
                .subscribe({
                    next: ((partnerEvents: any[]) => {
                        if(partnerEvents.length === 1) {
                            this.activity.name = partnerEvents[0].name;
                            this.activity.event_type = partnerEvents[0].event_type;
                            this.activity.description = partnerEvents[0].description;
                        }
                    })
                });
        });
    }

    public onDeletePartnerEvent() {
        this.api.modelDelete('sale\\booking\\PartnerEvent', this.activity.id)
            .subscribe({
                next: () => {
                    this.calendar.refresh();
                    this.dialogRef.close();
                },
                error: (error) => {
                    console.error('Error updating activity:', error);
                }
            });
    }

    public close() {
        this.dialogRef.close();
    }

    public closeAndSave() {
        const employee: number = this.form.get('employee')?.value;

        this.api.modelUpdate('sale\\booking\\BookingActivity', this.activity.id, { employee_id: employee > 0 ? employee : null })
            .subscribe({
                next: () => {
                    this.calendar.refresh();
                    this.dialogRef.close();
                },
                error: (error) => {
                    console.error('Error updating activity:', error);
                }
            });
    }
}
