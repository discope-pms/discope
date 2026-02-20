import { Component, Inject, OnInit } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { ActivityMapActivity } from '../../../../../type';
import { from } from 'rxjs';
import { EnvService } from 'sb-shared-lib';
import { FormBuilder, FormGroup } from '@angular/forms';
import { CalendarService } from '../../_services/calendar.service';
import { ApiService } from '../../../../_services/api.service';

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

    public form: FormGroup;

    constructor(
        private api: ApiService,
        private env: EnvService,
        private formBuilder: FormBuilder,
        private dialogRef: MatDialogRef<PlanningEmployeesActivityDialogComponent>,
        @Inject(MAT_DIALOG_DATA) public data: ActivityDialogData
    ) {
        this.calendar = data.calendar;
        this.activity = data.activity;
    }

    ngOnInit() {
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

        if(!this.activity.is_partner_event) {
            this.form = this.formBuilder.group({
                employee: [this.activity.employee_id ?? 0]
            });

            this.calendar.userGroup$.subscribe(userGroup => {
                if(userGroup === 'animator') {
                    this.form.get('employee')?.disable();
                }
            });
        }
        else {
            this.form = this.formBuilder.group({});
        }
    }

    private formatDate(date: Date, locale: string): string {
        date = new Date(date.getTime());
        const formatter = new Intl.DateTimeFormat(locale.replace('_', '-'), { weekday: 'long', day: '2-digit', month: '2-digit' });
        return formatter.format(date);
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
