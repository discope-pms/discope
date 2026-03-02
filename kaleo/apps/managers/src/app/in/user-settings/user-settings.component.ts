import { Component, OnInit } from '@angular/core';
import { AuthService } from 'sb-shared-lib';
import { Router } from '@angular/router';
import { takeUntil } from 'rxjs/operators';
import { Subject } from 'rxjs';
import { AvailableLang, TranslationService } from '../../_services/translation.service';

interface User {
    name: string,
    login: string
}

@Component({
    selector: 'app-user-settings',
    templateUrl: 'user-settings.component.html',
    styleUrls: ['user-settings.component.scss']
})
export class UserSettingsComponent implements OnInit  {

    public user: User|null = null;

    public availableLangList: AvailableLang[] = [];

    public selectedLang: AvailableLang;

    private destroy$ = new Subject<void>();

    constructor(
        private auth: AuthService,
        private router: Router,
        private translation: TranslationService
    ) {
        this.availableLangList = this.translation.availableLangList;
        this.selectedLang = this.translation.getLang();
    }

    public ngOnInit() {
        this.auth.getObservable().pipe(takeUntil(this.destroy$)).subscribe((user: User) => {
            this.user = user;
        });
    }

    public ngOnDestroy() {
        this.destroy$.next();
        this.destroy$.complete();
    }

    public async signOut() {
        await this.auth.signOut();

        this.router.navigate(['/auth/sign-in']);
    }

    public changeLang(lang: AvailableLang) {
        this.translation.setLang(lang);

        this.router.navigate(['/bookings/pending']);
    }
}
