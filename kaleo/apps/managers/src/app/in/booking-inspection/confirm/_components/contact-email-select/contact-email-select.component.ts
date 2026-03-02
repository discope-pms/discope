import { Component, forwardRef, Input, OnChanges, SimpleChanges } from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';
import { BehaviorSubject, Subject} from 'rxjs';
import { debounceTime, distinctUntilChanged } from 'rxjs/operators';
import { MatAutocompleteSelectedEvent } from '@angular/material/autocomplete';

@Component({
    selector: 'app-contact-email-select',
    templateUrl: 'contact-email-select.component.html',
    styleUrls: ['contact-email-select.component.scss'],
    providers:  [
        {
            provide: NG_VALUE_ACCESSOR,
            useExisting: forwardRef(() => ContactEmailSelectComponent),
            multi: true
        }
    ]
})
export class ContactEmailSelectComponent implements ControlValueAccessor, OnChanges {

    @Input() emailList: string[];
    @Input() isValid: boolean;
    @Input() isTouched: boolean;
    @Input() label: string;
    @Input() placeholder: string;
    @Input() isFirst: boolean;

    public value: string = '';

    public emailFilteredList: string[] = [];

    private inputSubject = new Subject<string>();

    private searchSubject = new BehaviorSubject<string>('');

    private isFocused = false;

    public onChange: any = () => {};

    public onTouched: any = () => {};

    constructor(
    ) {}

    public ngOnInit() {
        this.inputSubject.pipe(
            debounceTime(300),
            distinctUntilChanged()
        ).subscribe(value => {
            if (this.isFocused) {
                this.searchSubject.next(value);
            }
        })

        this.searchSubject.subscribe(search => {
            if(!search) {
                this.emailFilteredList = this.emailList;
            }
            else {
                this.emailFilteredList = this.emailList.filter(email => {
                    return email.includes(search);
                });
            }
        });
    }

    public ngOnChanges(changes: SimpleChanges) {
        if(changes.emailList && this.emailList.length > 0 && this.value.length === 0) {
            this.emailFilteredList = this.emailList;
        }
    }

    public onInput(event: any) {
        const value = event.target.value;

        this.inputSubject.next(value);
        this.onChange(value);
    }

    public onFocus() {
        this.isFocused = true;
        this.searchSubject.next(this.value);
    }

    public onBlur() {
        this.isFocused = false;
        this.onTouched();
    }

    public emailSelected(event: MatAutocompleteSelectedEvent) {
        const value = event.option.value;

        this.value = value;
        this.onChange(value);
    }

    public writeValue(value: string): void {
        this.value = value;
    }

    public registerOnChange(fn: any): void {
        this.onChange = fn;
    }

    public registerOnTouched(fn: any): void {
        this.onTouched = fn;
    }

    public setDisabledState?(isDisabled: boolean): void {
        // Optional: Handle the control's disabled state
    }
}
