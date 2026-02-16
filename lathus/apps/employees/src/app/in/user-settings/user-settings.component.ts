import { Component, OnInit } from '@angular/core';
import { AuthService } from 'sb-shared-lib';
import { Router } from '@angular/router';

@Component({
    selector: 'user-settings',
    templateUrl: 'user-settings.component.html',
    styleUrls: ['user-settings.component.scss']
})
export class UserSettingsComponent implements OnInit {

    public name = '';
    public login = '';

    constructor(
        private auth: AuthService,
        private router: Router
    ) {
    }

    ngOnInit() {
        this.loadUser();
    }

    async loadUser() {
        const user = await this.auth.getUser();

        this.name = user.name;
        this.login = user.login;
    }

    async signOut() {
        this.auth.signOut();
        this.router.navigate(['/auth/sign-in']);
    }
}
