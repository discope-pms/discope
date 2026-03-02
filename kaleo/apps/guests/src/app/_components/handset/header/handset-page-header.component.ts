import { Component, Input, EventEmitter, OnInit, Output } from '@angular/core';

@Component({
    selector: 'handset-page-header',
    templateUrl: './handset-page-header.component.html',
    styleUrls: ['./handset-page-header.component.scss']
})
export class HandsetPageHeaderComponent implements OnInit {

    @Input() title: string;

    @Output() back = new EventEmitter();

    constructor(
    ) {}

    public ngOnInit() {
    }
}
