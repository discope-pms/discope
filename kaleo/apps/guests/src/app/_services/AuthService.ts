import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable, ReplaySubject } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { EnvService } from 'sb-shared-lib';
import { GuestUser } from '../../type';

@Injectable({
    providedIn: 'root'
})
export class AuthService {

    private pathParamState = new BehaviorSubject<any>({});

    public pathParam: Observable<string>;

    private _guestUser: GuestUser;

    private readonly observable: ReplaySubject<GuestUser>;

    constructor(
        private http: HttpClient,
        private env: EnvService
    ) {
        this.observable = new ReplaySubject<any>(1);
        this.pathParam = this.pathParamState.asObservable();
    }

    private set guestUser(guestUser: GuestUser) {
        this._guestUser = {
            ...this._guestUser,
            ...guestUser
        };

        // notify subscribers
        this.observable.next(this._guestUser);
    }

    public async signIn(nonce_token: string) {
        try {
            const environment:any = await this.env.getEnv();
            const data = await this.http.get<any>(environment.backend_url+'?do=sale_booking_guests_auth', {
                params: {
                    nonce: nonce_token
                }
            })
            .pipe(
                catchError((response: HttpErrorResponse, caught: Observable<any>) => {
                    throw response;
                })
            )
            .toPromise();

            // authentication will trigger router navigation within running controller
            await this.authenticate();
        }
        catch(response) {
            throw response;
        }
    }

    public async authenticate(): Promise<GuestUser> {
        let data;
        // attempt to log the user in based on cookie `guest_access_token`, if present (HTTP only)
        try {
            // make sure Environment has been fetched
            const environment = await this.env.getEnv();
            // #memo - /userinfo route can be adapted in back-end config (to steer to wanted controller)
            data = await this.http.get<any>(environment.backend_url + '?get=sale_booking_guests_userinfo').toPromise();
            this.guestUser = <GuestUser> data;
        }
        catch(httpErrorResponse:any) {
            let response: HttpErrorResponse = <HttpErrorResponse> httpErrorResponse;

            if(response.hasOwnProperty('status') && response.status > 299) {
                let body = response.error;
                let error_code = Object.keys(body.errors)[0];
                let error_id = body.errors[error_code];
                if(error_id == 'auth_expired_token') {
                    // redirect to signin page
                }
            }
            throw response;
        }

        return data;
    }

    public getObservable(): ReplaySubject<GuestUser> {
        return this.observable;
    }
}
