import { Injectable, OnDestroy } from '@angular/core';
import { BehaviorSubject, combineLatest, EMPTY, forkJoin, Observable, Subject } from 'rxjs';
import {ActivityMap, Category, Employee, EmployeeRole, ProductModel, TimeSlot} from '../../../../type';
import {takeUntil, switchMap, debounceTime, tap, catchError, filter} from 'rxjs/operators';
import { ApiService } from '../../../_services/api.service';
import { AuthService } from 'sb-shared-lib';

@Injectable()
export class CalendarService implements OnDestroy {

    /**
     * User
     */

    private user: any = null;

    private userGroupSubject = new BehaviorSubject<'animator'|'manager'|'organizer'|null>(null);
    public userGroup$ = this.userGroupSubject.asObservable();

    private employeeRoleSubject = new BehaviorSubject<'EQUI'|'ENV'|'SP'|null>(null);
    public employeeRole$ = this.employeeRoleSubject.asObservable();

    /**
     * Interface data
     */

    private timeSlotListSubject = new BehaviorSubject<TimeSlot[]>([]);
    public timeSlotList$ = this.timeSlotListSubject.asObservable();

    private employeeRoleListSubject =  new BehaviorSubject<EmployeeRole[]>([]);
    public employeeRoleList$ = this.employeeRoleListSubject.asObservable();

    private categoryListSubject = new BehaviorSubject<Category[]>([]);
    public categoryList$ = this.categoryListSubject.asObservable();

    private employeeListSubject = new BehaviorSubject<Employee[]>([]);
    public employeeList$ = this.employeeListSubject.asObservable();

    private productModelListSubject = new BehaviorSubject<ProductModel[]>([]);
    public productModelList$ = this.productModelListSubject.asObservable();

    /**
     * Filters
     */

    private dateFromSubject = new BehaviorSubject<Date>(new Date());
    public dateFrom$ = this.dateFromSubject.asObservable();

    private dateToSubject = new BehaviorSubject<Date>(new Date());
    public dateTo$ = this.dateToSubject.asObservable();

    private daysDisplayedQtySubject = new BehaviorSubject<number>(1);
    public daysDisplayedQty$ = this.daysDisplayedQtySubject.asObservable();

    private selectedEmployeeRoleCodeSubject = new BehaviorSubject<'ALL'|'EQUI'|'ENV'|'SP'>('ALL');
    public selectedEmployeeRoleCode$ = this.selectedEmployeeRoleCodeSubject.asObservable();

    // Should only depend on the employee role
    private selectedEmployeesIdsSubject = new BehaviorSubject<number[]>([]);
    public selectedEmployeesIds$ = this.selectedEmployeesIdsSubject.asObservable();

    // Should only depend on the employee role
    private selectedProductModelsIdsSubject = new BehaviorSubject<number[]>([]);
    public selectedProductModelsIds$ = this.selectedProductModelsIdsSubject.asObservable();

    /**
     * Display filters
     */

    private employeesIdsToDisplaySubject = new BehaviorSubject<number[]>([]);
    public employeesIdsToDisplay$ = this.employeesIdsToDisplaySubject.asObservable();

    private productModelsIdsToDisplaySubject = new BehaviorSubject<number[]>([]);
    public productModelsIdsToDisplay$ = this.productModelsIdsToDisplaySubject.asObservable();

    /**
     * Result activity map + its loading flag
     */

    private activityMapSubject = new BehaviorSubject<ActivityMap>({});
    public activityMap$ = this.activityMapSubject.asObservable();

    private loadingSubject = new BehaviorSubject<boolean>(true);
    public loading$ = this.loadingSubject.asObservable();

    /**
     * Destroy
     */

    private destroy$ = new Subject<void>();

    constructor(
        private api: ApiService,
        private auth: AuthService
    ) {}

    ngOnDestroy() {
        this.destroy$.next();
        this.destroy$.complete();
    }

    public init() {
        // Listen to auth user to set user related data
        this.auth.getObservable()
            .pipe(takeUntil(this.destroy$))
            .subscribe((user) => {
               this.user = user;

                let role: 'animator'|'manager'|'organizer'|null = null;
                if(user) {
                    if(user.groups.includes('planning.employees.organizer')) {
                        role = 'organizer';
                    }
                    else if(user.groups.includes('planning.employees.manager')) {
                        role = 'manager';
                    }
                    else if(user.groups.includes('planning.employees.animator')) {
                        role = 'animator';
                    }
                }

                this.userGroupSubject.next(role);
            });

        // Load base data then listen to changes to reload the activity map
        forkJoin([
            this.loadTimeSlotList(),
            this.loadEmployeeRoleList(),
            this.loadCategoryList(),
            this.loadEmployeeList()
        ])
            .pipe(
                takeUntil(this.destroy$),
                switchMap(() => combineLatest([
                    this.userGroup$,
                    this.employeeRole$,
                    this.dateFrom$,
                    this.daysDisplayedQty$,
                    this.selectedEmployeesIds$,
                    this.selectedProductModelsIds$
                ])),
                debounceTime(300),
                tap(() => this.loadingSubject.next(true)),
                switchMap(([userGroup, employeeRole, dateFrom, daysDisplayedQty, employeesIds, productModelsIds]) => {
                    if(!userGroup || !employeeRole || !employeesIds.length || !productModelsIds.length) {
                        return EMPTY;
                    }

                    const dateTo: Date = new Date(dateFrom.getTime() + (daysDisplayedQty - 1) * 24 * 60 * 60 * 1000);
                    this.dateToSubject.next(dateTo);

                    return this.api.fetchActivityMap(dateFrom, dateTo, employeesIds, productModelsIds);
                })
            )
            .subscribe({
                next: (activityMap) => {
                    this.loadingSubject.next(false);
                    this.activityMapSubject.next({...activityMap});
                },
                error: (error) => {
                    this.loadingSubject.next(false);
                    console.error('Error fetching activity map:', error);
                }
            });

        // Listen to filter changes to modify the product models and employees to display
        combineLatest([
            this.userGroup$,
            this.employeeRole$,
            this.selectedEmployeeRoleCode$,
            this.employeeList$,
            this.categoryList$
        ])
            .pipe(
                takeUntil(this.destroy$),
                switchMap(([userGroup, employeeRole, selectedEmployeeRoleCode, employeeList, categoryList]) => {
                    if(!userGroup || !employeeRole || !employeeList.length || !categoryList.length) {
                        return EMPTY;
                    }

                    let productModelsIds: number[] = [];
                    let employeesIds: number[] = [];

                    if(userGroup === 'organizer') {
                        // filter product models
                        let allProductModelsIds: number[] = [];
                        for(let category of categoryList) {
                            if(selectedEmployeeRoleCode === 'ALL' || selectedEmployeeRoleCode === category.code) {
                                allProductModelsIds = [...allProductModelsIds, ...category.product_models_ids];
                            }
                        }
                        productModelsIds = allProductModelsIds;

                        // filter employees
                        if(selectedEmployeeRoleCode !== 'ALL') {
                            employeeList = employeeList.filter(e => e.role_id.code === selectedEmployeeRoleCode);
                        }
                        employeesIds = employeeList.map(e => e.id);
                    }
                    else {
                        // filter product models
                        const category = categoryList.find(c => c.code === employeeRole);
                        if(category) {
                            productModelsIds = category.product_models_ids;
                        }

                        // filter employees
                        employeesIds = employeeList.filter(e => e.role_id.code === employeeRole).map(e => e.id);
                    }

                    this.selectedProductModelsIdsSubject.next(productModelsIds);
                    this.selectedEmployeesIdsSubject.next(employeesIds);

                    return EMPTY;
                })
            )
            .subscribe();

        // Wait for user and product categories data to be loaded, then load the product models accordingly
        combineLatest([
            this.userGroup$,
            this.employeeRole$,
            this.categoryList$
        ])
            .pipe(
                takeUntil(this.destroy$),
                debounceTime(300),
                filter(([userGroup, employeeRole, categoryList]) => !!userGroup && !!employeeRole && !!categoryList.length)
            )
            .subscribe(([userGroup, employeeRole, categoryList]) => {
                if(userGroup === 'organizer') {
                    this.loadProductModelList().subscribe();
                } else {
                    const category = categoryList.find(c => c.code === employeeRole);
                    if(category) {
                        this.loadProductModelList({ ids: category.product_models_ids }).subscribe();
                    }
                }
            });
    }

    private loadTimeSlotList(): Observable<any> {
        return this.api.fetchTimeSlots()
            .pipe(
                takeUntil(this.destroy$),
                tap(timeSlots => {
                    if(timeSlots.length > 0) {
                        this.timeSlotListSubject.next(timeSlots);
                    }
                    else {
                        console.error('No time slots AM, PM and EV defined!');
                    }
                }),
                catchError(error => {
                    console.error('Error fetching time slots:', error);
                    return EMPTY;
                })
            );
    }

    private loadEmployeeRoleList(): Observable<any> {
        return this.api.fetchEmployeeRoles()
            .pipe(
                takeUntil(this.destroy$),
                tap(employeeRoles => {
                    if(employeeRoles.length > 0) {
                        this.employeeRoleListSubject.next(employeeRoles);
                    }
                    else {
                        console.error('No employee roles defined!');
                    }
                }),
                catchError(error => {
                    console.error('Error fetching employee roles:', error);
                    return EMPTY;
                })
            );
    }

    private loadCategoryList(): Observable<any> {
        return this.api.fetchActivityCategories()
            .pipe(
                takeUntil(this.destroy$),
                tap(categories => {
                    if(categories.length > 0) {
                        this.categoryListSubject.next(categories);
                    }
                    else {
                        console.error('No categories defined!');
                    }
                }),
                catchError(error => {
                    console.error('Error fetching categories:', error);
                    return EMPTY;
                })
            );
    }

    private loadEmployeeList(): Observable<any> {
        return this.api.fetchEmployees()
            .pipe(
                takeUntil(this.destroy$),
                tap(employees => {
                    if(employees.length > 0) {
                        employees = employees.filter(e => e.role_id);

                        this.employeeListSubject.next(employees);
                        this.employeesIdsToDisplaySubject.next(employees.map(e => e.id));

                        const employee = employees.find(e => e.partner_identity_id === this.user.identity_id.id);
                        if(employee) {
                            this.employeeRoleSubject.next(employee.role_id.code);
                        }
                    }
                    else {
                        console.error('No employees defined!');
                    }
                }),
                catchError(error => {
                    console.error('Error fetching employees:', error);
                    return EMPTY;
                })
            );
    }

    private loadProductModelList(filters: { ids?: number[] }  = {}): Observable<any> {
        return this.api.fetchProductModels(filters)
            .pipe(
                takeUntil(this.destroy$),
                tap(productModels => {
                    if(productModels.length > 0) {
                        this.productModelListSubject.next(productModels);
                        this.productModelsIdsToDisplaySubject.next(productModels.map(pm => pm.id));
                    }
                    else {
                        console.error('No product models defined!');
                    }
                }),
                catchError(error => {
                    console.error('Error fetching product models:', error);
                    return EMPTY;
                })
            );
    }

    /**
     * Refresh with minimum delay for the loading spinner to show correctly
     */
    public refresh() {
        const DELAY = 300;
        const startTime = Date.now();

        this.loadingSubject.next(true);

        this.api.fetchActivityMap(
            this.dateFromSubject.value,
            this.dateToSubject.value,
            this.selectedEmployeesIdsSubject.value,
            this.selectedProductModelsIdsSubject.value
        )
            .subscribe({
                next: (activityMap) => {
                    const elapsed = Date.now() - startTime;
                    const remaining = Math.max(0, DELAY - elapsed);

                    setTimeout(() => {
                        this.loadingSubject.next(false);
                        this.activityMapSubject.next({...activityMap});
                    }, remaining);
                },
                error: (error) => {
                    const elapsed = Date.now() - startTime;
                    const remaining = Math.max(0, DELAY - elapsed);

                    setTimeout(() => {
                        this.loadingSubject.next(false);
                        console.error('Error fetching activity map:', error);
                    }, remaining);
                }
            });
    }

    /**
     * SETTERS
     */

    public setPreviousDate() {
        this.loadingSubject.next(true);

        const previousDateFrom = new Date(this.dateFromSubject.value.getTime());
        previousDateFrom.setDate(this.dateFromSubject.value.getDate() - 1);

        this.dateFromSubject.next(previousDateFrom);
    }

    public setNextDate() {
        this.loadingSubject.next(true);

        const nextDateFrom = new Date(this.dateFromSubject.value.getTime());
        nextDateFrom.setDate(this.dateFromSubject.value.getDate() + 1);

        this.dateFromSubject.next(nextDateFrom);
    }

    public setDaysDisplayedQty(daysDisplayedQty: number) {
        if(daysDisplayedQty !== this.daysDisplayedQtySubject.value) {
            this.loadingSubject.next(true);
            this.daysDisplayedQtySubject.next(daysDisplayedQty);
        }
    }

    public selectSelectedEmployeeRole(employeeRoleCode: 'ALL'|'EQUI'|'ENV'|'SP') {
        this.selectedEmployeeRoleCodeSubject.next(employeeRoleCode);
    }

    public setEmployeesIdsToDisplay(employeesIds: number[]) {
        this.employeesIdsToDisplaySubject.next(employeesIds);
    }

    public resetEmployeesIdsToDisplay() {
        this.employeesIdsToDisplaySubject.next(this.selectedEmployeesIdsSubject.value);
    }

    public setProductModelsIdsToDisplay(productModelsIds: number[]) {
        this.productModelsIdsToDisplaySubject.next(productModelsIds);
    }

    public resetProductModelsIdsToDisplay() {
        this.productModelsIdsToDisplaySubject.next(this.selectedProductModelsIdsSubject.value);
    }
}
