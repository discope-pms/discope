import { Injectable, OnDestroy } from '@angular/core';
import { BehaviorSubject, combineLatest, EMPTY, forkJoin, Observable, Subject } from 'rxjs';
import { ActivityMap, Category, Partner, ProductModel, TimeSlot } from '../../type';
import { takeUntil, switchMap, debounceTime, tap, catchError } from 'rxjs/operators';
import { ApiService } from './api.service';

@Injectable()
export class CalendarService implements OnDestroy {

    private dateFromSubject = new BehaviorSubject<Date>(new Date());
    public dateFrom$ = this.dateFromSubject.asObservable();

    private dateToSubject = new BehaviorSubject<Date>(new Date());
    public dateTo$ = this.dateToSubject.asObservable();

    private daysDisplayedQtySubject = new BehaviorSubject<number>(1);
    public daysDisplayedQty$ = this.daysDisplayedQtySubject.asObservable();

    private loadingSubject = new BehaviorSubject<boolean>(true);
    public loading$ = this.loadingSubject.asObservable();

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
        private api: ApiService
    ) {}

    public init() {
        // Listen to change of selected partners or product models to reload the activity map
        forkJoin([
            this.loadTimeSlotList(),
            this.loadCategoryList(),
            this.loadPartnerList(),
            this.loadProductModelList()
        ])
            .pipe(
                takeUntil(this.destroy$),
                switchMap(() => combineLatest([
                    this.dateFrom$,
                    this.daysDisplayedQty$,
                    this.selectedPartnersIds$,
                    this.selectedProductModelsIds$
                ])),
                debounceTime(300),
                tap(() => this.loadingSubject.next(true)),
                switchMap(([dateFrom, daysDisplayedQty, partnersIds, productModelsIds]) => {
                    if(!partnersIds.length || !productModelsIds.length) {
                        return EMPTY;
                    }

                    const dateTo: Date = new Date(dateFrom.getTime() + (daysDisplayedQty - 1) * 24 * 60 * 60 * 1000);
                    this.dateToSubject.next(dateTo);

                    return this.api.fetchActivityMap(dateFrom, dateTo, partnersIds, productModelsIds);
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

        // Listen to change of selected category to update selected product models
        this.selectedCategoryId$
            .pipe(takeUntil(this.destroy$))
            .subscribe(categoryId => this.handleChangeCategory(categoryId));
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
            this.selectedPartnersIdsSubject.value,
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

    ngOnDestroy() {
        this.destroy$.next();
        this.destroy$.complete();
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

    private loadPartnerList(): Observable<any> {
        return forkJoin({
            employees: this.api.fetchEmployees(),
            providers: this.api.fetchProviders()
        })
            .pipe(
                takeUntil(this.destroy$),
                tap(({ employees, providers }) => {
                    const filtered = employees.filter(e => e.activity_product_models_ids.length > 0);
                    const combined = [...filtered, ...providers];
                    if(combined.length > 0) {
                        this.partnerListSubject.next(combined);
                        this.selectedPartnersIdsSubject.next(combined.map(partner => partner.id));
                    }
                    else {
                        console.error('No partners defined!');
                    }
                }),
                catchError(error => {
                    console.error('Error fetching partners:', error);
                    return EMPTY;
                })
            );
    }

    private loadProductModelList(): Observable<any> {
        return this.api.fetchProductModels()
            .pipe(
                takeUntil(this.destroy$),
                tap(productModels => {
                    if(productModels.length > 0) {
                        this.productModelListSubject.next(productModels);
                        this.selectedProductModelsIdsSubject.next(productModels.map(p => p.id));
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
