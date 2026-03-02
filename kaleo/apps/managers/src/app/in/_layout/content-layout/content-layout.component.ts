import { Component, ContentChild, Input, OnInit, TemplateRef } from '@angular/core';
import { Location } from '@angular/common';
import { Router } from '@angular/router';

@Component({
    selector: 'app-content-layout',
    templateUrl: './content-layout.component.html',
    styleUrls: ['./content-layout.component.scss']
})
export class ContentLayoutComponent implements OnInit {

    @Input() showBack: boolean = true;
    @Input() loading: boolean = false;
    @Input() backNavigateTo: string = '';

    @ContentChild('title') title: TemplateRef<any>|null = null;
    @ContentChild('footer') footer: TemplateRef<any>|null = null;

    constructor(
        private location: Location,
        private router: Router
    ) {}

    public ngOnInit() {
    }

    public goBack() {
        if(this.backNavigateTo.length === 0) {
            this.location.back();
        }
        else {
            this.router.navigate([this.backNavigateTo]);
        }
    }
}
