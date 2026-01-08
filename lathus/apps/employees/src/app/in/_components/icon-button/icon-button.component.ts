import { Component, Input, OnInit } from '@angular/core';

@Component({
    selector: 'app-icon-button',
    templateUrl: 'icon-button.component.html',
    styleUrls: ['icon-button.component.scss']
})
export class IconButtonComponent implements OnInit {

    @Input() icon: string;
    @Input() fill: boolean = false;
    @Input() outlined: boolean = false;
    @Input() fillInverse: boolean = false;
    @Input() outlinedInverse: boolean = false;
    @Input() disabled: boolean = false;

    constructor(
    ) {}

    public ngOnInit() {
    }
}
