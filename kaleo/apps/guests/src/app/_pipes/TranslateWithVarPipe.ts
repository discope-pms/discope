import { Pipe, PipeTransform } from '@angular/core';
import { TranslationService } from 'src/app/_services/TranslationService';

@Pipe({
    name: 'translateWithVar'
})
export class TranslateWithVarPipe implements PipeTransform {

    constructor(private translationService: TranslationService) {}

    public transform(value: string, values: { [key: string]: any }, lang: string = 'fr'): string {
        return this.translationService.translateWithVar(value, values, lang);
    }
}
