import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import {
    Booking,
    BookingContact,
    BookingInspection,
    Center,
    ConsumptionMeter,
    ConsumptionMeterReading,
    Product
} from '../../type';
import { EnvService } from 'sb-shared-lib';
import { from, Observable, of } from 'rxjs';
import { map, switchMap } from 'rxjs/operators';

interface CollectOptions {
    limit: number,
    order: string,
    sort: 'asc'|'desc'
}

type EntityNamespace = 'identity\\Center'
    | 'sale\\booking\\Booking'
    | 'sale\\booking\\ConsumptionMeter'
    | 'sale\\booking\\BookingInspection'
    | 'sale\\booking\\ConsumptionMeterReading'
    | 'sale\\catalog\\Product'
    | 'sale\\booking\\Contact';

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

    public fetchPendingBookings(centerId: number): Observable<Booking[]> {
        const now = new Date().toISOString().split('T')[0];

        const fields = {
            0: 'id',
            1: 'name',
            2: 'date_from',
            3: 'date_to',
            customer_identity_id: ['id', 'display_name']
        };

        const domain = [
            ['center_id', '=', centerId],
            ['status', '=', 'checkedin']
        ];

        return this.modelCollect<Booking>('sale\\booking\\Booking', fields, domain);
    }

    public fetchUpcomingBookings(centerId: number): Observable<Booking[]> {
        const now = new Date().toISOString().split('T')[0];

        const fields = {
            0: 'id',
            1: 'name',
            2: 'date_from',
            3: 'date_to',
            customer_identity_id: ['id', 'display_name']
        };

        const domain = [
            ['center_id', '=', centerId],
            ['date_from', '>=', now],
            ['status', 'in', ['confirmed', 'validated']]
        ];

        return this.modelCollect<Booking>(
            'sale\\booking\\Booking',
            fields,
            domain,
            { limit: 25, order: 'date_from', sort: 'asc' }
        );
    }

    public fetchCenterConsumptionMeters(centerId: number): Observable<ConsumptionMeter[]> {
        return this.modelCollect<ConsumptionMeter>(
            'sale\\booking\\ConsumptionMeter',
            ['id', 'name', 'center_id', 'type_meter', 'meter_number', 'index_value'],
            ['center_id', '=', centerId]
        );
    }

    public fetchCenterConsumptionMetersExcept(centerId: number, exceptIds: number[]): Observable<ConsumptionMeter[]> {
        return this.modelCollect<ConsumptionMeter>(
            'sale\\booking\\ConsumptionMeter',
            ['id', 'name', 'center_id', 'type_meter', 'meter_number', 'index_value'],
            [
                ['center_id', '=', centerId],
                ['id', 'not in', exceptIds]
            ]
        );
    }

    public fetchCheckedinBookingInspectionByBookingId(bookingId: number): Observable<BookingInspection|null> {
        return this.modelCollect<BookingInspection>(
            'sale\\booking\\BookingInspection',
            ['id', 'booking_id'],
            [
                ['booking_id', '=', bookingId],
                ['type_inspection', '=', 'checkedin']
            ],
            {
                limit: 1
            }
        ).pipe(
            map(items => items[0] ?? null)
        );
    }

    public fetchCheckedoutBookingInspectionByBookingId(bookingId: number): Observable<BookingInspection|null> {
        return this.modelCollect<BookingInspection>(
            'sale\\booking\\BookingInspection',
            ['id', 'booking_id'],
            [
                ['booking_id', '=', bookingId],
                ['type_inspection', '=', 'checkedout']
            ],
            {
                limit: 1
            }
        ).pipe(
            map(items => items[0] ?? null)
        );
    }

    public fetchLastConsumptionMeterReadingByMeterId(meterId: number): Observable<ConsumptionMeterReading|null> {
        return this.modelCollect<ConsumptionMeterReading>(
            'sale\\booking\\ConsumptionMeterReading',
            ['id', 'index_value'],
            [
                ['consumption_meter_id', '=', meterId]
            ],
            {
                limit: 1,
                order: 'created',
                sort: 'desc'
            }
        ).pipe(
            map(items => items[0] ?? null)
        );
    }


    public fetchConsumptionMeterReadingsByBookingInspectionId(bookingInspectionId: number): Observable<ConsumptionMeterReading[]> {
        const fields = {
            0: 'id',
            1: 'booking_inspection_id',
            2: 'index_value',
            3: 'unit_price',
            'consumption_meter_id': ['id', 'type_meter', 'meter_unit', 'meter_number', 'product_id']
        };

        return this.modelCollect<ConsumptionMeterReading>(
            'sale\\booking\\ConsumptionMeterReading',
            fields,
            ['booking_inspection_id', '=', bookingInspectionId]
        );
    }

    public fetchDoneConsumptionMeterIdsForBookingInspection(bookingInspectionId: number): Observable<number[]> {
        return this.modelCollect<ConsumptionMeterReading>(
            'sale\\booking\\ConsumptionMeterReading',
            ['id', 'consumption_meter_id'],
            ['booking_inspection_id', '=', bookingInspectionId]
        ).pipe(
            map(consumptionMeterReadings => consumptionMeterReadings.map(reading => reading.consumption_meter_id as number))
        );
    }

    public fetchProductByName(name: string): Observable<Product[]> {
        if(name.length === 0) {
            return of([]);
        }

        return this.modelCollect<ConsumptionMeterReading>(
            'sale\\catalog\\Product',
            ['id', 'name'],
            [['name', 'ilike', `%${name}%`], ['can_sell', '=', true]],
            { limit: 15 }
        );
    }

    public fetchBookingContactsByBookingId(bookingId: number): Observable<BookingContact[]> {
        const fields = {
            0: 'id',
            1: 'name',
            2: 'type',
            partner_identity_id: ['id', 'email']
        };

        return this.modelCollect<BookingContact>(
            'sale\\booking\\Contact',
            fields,
            ['booking_id', '=', bookingId]
        );
    }

    public modelGet<T>(entity: EntityNamespace, id: number, fields: any): Observable<T|null> {
        return this.modelCollect<T>(entity, fields, ['id', '=', id], { limit: 1 }).pipe(
            map(items => items[0] ?? null)
        );
    }

    public fetchConsumptionMeter(id: number): Observable<ConsumptionMeter|null> {
        return this.modelGet<ConsumptionMeter>(
            'sale\\booking\\ConsumptionMeter',
            id,
            ['id', 'name', 'center_id', 'type_meter', 'index_value', 'coefficient', 'meter_number', 'has_ean', 'meter_ean', 'meter_unit', 'product_id']
        );
    }

    public fetchBookingInspection(id: number): Observable<BookingInspection|null> {
        const fields = {
            0: 'id',
            1: 'name',
            2: 'date_inspection',
            3: 'type_inspection',
            4: 'status',
            booking_id: {
                0: 'id',
                1: 'name',
                2: 'date_from',
                3: 'date_to',
                customer_identity_id: ['id', 'display_name'],
                center_id: ['id', 'name']
            }
        };

        return this.modelGet<BookingInspection>('sale\\booking\\BookingInspection', id, fields).pipe(
            map(bookingInspection => this.adaptBookingInspection(bookingInspection))
        );
    }

    /**
     * Adapt booking_id of booking inspection, because dates of sub objects are unix timestamp instead of ISO date string
     *
     * @param inspection
     * @private
     */
    private adaptBookingInspection(inspection: BookingInspection|null): BookingInspection|null {
        if(!inspection || !inspection.booking_id || typeof inspection.booking_id === 'number') return inspection;

        if(typeof inspection.booking_id.date_from === 'number') {
            inspection.booking_id.date_from = this.unixTimestampToISO(inspection.booking_id.date_from);
        }
        if(typeof inspection.booking_id.date_to === 'number') {
            inspection.booking_id.date_to = this.unixTimestampToISO(inspection.booking_id.date_to);
        }

        return inspection;
    }

    public fetchProduct(id: number): Observable<Product|null> {
        return this.modelGet<ConsumptionMeter>('sale\\catalog\\Product', id, ['id', 'name']);
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

    public createConsumptionMeter(data: Partial<ConsumptionMeter>): Observable<ConsumptionMeter> {
        return this.modelCreate<ConsumptionMeter>('sale\\booking\\ConsumptionMeter', data);
    }

    public createBookingInspection(data: Partial<BookingInspection>): Observable<BookingInspection> {
        return this.modelCreate<BookingInspection>('sale\\booking\\BookingInspection', data);
    }

    public createMeterReading(data: Partial<ConsumptionMeterReading>): Observable<ConsumptionMeterReading> {
        return this.modelCreate<ConsumptionMeterReading>('sale\\booking\\ConsumptionMeterReading', data);
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

    public updateConsumptionMeter(id: number, data: Partial<ConsumptionMeter>): Observable<ConsumptionMeter> {
        return this.modelUpdate('sale\\booking\\ConsumptionMeter', id, data);
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

    public deleteConsumptionMeterReading(id: number): Observable<any> {
        return this.modelDelete('sale\\booking\\ConsumptionMeterReading', id);
    }

    public doBookingInspectionSubmit(id: number, emails: string[]): Observable<any> {
        const body = {
            id: id,
            emails: emails
        };

        return this.getBackendUrl().pipe(
            switchMap((backend_url: string) => {
                return this.http.post<any>(`${backend_url}?do=sale_booking_inspection_submit`, body);
            })
        );
    }

    public getUpcomingBookingsPdf(centerId: number, dateTo: string): Observable<any> {
        const nowUnixTimestamp = Math.floor(Date.now() / 1000);
        const dateToUnixTimestamp = Math.floor(new Date(dateTo.split('T')[0]).getTime() / 1000);

        const options = {
            headers: this.headers,
            params: {
                params: JSON.stringify({
                    center_id: centerId,
                    date_from: nowUnixTimestamp,
                    date_to: dateToUnixTimestamp
                })
            },
            responseType: 'blob' as 'json'
        };

        return this.getBackendUrl().pipe(
            switchMap((backend_url: string) => {
                return this.http.get(`${backend_url}?get=sale_booking_print-arrivals`, options);
            })
        );
    }
}
