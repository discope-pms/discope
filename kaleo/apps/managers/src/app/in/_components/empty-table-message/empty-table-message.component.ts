import { Component, OnInit } from '@angular/core';

@Component({
    selector: 'app-empty-table-message',
    template: '<ng-content></ng-content>',
    styleUrls: ['empty-table-message.component.scss']
})
export class EmptyTableMessageComponent implements OnInit {

    constructor() {
    }

    public ngOnInit() {
    }
}
