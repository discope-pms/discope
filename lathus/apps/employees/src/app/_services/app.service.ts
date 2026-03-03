import { Injectable, OnDestroy } from '@angular/core';
import { BehaviorSubject, Subject } from 'rxjs';
import { Center } from '../../type';
import { ApiService } from './api.service';
import { AuthService } from 'sb-shared-lib';
import { takeUntil } from 'rxjs/operators';

@Injectable({
    providedIn: 'root'
})
export class AppService implements OnDestroy {

    private userSubject = new BehaviorSubject<any>(null);
    public user$ = this.userSubject.asObservable();

    private employeeSubject = new BehaviorSubject<any>(null);
    public employee$ = this.employeeSubject.asObservable();

    private centerListSubject = new BehaviorSubject<Center[]>([]);
    public centerList$ = this.centerListSubject.asObservable();

    private centerSubject = new BehaviorSubject<Center|null>(null);
    public center$ = this.centerSubject.asObservable();

    private destroy$ = new Subject<void>();

    constructor(
        private api: ApiService,
        private auth: AuthService
    ) {
        // Load user, employee and center when user authenticated
        this.auth.getObservable()
            .pipe(takeUntil(this.destroy$))
            .subscribe(user => {
                this.userSubject.next(user);
                if(user) {
                    this.loadCenterList(user.centers_ids);
                    this.loadEmployee(user.identity_id.id);
                }
                else {
                    this.centerSubject.next(null);
                    this.centerListSubject.next([]);
                }
            });
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

    private loadEmployee(identityId: number) {
        this.api.fetchEmployeeByIdentityId(identityId)
            .pipe(takeUntil(this.destroy$))
            .subscribe({
                next: (employees) => {
                    if(employees.length > 0) {
                        this.employeeSubject.next(employees[0]);
                    }
                    else {
                        console.error('Employee not found!');
                    }
                },
                error: (error) => {
                    console.error('Error fetching employee by identity id:', error);
                }
            });
    }

    /**
     * SETTERS
     */

    public setCenter(center: Center|null) {
        this.centerSubject.next(center);
    }
}
