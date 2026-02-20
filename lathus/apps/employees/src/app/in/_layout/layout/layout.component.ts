import { Component, OnInit } from '@angular/core';
import { AppService } from '../../../_services/app.service';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { filter } from 'rxjs/operators';
import {Location} from "@angular/common";

@Component({
    selector: 'app-layout',
    templateUrl: './layout.component.html',
    styleUrls: ['./layout.component.scss']
})
export class LayoutComponent implements OnInit {

    public title = '';

    constructor(
        private app: AppService,
        private router: Router,
        private route: ActivatedRoute,
        private location: Location
    ) {}

    ngOnInit() {
        this.router.events.pipe(
            filter(event => event instanceof NavigationEnd)
        ).subscribe(() => {
            this.updateRouteData();
        });

        this.updateRouteData();
    }

    private updateRouteData() {
        let route = this.route.firstChild;
        while (route?.firstChild) {
            route = route.firstChild;
        }

        if (route?.snapshot.data) {
            this.title = route.snapshot.data['title'] ?? '';
        }
        else {
            this.title = '';
        }
    }

    public back() {
        if(this.router.url === '/planning-employees') {
            window.location.href = '/apps';
        }
        else {
            this.location.back();
        }
    }

    public goToUserSettings() {
        this.router.navigate(['user-settings']);
    }
}
