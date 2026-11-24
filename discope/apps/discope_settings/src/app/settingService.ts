import { Injectable } from '@angular/core';
import { MatSnackBar } from '@angular/material/snack-bar';
import { Observable, Subject } from 'rxjs';
import { ApiService, EnvService } from 'sb-shared-lib';

@Injectable({
    providedIn: 'root'
})

export class SettingService {

    // booking object for conditionning API calls
    public queue: any[] = [];

    constructor(
        private api: ApiService,
        private env: EnvService,
        private snackBar: MatSnackBar) { }

    public toQueue(idSetting: number, fieldsSetting: any): Observable<string> {

        let subject = new Subject<string>();
        //adding the elements to the queue
        this.queue.push({ id: idSetting, fields: fieldsSetting });
        let snackBarRef = this.snackBar.open('Changes saved', 'Undo', {
            duration: 3000,
            verticalPosition: 'bottom',
            horizontalPosition: 'start',
        });

        snackBarRef.onAction().subscribe( () => {
            // remove from the queue and send the old value back
            this.queue.shift();
            // send 'undo' to understand that it doesn't come from dismissed
            subject.next('undo');
        })

        snackBarRef.afterDismissed().subscribe(() => {

            // if didn't do anything, sends the new value back
            if(this.queue.length > 0) {
                const setting = this.queue[0];
                this.env.getEnv().then((environment: any) => {
                    this.api.call('?do=core_setting_SettingValue_update', {
                        id: setting.id,
                        value: setting.fields.newValue,
                        lang: environment.lang
                    });
                });
                subject.next(fieldsSetting.newValue);
            }
            this.queue.shift();
        });

        return subject.asObservable();
    }
}
