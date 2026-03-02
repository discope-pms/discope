import { Pipe, PipeTransform } from '@angular/core';
import { TranslationService } from '../_services/translation.service';

@Pipe({
    name: 'translateWithVar'
})
export class TranslateWithVarPipe implements PipeTransform {

    constructor(private translationService: TranslationService) {}

    public transform(value: string, values: { [key: string]: any }, lang: string|null = null): string {
        return this.translationService.translateWithVar(value, values, lang);
    }
}
