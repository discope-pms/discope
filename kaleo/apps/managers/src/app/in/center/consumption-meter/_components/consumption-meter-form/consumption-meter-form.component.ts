import { Component, Input, OnInit } from '@angular/core';
import { FormGroup } from '@angular/forms';
import { MeterUnit, TypeMeter } from '../../../../../../type';
import { TranslationService } from '../../../../../_services/translation.service';

@Component({
    selector: 'app-consumption-meter-form',
    templateUrl: './consumption-meter-form.component.html',
    styleUrls: ['./consumption-meter-form.component.scss']
})
export class ConsumptionMeterFormComponent implements OnInit {

    @Input() form: FormGroup;

    public typerMeterList: { value: TypeMeter, label: string }[] = [];
    public meterUnitList: MeterUnit[] = ['m3', 'kWh', 'L', '%', 'cm'];

    constructor(
        private translation: TranslationService
    ) {
        this.typerMeterList = [
            { value: 'water', label: this.translation.translate('TYPE_METER_WATER') },
            { value: 'gas', label: this.translation.translate('TYPE_METER_GAS') },
            { value: 'electricity', label: this.translation.translate('TYPE_METER_ELECTRICITY') },
            { value: 'gas tank', label: this.translation.translate('TYPE_METER_GAS_TANK') },
            { value: 'oil tank', label: this.translation.translate('TYPE_METER_OIL_TANK') },
        ];
    }

    public ngOnInit() {
    }

    public changeSelectedProduct(id: number) {
        this.form.get('product_id')?.setValue(id);
    }
}
