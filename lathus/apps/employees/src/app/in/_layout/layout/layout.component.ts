import { Component, OnInit } from '@angular/core';
import { Center } from '../../../../type';
import { AppService } from '../../../_services/app.service';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { filter } from 'rxjs/operators';

@Component({
    selector: 'app-layout',
    templateUrl: './layout.component.html',
    styleUrls: ['./layout.component.scss']
})
export class LayoutComponent implements OnInit {

    public centerList: Center[] = [];

    public selectedCenter: Center|null = null;

    public showCenterSettingsBtn: boolean = false;

    constructor(
        private app: AppService,
        private router: Router,
        private route: ActivatedRoute
    ) {}

    public ngOnInit() {
        this.app.centerList$.subscribe(centerList => {
            this.centerList = centerList;
        });

        this.app.center$.subscribe(center => {
            this.selectedCenter = center;
        });

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
            this.showCenterSettingsBtn = route.snapshot.data['showCenterSettingsBtn'] ?? false;
        }
        else {
            this.showCenterSettingsBtn = false;
        }
    }

    public centerChange(center: Center) {
        this.app.setCenter(center);
    }
}
