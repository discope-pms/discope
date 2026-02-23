import { Component, Inject, OnDestroy, OnInit } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { CalendarService } from '../../../../_services/calendar.service';
import { FormBuilder, FormGroup } from '@angular/forms';
import { combineLatest, Subject} from 'rxjs';
import { takeUntil } from 'rxjs/operators';
import { TranslationService } from '../../../../../../_services/translation.service';
import { Employee } from '../../../../../../../type';

export interface FilterDialogOpenData {
    calendar: CalendarService
}

@Component({
    selector: 'app-planning-employees-filters-dialog',
    templateUrl: 'planning-employees-filters-dialog.component.html',
    styleUrls: ['planning-employees-filters-dialog.component.scss']
})
export class PlanningEmployeesFiltersDialogComponent implements OnInit, OnDestroy {

    private readonly calendar: CalendarService;

    public userGroup: 'organizer'|'manager'|'animator'|null = null;
    public initialSelectedCategory: 'ALL'|'EQUI'|'ENV'|'SP' =  'ALL';

    public categories: { name: string, code: 'ALL'|'EQUI'|'ENV'|'SP' }[] = [];

    public productModels: { id: number, name: string }[] = [];
    public employees: { id: string, name: string }[] = [];

    public employeeList: Employee[] = [];

    public form: FormGroup;

    private destroy$ = new Subject<void>();

    constructor(
        private formBuilder: FormBuilder,
        private translateService: TranslationService,
        private dialogRef: MatDialogRef<PlanningEmployeesFiltersDialogComponent>,
        @Inject(MAT_DIALOG_DATA) public data: FilterDialogOpenData
    ) {
        this.calendar = data.calendar;
    }

    ngOnInit() {
        this.form = this.formBuilder.group({
            category: ['ALL'],
            employeeRole: ['ALL'],
            employee: ['ALL'],
            productModel: [0]
        });

        this.calendar.userGroup$
            .pipe(takeUntil(this.destroy$))
            .subscribe(userGroup => this.userGroup = userGroup);

        this.calendar.categoryList$
            .pipe(takeUntil(this.destroy$))
            .subscribe(categories => {
                this.categories = [
                    { code: 'ALL', name: this.translateService.translate('FILTERS_DIALOG_CATEGORY_ALL_LABEL') },
                    ...categories.map(er => { return { code: er.code, name: er.name }; })
                ];
            });

        this.calendar.selectedCategoryCode$
            .pipe(takeUntil(this.destroy$))
            .subscribe((selectedCategoryCode) => {
                this.form.get('category')?.setValue(selectedCategoryCode);
                this.initialSelectedCategory = selectedCategoryCode;
            })

        this.calendar.productModelList$
            .pipe(takeUntil(this.destroy$))
            .subscribe(productModels => {
                this.productModels = [
                    { id: 0, name: this.translateService.translate('FILTERS_DIALOG_PRODUCT_MODEL_ALL_LABEL') },
                    ...productModels
                        .sort((a, b) => a.name.localeCompare(b.name))
                        .map(pm => { return { id: pm.id, name: pm.name }; })
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
            .subscribe(employeeList => {
                this.employeeList = employeeList;
            });

        combineLatest([
            this.calendar.employeeRoleList$,
            this.calendar.employeeList$,
            this.calendar.userGroup$
        ])
            .pipe(takeUntil(this.destroy$))
            .subscribe(([employeeRoleList, employeeList, userGroup]) => {
                const roleEmployeesQtyMap = {
                    EQUI: employeeList.filter(e => e.role_id && e.role_id.code === 'EQUI').length,
                    ENV: employeeList.filter(e => e.role_id && e.role_id.code === 'ENV').length,
                    SP: employeeList.filter(e => e.role_id && e.role_id.code === 'SP').length,
                    CAMPS: employeeList.filter(e => e.role_id && e.role_id.code === 'CAMPS').length,
                };

                this.employees = [
                    { id: 'ALL', name: this.translateService.translate('FILTERS_DIALOG_EMPLOYEE_ALL_LABEL') },
                    ...employeeRoleList
                        .filter(() => userGroup === 'organizer')
                        .sort((a, b) => a.name.localeCompare(b.name))
                        .map(er => { return { id: er.code, name: er.name + ' (' + roleEmployeesQtyMap[er.code] + ')' }; }),
                    ...employeeList
                        .sort((a, b) => a.name.localeCompare(b.name))
                        .map(pm => { return { id: pm.id.toString(), name: pm.name }; })
                ];
            });

        combineLatest([
            this.calendar.employeeList$,
            this.calendar.employeeRoleList$,
            this.calendar.employeesIdsToDisplay$
        ])
            .pipe(takeUntil(this.destroy$))
            .subscribe(([employeeList, employeeRoleList, employeesIds]) => {
                if(employeesIds.length !== 1) {
                    let employee = 'ALL';
                    for(let employeeRole of employeeRoleList) {
                        const roleEmployeesIds = employeeList.filter(e => e.role_id && e.role_id.code === employeeRole.code).map(e => e.id);
                        if(JSON.stringify(roleEmployeesIds) === JSON.stringify(employeesIds)) {
                            employee = employeeRole.code;
                        }
                    }

                    this.form.get('employee')?.setValue(employee);
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
        const category = this.form.get('category')?.value;
        const employee = this.form.get('employee')?.value;
        const productModel = this.form.get('productModel')?.value;

        if(category !== this.initialSelectedCategory) {
            this.calendar.setSelectedCategoryCode(category);
        }

        if(employee === 'ALL') {
            this.calendar.resetEmployeesIdsToDisplay();
        }
        else if(['EQUI','ENV', 'SP', 'CAMPS'].includes(employee)) {
            const employeesIds = this.employeeList.filter(e => e.role_id && e.role_id.code === employee).map(e => e.id);
            this.calendar.setEmployeesIdsToDisplay(employeesIds);
        }
        else {
            this.calendar.setEmployeesIdsToDisplay([parseInt(employee)]);
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
