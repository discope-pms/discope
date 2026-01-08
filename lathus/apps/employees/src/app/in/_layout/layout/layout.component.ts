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

    public title = '';

    constructor(
        private app: AppService,
        private router: Router,
        private route: ActivatedRoute
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
        console.log('BACK');
    }

    public goTo(route: string) {
        console.log('GO TO ' + route);
    }
}
