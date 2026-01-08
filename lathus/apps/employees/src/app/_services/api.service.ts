import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import {
    Center,
    TimeSlot,
    Category,
    Partner,
    Employee,
    Provider,
    ProductModel,
    ActivityMap
} from '../../type';
import { EnvService } from 'sb-shared-lib';
import { from, Observable } from 'rxjs';
import { map, switchMap } from 'rxjs/operators';

interface CollectOptions {
    limit: number,
    order: string,
    sort: 'asc'|'desc'
}

type EntityNamespace = 'identity\\Center'
    | 'sale\\booking\\TimeSlot'
    | 'sale\\catalog\\Category'
    | 'hr\\employee\\Employee'
    | 'sale\\provider\\Provider'
    | 'sale\\catalog\\ProductModel';

@Injectable({
    providedIn: 'root'
})
export class ApiService {

    private readonly headers: HttpHeaders;

    constructor(
        private http: HttpClient,
        private env: EnvService
    ) {
        this.headers = new HttpHeaders();
        this.headers
            .set('Cache-Control', 'no-cache')
            .set('Pragma', 'no-cache');
    }

    private unixTimestampToISO(timestamp: number) {
        const date = new Date(timestamp * 1000);
        return date.toISOString();
    }

    private getBackendUrl(): Observable<string> {
        return from(this.env.getEnv()).pipe(
            map((environment: any) => environment.backend_url)
        );
    }

    public modelCollect<T>(entity: EntityNamespace, fields: any, domain: any, collectOption: Partial<CollectOptions> = {}): Observable<T[]> {
        collectOption = {
            ...{
                limit: 100,
                order: 'id',
                sort: 'asc'
            },
            ...collectOption
        }

        const options = {
            headers: this.headers,
            params: {
                entity: entity,
                domain: JSON.stringify(domain),
                fields: JSON.stringify(fields),
                ...collectOption
            }
        };

        return this.getBackendUrl().pipe(
            switchMap((backend_url: string) => this.http.get<T[]>(`${backend_url}?get=model_collect`, options))
        );
    }

    public fetchCentersByIds(centerIds: number[]): Observable<Center[]> {
        return this.modelCollect<Center>(
            'identity\\Center',
            ['id', 'name'],
            ['id', 'in', centerIds],
            { limit: centerIds.length }
        );
    }

    public fetchTimeSlots(): Observable<TimeSlot[]> {
        return this.modelCollect<TimeSlot>(
            'sale\\booking\\TimeSlot',
            ['id', 'name', 'code', 'schedule_from', 'schedule_to'],
            ['code', 'in', ['AM', 'PM', 'EV']]
        );
    }

    public fetchActivityCategories(): Observable<Category[]> {
        const options = {
            headers: this.headers,
            params: {
                fields: JSON.stringify(['id', 'name', 'code']),
                order: 'name',
                sort: 'asc',
                start: 0,
                limit: 500
            }
        };

        return this.getBackendUrl().pipe(
            switchMap((backend_url: string) => this.http.get<Category[]>(`${backend_url}?get=sale_booking_activity_collect-categories`, options))
        );
    }

    public fetchEmployees(): Observable<Employee[]> {
        return this.modelCollect<Employee>(
            'hr\\employee\\Employee',
            ['id', 'name', 'relationship', 'is_active', 'activity_product_models_ids'],
            [['relationship', '=', 'employee'], ['is_active', '=', true]]
        );
    }

    public fetchProviders(): Observable<Provider[]> {
        return this.modelCollect<Provider>(
            'sale\\provider\\Provider',
            ['id', 'name', 'relationship', 'is_active'],
            ['relationship', '=', 'provider']
        );
    }

    public fetchProductModels(): Observable<ProductModel[]> {
        return this.modelCollect<ProductModel>(
            'sale\\catalog\\ProductModel',
            ['id', 'name'],
            [['can_sell', '=', true], ['is_activity', '=', true]]
        );
    }

    public fetchActivityMap(dateFrom: Date, dateTo: Date, partnersIds: number[], productModelsIds: number[]): Observable<ActivityMap> {
        // date_to isn't included
        dateTo.setDate(dateTo.getDate() + 1);

        const options = {
            headers: this.headers,
            params: {
                date_from: dateFrom.toISOString().split('T')[0],
                date_to: dateTo.toISOString().split('T')[0],
                partners_ids: JSON.stringify(partnersIds),
                product_model_ids: JSON.stringify(productModelsIds)
            }
        };

        return this.getBackendUrl().pipe(
            switchMap((backend_url: string) => this.http.get<ActivityMap>(`${backend_url}?get=sale_booking_activity_map`, options))
        );
    }

    public modelCreate<T>(entity: EntityNamespace, fields: any): Observable<T> {
        const body = {
            entity: entity,
            fields: fields
        };

        return this.getBackendUrl().pipe(
            switchMap((backend_url: string) => {
                return this.http.post<T>(`${backend_url}?do=model_create`, body);
            })
        );
    }

    public modelUpdate<T>(entity: EntityNamespace, id: number, fields: any): Observable<T> {
        const body = {
            entity: entity,
            id: id,
            fields: fields
        };

        return this.getBackendUrl().pipe(
            switchMap((backend_url: string) => {
                return this.http.post<T>(`${backend_url}?do=model_update`, body);
            })
        );
    }

    public modelDelete(entity: EntityNamespace, id: number): Observable<any> {
        const body = {
            entity: entity,
            id: id
        };

        return this.getBackendUrl().pipe(
            switchMap((backend_url: string) => {
                return this.http.post(`${backend_url}?do=model_delete`, body);
            })
        );
    }
}
