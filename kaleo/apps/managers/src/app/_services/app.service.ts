import { Injectable } from '@angular/core';
import { BehaviorSubject, Subject } from 'rxjs';
import { Center } from '../../type';
import { ApiService } from './api.service';
import { AuthService } from 'sb-shared-lib';
import { takeUntil } from 'rxjs/operators';

@Injectable({
    providedIn: 'root'
})
export class AppService {
    private centerSubject = new BehaviorSubject<Center|null>(null);
    public center$ = this.centerSubject.asObservable();

    private centerListSubject = new BehaviorSubject<Center[]>([]);
    public centerList$ = this.centerListSubject.asObservable();

    private destroy$ = new Subject<void>();

    constructor(
        private api: ApiService,
        private auth: AuthService
    ) {
        this.auth.getObservable()
            .pipe(takeUntil(this.destroy$))
            .subscribe(user => {
                this.loadCenterList(user.centers_ids);
            });
    }

    private loadCenterList(centerIds: number[]) {
        this.api.fetchCentersByIds(centerIds)
            .pipe(takeUntil(this.destroy$))
            .subscribe({
                next: (data) => {
                    if(data.length > 0) {
                        this.centerListSubject.next(data);
                        this.centerSubject.next(data[0]);
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

    public ngOnDestroy() {
        this.destroy$.next();
        this.destroy$.complete();
    }

    public setCenter(center: Center|null) {
        this.centerSubject.next(center);
    }
}
