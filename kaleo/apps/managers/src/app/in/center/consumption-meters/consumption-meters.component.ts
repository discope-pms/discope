import { Component, OnInit } from '@angular/core';
import { ApiService } from '../../../_services/api.service';
import { AppService } from '../../../_services/app.service';
import { Center, ConsumptionMeter } from '../../../../type';
import { Router } from '@angular/router';
import { Subject } from 'rxjs';
import { finalize, takeUntil } from 'rxjs/operators';

@Component({
    selector: 'app-consumption-meters',
    templateUrl: 'consumption-meters.component.html',
    styleUrls: ['consumption-meters.component.scss']
})
export class ConsumptionMetersComponent implements OnInit  {

    public consumptionMeterList: ConsumptionMeter[] = [];

    private selectedCenter: Center|null = null;

    public loading: boolean = true;

    private destroy$ = new Subject<void>();

    constructor(
        private api: ApiService,
        private app: AppService,
        private router: Router
    ) {}

    public ngOnInit() {
        this.app.center$.pipe(takeUntil(this.destroy$)).subscribe((center) => {
            this.selectedCenter = center;

            if(!center) return;

            this.loading = true;
            this.api.fetchCenterConsumptionMeters(center.id)
                .pipe(takeUntil(this.destroy$))
                .pipe(finalize(() => {
                    this.loading = false;
                }))
                .subscribe({
                    next: (data) => {
                        this.consumptionMeterList = data;
                    },
                    error: (error) => {
                        console.error('Error fetching center consumption meters:', error);
                    }
                });
        });
    }

    public ngOnDestroy() {
        this.destroy$.next();
        this.destroy$.complete();
    }

    public goToNew() {
        this.router.navigate([`/center/${this.selectedCenter?.id}/consumption-meter/new`]);
    }

    public goToEdit(id: number) {
        this.router.navigate([`/center/${this.selectedCenter?.id}/consumption-meter/${id}`]);
    }
}
