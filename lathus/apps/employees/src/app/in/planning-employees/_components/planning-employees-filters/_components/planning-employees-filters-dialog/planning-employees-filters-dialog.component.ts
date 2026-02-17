import { Component, Inject, OnInit } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { CalendarService } from '../../../../_services/calendar.service';
import { FormBuilder, FormGroup } from '@angular/forms';

@Component({
    selector: 'app-planning-employees-filters-dialog',
    templateUrl: 'planning-employees-filters-dialog.component.html',
    styleUrls: ['planning-employees-filters-dialog.component.scss']
})
export class PlanningEmployeesFiltersDialogComponent implements OnInit {

    public userGroup: 'organizer'|'manager'|'animator'|null = null;

    public employeeRoles: { name: string, code: 'ALL'|'EQUI'|'ENV'|'SP' }[] = [];

    public form: FormGroup;

    constructor(
        private calendar: CalendarService,
        private formBuilder: FormBuilder,
        private dialogRef: MatDialogRef<PlanningEmployeesFiltersDialogComponent>,
        @Inject(MAT_DIALOG_DATA) public data: {}
    ) {}

    ngOnInit() {
        this.form = this.formBuilder.group({
            employeeRole: ['ALL']
        });

        this.calendar.userGroup$.subscribe(userGroup => this.userGroup = userGroup);

        this.calendar.employeeRoleList$.subscribe(employeeRoles => {
            this.employeeRoles = [
                { code: 'ALL', name: 'Tous' },
                ...employeeRoles.map(er => {
                    return { code: er.code, name: er.name }
                })
            ];
        });

        this.calendar.selectedEmployeeRoleCode$.subscribe((selectedEmployeeRoleCode) => {
            this.form.get('employeeRole')?.setValue(selectedEmployeeRoleCode);
        });
    }

    public closeAndSave() {
        const employeeRole = this.form.get('employeeRole')?.value;

        this.calendar.selectSelectedEmployeeRole(employeeRole);

        this.dialogRef.close();
    }

    public close() {
        this.dialogRef.close();
    }
}
