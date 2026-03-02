import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { EnvService } from 'sb-shared-lib';
import { Booking, GuestListItem } from '../../type';

@Injectable({
  providedIn: 'root'
})

export class ApiService {

    private headers: HttpHeaders;

    constructor(
            private http: HttpClient,
            private env: EnvService) {
        this.headers = new HttpHeaders();
        this.headers
            .set('Cache-Control', 'no-cache')
            .set('Pragma', 'no-cache');
    }

    /**
     *  Sends a direct POST request to the backend without using ReST API URL
     */
    public getBooking(body: any = {}): Promise<Booking> {
        return new Promise<any>( async (resolve, reject) => {
            try {
                const environment:any = await this.env.getEnv();
                // make sure not to double the trailing slash
                const response:any = await this.http.get<any>(environment.backend_url+'?get=sale_booking_guests_booking', {headers: this.headers, params: body}).toPromise();
                resolve(response);
            }
            catch(error) {
                reject(error);
            }
        });
    }

    public requestAccess(booking_id: number, email_address: string) {
        return new Promise<any>( async (resolve, reject) => {
            try {
                const environment:any = await this.env.getEnv();
                const response:any = await this.http.put<any>(environment.backend_url+'?do=sale_booking_guests_list_request-access', {
                        booking_id: booking_id,
                        email: email_address
                    })
                    .toPromise();
                resolve(response);
            }
            catch(error) {
                reject(error);
            }
        });
    }

    public submit(guest_list_id: number) {
        return new Promise<any>( async (resolve, reject) => {
            try {
                const environment:any = await this.env.getEnv();
                const response:any = await this.http.put<any>(environment.backend_url+'?do=sale_booking_guests_list_submit', {
                        id: guest_list_id
                    })
                    .toPromise();
                resolve(response);
            }
            catch(error) {
                reject(error);
            }
        });
    }

    public createListItem(guest_list_id: number, booking_line_group_id: number, values: Partial<GuestListItem> = {}, lang: string = '') {
        return new Promise<any>( async (resolve, reject) => {
            try {
                const environment:any = await this.env.getEnv();
                const response:any = await this.http.put<any>(environment.backend_url+'?do=sale_booking_guests_listitem_create', {
                        guest_list_id: guest_list_id,
                        booking_line_group_id: booking_line_group_id,
                        fields: JSON.stringify(values),
                        lang: (lang.length)?lang:environment.lang
                    })
                    .toPromise();
                resolve(response);
            }
            catch(error) {
                reject(error);
            }
        });
    }

    public updateListItem(guest_list_item_id: number, values: Partial<GuestListItem>, force: boolean = false, lang: string = '') {
        return new Promise<any>( async (resolve, reject) => {
            try {
                const environment:any = await this.env.getEnv();
                const response:any = await this.http.patch<any>(environment.backend_url+'?do=sale_booking_guests_listitem_update', {
                        id: guest_list_item_id,
                        fields: JSON.stringify(values),
                        force: force,
                        lang: (lang.length)?lang:environment.lang
                    })
                    .toPromise();
                resolve(response);
            }
            catch(error) {
                reject(error);
            }
        });
    }

    public updateListItems(guest_list_items_ids: number[], values: Partial<GuestListItem>, force: boolean = false, lang: string = '') {
        return new Promise<any>( async (resolve, reject) => {
            try {
                const environment:any = await this.env.getEnv();
                const response:any = await this.http.patch<any>(environment.backend_url+'?do=sale_booking_guests_listitem_update', {
                    ids: guest_list_items_ids,
                    fields: JSON.stringify(values),
                    force: force,
                    lang: (lang.length)?lang:environment.lang
                })
                    .toPromise();
                resolve(response);
            }
            catch(error) {
                reject(error);
            }
        });
    }

    public deleteListItems(guest_list_item_ids: number[]) {
        return new Promise<any>( async (resolve, reject) => {
            try {
                const environment:any = await this.env.getEnv();
                const response:any = await this.http.delete<any>(environment.backend_url+'?do=sale_booking_guests_listitem_delete', {
                        body: {
                            ids: guest_list_item_ids
                        }
                    }).toPromise();
                resolve(response);
            }
            catch(error) {
                reject(error);
            }
        });
    }
}
