import { Component, EventEmitter, Input, OnInit, Output } from '@angular/core';
import { Center } from '../../../../../type';
import { Router } from '@angular/router';

@Component({
    selector: 'app-select-center-header',
    templateUrl: 'select-center-header.component.html',
    styleUrls: ['select-center-header.component.scss']
})
export class SelectCenterHeaderComponent implements OnInit {

    @Input() centerList: Center[];
    @Input() selectedCenter: Center|null;
    @Input() showCenterSettingsBtn: boolean = true;

    @Output() selectedCenterChange= new EventEmitter();

    constructor(
        private router: Router
    ) {
    }

    public ngOnInit() {
    }

    public centerChange(id: number) {
        const center = this.centerList.find(c => c.id === id);
        this.selectedCenterChange.emit(center);
    }

    public async goTo(destination: 'center-settings'|'user-settings') {
        switch (destination) {
            case 'center-settings':
                await this.router.navigate(['/center/' + this.selectedCenter?.id + '/consumption-meters']);
                break;
            case 'user-settings':
                await this.router.navigate(['/user-settings']);
                break;
        }
    }
}
