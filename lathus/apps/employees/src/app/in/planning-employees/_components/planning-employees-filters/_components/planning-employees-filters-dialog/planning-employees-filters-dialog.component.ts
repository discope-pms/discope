import {Component, Inject, OnDestroy, OnInit} from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { CalendarService } from '../../../../_services/calendar.service';
import { FormBuilder, FormGroup } from '@angular/forms';
import {Subject} from "rxjs";
import {takeUntil} from "rxjs/operators";

export interface FilterDialogOpenData {
    calendar: CalendarService
}

@Component({
    selector: 'app-planning-employees-filters-dialog',
    templateUrl: 'planning-employees-filters-dialog.component.html',
    styleUrls: ['planning-employees-filters-dialog.component.scss']
})
export class PlanningEmployeesFiltersDialogComponent implements OnInit, OnDestroy {

    private calendar: CalendarService;

    public userGroup: 'organizer'|'manager'|'animator'|null = null;
    public initialSelectedEmployeeRole: 'ALL'|'EQUI'|'ENV'|'SP' =  'ALL';

    public employeeRoles: { name: string, code: 'ALL'|'EQUI'|'ENV'|'SP' }[] = [];

    public productModels: { id: number, name: string }[] = [];
    public employees: { id: number, name: string }[] = [];

    public form: FormGroup;

    private destroy$ = new Subject<void>();

    constructor(
        private formBuilder: FormBuilder,
        private dialogRef: MatDialogRef<PlanningEmployeesFiltersDialogComponent>,
        @Inject(MAT_DIALOG_DATA) public data: FilterDialogOpenData
    ) {
        this.calendar = data.calendar;
    }

    ngOnInit() {
        this.form = this.formBuilder.group({
            employeeRole: ['ALL'],
            employee: [0],
            productModel: [0]
        });

        this.calendar.userGroup$
            .pipe(takeUntil(this.destroy$))
            .subscribe(userGroup => this.userGroup = userGroup);

        this.calendar.employeeRoleList$
            .pipe(takeUntil(this.destroy$))
            .subscribe(employeeRoles => {
                this.employeeRoles = [
                    { code: 'ALL', name: 'Tous' },
                    ...employeeRoles.map(er => {
                        return { code: er.code, name: er.name }
                    })
                ];
            });

        this.calendar.selectedEmployeeRoleCode$
            .pipe(takeUntil(this.destroy$))
            .subscribe((selectedEmployeeRoleCode) => {
                this.form.get('employeeRole')?.setValue(selectedEmployeeRoleCode);
                this.initialSelectedEmployeeRole = selectedEmployeeRoleCode;
            });

        this.calendar.productModelList$
            .pipe(takeUntil(this.destroy$))
            .subscribe(productModels => {
                this.productModels = [
                    { id: 0, name: 'Toutes' },
                    ...productModels
                        .sort((a, b) => a.name.localeCompare(b.name))
                        .map(pm => {return { id: pm.id, name: pm.name } })
                ];
            });

        this.calendar.productModelsIdsToDisplay$
            .pipe(takeUntil(this.destroy$))
            .subscribe((productModelsIds) => {
                if(productModelsIds.length !== 1) {
                    this.form.get('productModel')?.setValue(0);
                }
                else {
                    this.form.get('productModel')?.setValue(productModelsIds[0]);
                }
            });

        this.calendar.employeeList$
            .pipe(takeUntil(this.destroy$))
            .subscribe(employees => {
                this.employees = [
                    { id: 0, name: 'Tous' },
                    ...employees
                        .sort((a, b) => a.name.localeCompare(b.name))
                        .map(pm => {return { id: pm.id, name: pm.name } })
                ];
            });

        this.calendar.employeesIdsToDisplay$
            .pipe(takeUntil(this.destroy$))
            .subscribe((employeesIds) => {
                if(employeesIds.length !== 1) {
                    this.form.get('employee')?.setValue(0);
                }
                else {
                    this.form.get('employee')?.setValue(employeesIds[0]);
                }
            });
    }

    ngOnDestroy() {
        this.destroy$.next();
        this.destroy$.complete();
    }

    public closeAndSave() {
        const employeeRole = this.form.get('employeeRole')?.value;
        const employee = this.form.get('employee')?.value;
        const productModel = this.form.get('productModel')?.value;

        if(employeeRole !== this.initialSelectedEmployeeRole) {
            this.calendar.selectSelectedEmployeeRole(employeeRole);
        }

        if(employee !== 0) {
            this.calendar.setEmployeesIdsToDisplay([employee]);
        }
        else {
            this.calendar.resetEmployeesIdsToDisplay();
        }

        if(productModel !== 0) {
            this.calendar.setProductModelsIdsToDisplay([productModel]);
        }
        else {
            this.calendar.resetProductModelsIdsToDisplay();
        }

        this.dialogRef.close();
    }

    public close() {
        this.dialogRef.close();
    }
}
