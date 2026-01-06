import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from 'sb-shared-lib';

declare global {
    interface Window { context: any; }
}

/**
 * This is the component that is bootstrapped by app.module.ts
 * It is initialized but not rendered since we use app-routing.module to route towards target Component.
 */
@Component({
    selector: 'app-root',
    templateUrl: './app.root.component.html',
    styleUrls: ['./app.root.component.scss']
})
export class AppRootComponent implements OnInit {

    constructor(
        private auth: AuthService,
        private router: Router
    ) {}

    public async ngOnInit() {
        this.captureSystemInfo();
        this.authenticateUser();
    }

    private captureSystemInfo() {
        let info = {
            resolution: window.screen.width + 'x' + window.screen.height,
            platform: navigator.hasOwnProperty('platform') ? navigator.platform : '',
            vendor: navigator.hasOwnProperty('vendor') ? navigator.vendor : '',
            agent: navigator.userAgent,
            language: navigator.language
        };

        document.cookie = `system_info=${JSON.stringify(info)}; path=/; max-age=${60 * 60 * 24 * 365}`;
    }

    private async authenticateUser() {
        try {
            await this.auth.authenticate();
            await this.router.navigate(['/planning/employees']);
        } catch (e: any) {
            await this.router.navigate(['/auth/sign-in']);
        }
    }
}
