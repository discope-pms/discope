import { Component, EventEmitter, Input, OnChanges, OnInit, Output, SimpleChanges } from '@angular/core';
import { FormControl } from '@angular/forms';
import { BehaviorSubject, of } from 'rxjs';
import { debounceTime, distinctUntilChanged, switchMap, catchError } from 'rxjs/operators';
import { ApiService } from '../../../../../../../_services/api.service';
import { Product } from '../../../../../../../../type';
import { MatAutocompleteSelectedEvent } from '@angular/material/autocomplete';

@Component({
    selector: 'app-product-select',
    templateUrl: 'product-select.component.html',
    styleUrls: ['product-select.component.scss']
})
export class ProductSelectComponent implements OnInit, OnChanges {

    @Input() label: string;
    @Input() productId: number;

    @Output() selectionChange = new EventEmitter();

    public searchControl = new FormControl();

    public filteredProducts: Product[] = [];

    public selectedProductName: string = '';

    private searchSubject = new BehaviorSubject<string>('');

    constructor(
            private api: ApiService) {
    }

    public ngOnInit() {
        this.searchControl.valueChanges.pipe(
            debounceTime(300),
            distinctUntilChanged(),
        ).subscribe(query => {
            this.searchSubject.next(query);
        });

        this.searchSubject.pipe(
            switchMap(query => this.api.fetchProductByName(query).pipe(
                catchError(() => of([])) // Handle errors by returning an empty array
            ))
        ).subscribe(products => {
            this.filteredProducts = products;
        });
    }

    public ngOnChanges(changes: SimpleChanges) {
        if(changes.productId) {
            if(this.productId) {
                this.api.fetchProduct(this.productId).subscribe(product => {
                    this.selectedProductName = product ? product.name : '';
                });
            }
            else {
                this.selectedProductName = '';
            }
        }
    }

    public productSelected(event: MatAutocompleteSelectedEvent) {
        this.selectionChange.emit(event.option.value);

        const selectedProduct = this.filteredProducts.find(product => product.id === event.option.value);
        if(selectedProduct) {
            this.selectedProductName = selectedProduct.name;
        }
        else {
            this.selectedProductName = '';
        }
    }
}
