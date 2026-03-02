import { Component, OnInit } from '@angular/core';

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

    constructor() {}

    public async ngOnInit() {
        // create a cookie with system signature
        this.captureSystemInfo();
    }

    private captureSystemInfo() {
        // #memo - we use navigator.platform and navigator.vendor despite being marked as deprecated since there is no replacement
        let info = {
            resolution: window.screen.width + 'x' + window.screen.height,
            platform: navigator.hasOwnProperty('platform')?navigator.platform:'',
            vendor: navigator.hasOwnProperty('vendor')?navigator.vendor:'',
            agent: navigator.userAgent,
            language: navigator.language
        };

        document.cookie = `system_info=${JSON.stringify(info)}; path=/; max-age=${60 * 60 * 24 * 365}`;
    }

}
