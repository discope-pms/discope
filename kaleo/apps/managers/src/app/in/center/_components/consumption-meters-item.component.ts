import { Component, Input, OnInit } from '@angular/core';
import { TypeMeter } from '../../../../type';
import { TranslationService } from '../../../_services/translation.service';
import { mapTypeMeterTranslationKey } from '../../../../assets/data/consumption-meter';

@Component({
    selector: 'app-consumption-meters-item',
    templateUrl: 'consumption-meters-item.component.html',
    styleUrls: ['consumption-meters-item.component.scss']
})
export class ConsumptionMetersItemComponent implements OnInit {

    @Input() typeMeter: TypeMeter;
    @Input() meterNumber: string;

    public typeMeterStr: string = '';

    constructor(
        private translation: TranslationService
    ) {
    }

    public ngOnInit() {
        this.typeMeterStr = this.translation.translate(mapTypeMeterTranslationKey[this.typeMeter]);
    }
}
