import {Component, OnDestroy, OnInit} from '@angular/core';
import { AuthService } from 'sb-shared-lib';
import { Router } from '@angular/router';
import { takeUntil } from 'rxjs/operators';
import { Subject } from 'rxjs';
import { AppService } from '../../_services/app.service';
import { TranslationService } from '../../_services/translation.service';

@Component({
    selector: 'app-user-settings',
    templateUrl: 'user-settings.component.html',
    styleUrls: ['user-settings.component.scss']
})
export class UserSettingsComponent implements OnInit, OnDestroy {

    public name = '';
    public login = '';
    public planningGroup = '';
    public planningRole = '';

    private destroy$ = new Subject<void>();

    constructor(
        private app: AppService,
        private auth: AuthService,
        private router: Router,
        private translationService: TranslationService
    ) {
    }

    ngOnInit() {
        this.app.user$
            .pipe(takeUntil(this.destroy$))
            .subscribe(user => {
                if(!user) {
                    this.name = '';
                    this.login = '';
                    return;
                }

                this.name = user.name;
                this.login = user.login;

                if(user.groups.includes('planning.employees.organizer')) {
                    this.planningGroup = this.translationService.translate('USER_SETTINGS_GROUP_ORGANIZER');
                }
                else if(user.groups.includes('planning.employees.manager')) {
                    this.planningGroup = this.translationService.translate('USER_SETTINGS_GROUP_MANAGER');
                }
                else if(user.groups.includes('planning.employees.animator')) {
                    this.planningGroup = this.translationService.translate('USER_SETTINGS_GROUP_ANIMATOR');
                }
                else {
                    this.planningGroup = this.translationService.translate('USER_SETTINGS_GROUP_NONE');
                }
            });

        this.app.employee$
            .pipe(takeUntil(this.destroy$))
            .subscribe(employee => {
                if(!employee.role_id) {
                    this.planningRole = this.translationService.translate('USER_SETTINGS_ROLE_NONE');
                }
                else {
                    this.planningRole = employee.role_id.name;
                }
            });
    }

    ngOnDestroy() {
        this.destroy$.next();
        this.destroy$.complete();
    }

    async signOut() {
        this.auth.signOut();
        this.router.navigate(['/auth/sign-in']);
    }
}
