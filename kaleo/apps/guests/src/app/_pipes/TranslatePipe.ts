import { Pipe, PipeTransform } from '@angular/core';
import { TranslationService } from 'src/app/_services/TranslationService';

@Pipe({
    name: 'translate'
})
export class TranslatePipe implements PipeTransform {

    constructor(private translationService: TranslationService) {}

    public transform(value: string, lang: string = 'fr'): string {
        return this.translationService.translate(value, lang);
    }
}