import { Injectable } from '@angular/core';
import { translations } from 'src/assets/i18n/translations';

export type AvailableLang = 'fr'|'en';

@Injectable({
    providedIn: 'root',
})
export class TranslationService {

    private _selectedLang: AvailableLang = 'fr';

    public availableLangList: AvailableLang[] = ['fr', 'en'];

    constructor() {
        try {
            const lang = localStorage.getItem('selectedLang');
            if(lang && (lang === 'fr' || lang === 'en')) {
                this.setLang(lang);
            }
        }
        catch (error) {
            console.error('Error getting selectedLang from local storage', error);
        }
    }

    public setLang(lang: AvailableLang) {
        this._selectedLang = lang;

        try {
            localStorage.setItem('selectedLang', lang);
        }
        catch (error) {
            console.error('Error setting selectedLang to local storage', error);
        }
    }

    public getLang(): AvailableLang {
        return this._selectedLang;
    }

    public translate(resource_id: string, lang: string|null = null): string {
        if(!lang) {
            lang = this._selectedLang;
        }

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

    public translateWithVar(resource_id: string, values:  { [key: string]: any }, lang: string|null = null) {
        if(!lang) {
            lang = this._selectedLang;
        }

        let translation = this.translate(resource_id, lang);

        Object.entries(values).forEach(([key, value]) => {
            translation = translation.replace(`{${key}}`, value.toString());
        });

        return translation;
    }
}
