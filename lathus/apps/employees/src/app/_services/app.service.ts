import { Injectable, OnDestroy } from '@angular/core';
import {BehaviorSubject, combineLatest, forkJoin, of, Subject} from 'rxjs';
import { ActivityMap, Category, Center, Partner, ProductModel, TimeSlot, TypeDisplay } from '../../type';
import { ApiService } from './api.service';
import { AuthService } from 'sb-shared-lib';
import { switchMap, takeUntil } from 'rxjs/operators';

@Injectable({
    providedIn: 'root'
})
export class AppService implements OnDestroy {

    private centerListSubject = new BehaviorSubject<Center[]>([]);
    public centerList$ = this.centerListSubject.asObservable();

    private centerSubject = new BehaviorSubject<Center|null>(null);
    public center$ = this.centerSubject.asObservable();

    private displayTypeSubject = new BehaviorSubject<TypeDisplay>('day');
    public displayType$ = this.displayTypeSubject.asObservable();

    private dateFromSubject = new BehaviorSubject<Date>(new Date());
    public dateFrom$ = this.dateFromSubject.asObservable();

    private dateToSubject = new BehaviorSubject<Date>(new Date());
    public dateTo$ = this.dateToSubject.asObservable();

    private timeSlotListSubject = new BehaviorSubject<TimeSlot[]>([]);
    public timeSlotList$ = this.timeSlotListSubject.asObservable();

    private categoryListSubject = new BehaviorSubject<Category[]>([]);
    public categoryList$ = this.categoryListSubject.asObservable();

    private selectedCategoryIdSubject = new BehaviorSubject<number|null>(null);
    public selectedCategoryId$ = this.selectedCategoryIdSubject.asObservable();

    private partnerListSubject = new BehaviorSubject<Partner[]>([]);
    public partnerList$ = this.partnerListSubject.asObservable();

    private selectedPartnersIdsSubject = new BehaviorSubject<number[]>([]);
    public selectedPartnersIds$ = this.selectedPartnersIdsSubject.asObservable();

    private productModelListSubject = new BehaviorSubject<ProductModel[]>([]);
    public productModelList$ = this.productModelListSubject.asObservable();

    private selectedProductModelsIdsSubject = new BehaviorSubject<number[]>([]);
    public selectedProductModelsIds$ = this.selectedProductModelsIdsSubject.asObservable();

    private activityMapSubject = new BehaviorSubject<ActivityMap>({});
    public activityMap$ = this.activityMapSubject.asObservable();

    private destroy$ = new Subject<void>();

    constructor(
        private api: ApiService,
        private auth: AuthService
    ) {
        // Load center when user authenticated
        this.auth.getObservable()
            .pipe(takeUntil(this.destroy$))
            .subscribe(user => {
                this.loadCenterList(user.centers_ids);
            });

        // Listen to change of selected partners or product models to reload the activity map
        combineLatest([
            this.dateFrom$,
            this.dateTo$,
            this.selectedPartnersIds$,
            this.selectedProductModelsIds$
        ])
            .pipe(
                takeUntil(this.destroy$),
                switchMap(([dateFrom, dateTo, partnersIds, productModelsIds]) => {
                    // Only fetch if all data is available
                    if(!partnersIds.length || !productModelsIds.length) {
                        return of({});
                    }

                    // Call API to fetch activity map
                    return this.api.fetchActivityMap(dateFrom, dateTo, partnersIds, productModelsIds);
                })
            )
            .subscribe({
                next: (activityMap) => {
                    console.log('Activity map loaded:', activityMap);
                    this.activityMapSubject.next({...activityMap});
                },
                error: (error) => {
                    console.error('Error fetching activity map:', error);
                }
            });

        // Listen to change of "date from" to modify "date to", if necessary
        this.dateFrom$
            .pipe(takeUntil(this.destroy$))
            .subscribe(dateFrom => this.handleChangeDateFrom(dateFrom));

        // Listen to change of selected category to update selected product models
        this.selectedCategoryId$
            .pipe(takeUntil(this.destroy$))
            .subscribe(categoryId => this.handleChangeCategory(categoryId));

        // Load needed data
        this.loadTimeSlotList();
        this.loadCategoryList();
        this.loadPartnerList();
        this.loadProductModelList();
    }

    ngOnDestroy() {
        this.destroy$.next();
        this.destroy$.complete();
    }

    /**
     * LOADERS
     */

    private loadCenterList(centerIds: number[]) {
        this.api.fetchCentersByIds(centerIds)
            .pipe(takeUntil(this.destroy$))
            .subscribe({
                next: (centers) => {
                    if(centers.length > 0) {
                        this.centerListSubject.next(centers);
                        this.centerSubject.next(centers[0]);
                    }
                    else {
                        console.error('No access to any center!');
                    }
                },
                error: (error) => {
                    console.error('Error fetching centers:', error);
                }
            });
    }

    private loadTimeSlotList() {
        this.api.fetchTimeSlots()
            .pipe(takeUntil(this.destroy$))
            .subscribe({
                next: (timeSlots) => {
                    if(timeSlots.length > 0) {
                        this.timeSlotListSubject.next(timeSlots);
                    }
                    else {
                        console.error('No time slots AM, PM and EV defined!');
                    }
                },
                error: (error) => {
                    console.error('Error fetching time slots:', error);
                }
            });
    }

    private loadCategoryList() {
        this.api.fetchActivityCategories()
            .pipe(takeUntil(this.destroy$))
            .subscribe({
                next: (categories) => {
                    if(categories.length > 0) {
                        this.categoryListSubject.next(categories);
                    }
                    else {
                        console.error('No categories defined!');
                    }
                },
                error: (error) => {
                    console.error('Error fetching categories:', error);
                }
            });
    }

    private loadPartnerList() {
        forkJoin({
            employees: this.api.fetchEmployees(),
            providers: this.api.fetchProviders()
        })
            .pipe(takeUntil(this.destroy$))
            .subscribe({
                next: ({ employees, providers }) => {
                    // ignore employees that aren't configured
                    employees = employees.filter(e => e.activity_product_models_ids.length > 0);

                    const combined = [...employees, ...providers]; // merge the two arrays
                    if(combined.length > 0) {
                        console.log('EMPLOYEES', employees);
                        this.partnerListSubject.next(combined);
                        this.selectedPartnersIdsSubject.next(combined.map(partner => partner.id));
                    }
                    else {
                        console.error('No partners defined!');
                    }
                },
                error: (error) => {
                    console.error('Error fetching partners:', error);
                }
            });
    }

    private loadProductModelList() {
        this.api.fetchProductModels()
            .pipe(takeUntil(this.destroy$))
            .subscribe({
                next: (productModels) => {
                    if(productModels.length > 0) {
                        this.productModelListSubject.next(productModels);
                        this.selectedProductModelsIdsSubject.next(productModels.map(productModel => productModel.id));
                    }
                    else {
                        console.error('No product models defined!');
                    }
                },
                error: (error) => {
                    console.error('Error fetching product models:', error);
                }
            });
    }

    /**
     * SETTERS
     */

    public setCenter(center: Center|null) {
        this.centerSubject.next(center);
    }

    public setDisplayType(displayType: TypeDisplay) {
        this.displayTypeSubject.next(displayType);
    }

    public setPreviousDate() {
        const previousDateFrom = new Date(this.dateFromSubject.value.getTime());
        previousDateFrom.setDate(this.dateFromSubject.value.getDate() - 1);

        this.dateFromSubject.next(previousDateFrom);
    }

    public setNextDate() {
        const nextDateFrom = new Date(this.dateFromSubject.value.getTime());
        nextDateFrom.setDate(this.dateFromSubject.value.getDate() + 1);

        this.dateFromSubject.next(nextDateFrom);
    }

    public setCategory(categoryId: number) {
        this.selectedCategoryIdSubject.next(categoryId);
    }

    public setSelectedPartnersIds(partnersIds: number[]) {
        this.selectedPartnersIdsSubject.next(partnersIds);
    }

    public setSelectedProductModelsIds(productModelsIds: number[]) {
        this.selectedProductModelsIdsSubject.next(productModelsIds);
    }

    /**
     * CHANGE HANDLERS
     */

    private handleChangeDateFrom(dateFrom: Date) {
        const dateTo = this.dateToSubject.value;
        if(this.displayTypeSubject.value === 'day' || dateTo.getTime() < dateFrom.getTime()) {
            this.dateToSubject.next(new Date(dateFrom.getTime()));
        }
    }

    private handleChangeCategory(categoryId: number|null) {
        if(!categoryId) {
            this.selectedProductModelsIdsSubject.next(this.productModelListSubject.value.map(productModel => productModel.id));
            return;
        }

        const productModelsIds: number[] = [];
        for(let productModel of this.productModelListSubject.value) {
            if(productModel.categories_ids.includes(categoryId)) {
                productModelsIds.push(productModel.id);
            }
        }
        this.selectedProductModelsIdsSubject.next(productModelsIds);
    }
}
