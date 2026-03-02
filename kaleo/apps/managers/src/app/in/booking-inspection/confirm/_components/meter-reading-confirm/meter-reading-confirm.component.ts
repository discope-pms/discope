import { Component, Input, OnInit } from '@angular/core';
import { MeterUnit, TypeMeter } from '../../../../../../type';
import { TranslationService } from '../../../../../_services/translation.service';
import { mapTypeMeterTranslationKey } from '../../../../../../assets/data/consumption-meter';

@Component({
    selector: 'app-meter-reading-confirm',
    templateUrl: 'meter-reading-confirm.component.html',
    styleUrls: ['meter-reading-confirm.component.scss']
})
export class MeterReadingConfirmComponent implements OnInit {

    @Input() typeMeter: TypeMeter;
    @Input() meterUnit: MeterUnit;
    @Input() indexValue: number;
    @Input() unitPrice: number;

    public paddedIndexValue: string = '00000,000';

    public typeMeterStr: string = '';

    constructor(
        private translation: TranslationService
    ) {}

    public ngOnInit() {
        this.paddedIndexValue = this.formatIndexValue(this.indexValue);
        this.typeMeterStr = this.translation.translate(mapTypeMeterTranslationKey[this.typeMeter]);
    }

    private formatIndexValue(indexValue: number) {
        let res = indexValue.toString();
        while (res.length < 8) {
            res = '0' + res;
        }

        return res.slice(0, -3) + ',' + res.slice(-3);
    }
}
