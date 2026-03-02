import { Pipe, PipeTransform } from '@angular/core';
import { TranslationService } from 'src/app/_services/translation.service';

@Pipe({
    name: 'translate'
})
export class TranslatePipe implements PipeTransform {

    constructor(
        private translationService: TranslationService
    ) {}

    public transform(value: string, lang: string|null = null): string {
        return this.translationService.translate(value, lang);
    }
}
