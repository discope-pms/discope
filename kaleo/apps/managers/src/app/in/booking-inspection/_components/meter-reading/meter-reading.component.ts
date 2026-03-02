import { Component, EventEmitter, Input, OnInit, Output } from '@angular/core';
import { TranslationService } from '../../../../_services/translation.service';
import { TypeMeter } from '../../../../../type';
import { mapTypeMeterTranslationKey } from '../../../../../assets/data/consumption-meter';

@Component({
    selector: 'app-meter-reading',
    templateUrl: './meter-reading.component.html',
    styleUrls: ['./meter-reading.component.scss']
})
export class MeterReadingComponent implements OnInit {

    @Input() typeMeter: TypeMeter;
    @Input() meterNumber: string;
    @Input() indexValue: number;

    @Output() deleteClick = new EventEmitter();

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
