import { Injectable } from '@angular/core';
import { translations } from 'src/assets/i18n/translations';

@Injectable({
  providedIn: 'root',
})
export class TranslationService {

    public translate(resource_id: string, lang: string = 'fr'): string {
        const specificLang = translations[lang];
        const genericLang = translations[lang.split('_')[0]];

        if (specificLang && specificLang[resource_id]) {
            return specificLang[resource_id];
        }
        else if (genericLang && genericLang[resource_id]) {
            return genericLang[resource_id];
        }
        else {
            return resource_id;
        }
    }

    public translateWithVar(resource_id: string, values:  { [key: string]: any }, lang: string = 'fr') {
        let translation = this.translate(resource_id, lang);

        Object.entries(values).forEach(([key, value]) => {
            translation = translation.replace(`{${key}}`, value.toString());
        });

        return translation;
    }
}
