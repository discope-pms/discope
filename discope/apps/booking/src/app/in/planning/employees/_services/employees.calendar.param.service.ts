import { Injectable } from '@angular/core';
import { Subject } from 'rxjs';
import { ApiService } from 'sb-shared-lib';
import { EnvService } from 'sb-shared-lib';

const millisecondsPerDay:number = 24 * 60 * 60 * 1000;

export class Partner {
    constructor(
        public id: number = 0,
        public name: string = '',
        public relationship: 'employee'|'provider' = 'employee',
        public is_active: boolean = true
    ) {}
}

export class Employee extends Partner {
    constructor(
        public id: number = 0,
        public name: string = '',
        public relationship: 'employee' = 'employee',
        public is_active: boolean = true,
        public activity_product_models_ids: any[] = [],
        public role_id: number = 0,
        public date_start: Date = new Date(),
        public date_end: Date|null = null
    ) {
        super(id, name, relationship, is_active);
    }
}

export class Provider extends Partner {
    constructor(
        public id: number = 0,
        public name: string = '',
        public relationship: 'provider' = 'provider',
        public is_active: boolean = true
    ) {
        super(id, name, relationship, is_active);
    }
}

@Injectable({
    providedIn: 'root'
})
export class PlanningEmployeesCalendarParamService {

    // current state of filters
    private observable: Subject<any>;
    // date from
    private _date_from: Date;
    // date to
    private _date_to: Date;
    // duration in days
    private _duration: number;
    // selected partners (employees/providers)
    private _partners_ids: number[];
    // all employees
    private _all_employees: Employee[];
    // all employees with active contract
    private _employees: Employee[];
    // all providers
    private _providers: Provider[];
    // if true display only product models with has_transport_required
    private _show_only_transport: boolean;
    // selected product category
    private _product_category_id: number;
    // selected product model
    private _product_model_id: number|null;
    // ids of the product models to display (all if empty)
    private _product_model_ids: number[];
    // ids of the employee roles to display (all if empty)
    private _employee_role_ids: number[];
    // timeout handler for debounce
    private timeout: any;
    // current state, for changes detection
    private state: string;

    private environment: any;

    constructor(
        private api: ApiService,
        private env: EnvService
    ) {
        this.observable = new Subject();
    }

    /**
     * Current state according to instant values of the instance.
     */
    private getState(): string {
        return this._date_from.getTime() + this._date_to.getTime() + this._partners_ids.toString() + this._product_model_ids.toString() + this._employee_role_ids.toString();
    }

    private treatAsUTC(date:Date): Date {
        let result = new Date(date.getTime());
        result.setMinutes(result.getMinutes() - result.getTimezoneOffset());
        return result;
    }

    private updateRange(refreshDisplayedEmployees = false) {
        if(this.timeout) {
            clearTimeout(this.timeout);
        }

        // add a debounce in case range is updated several times in a row
        this.timeout = setTimeout( () => {
            if(refreshDisplayedEmployees) {
                this.filterEmployees();

                let employees = this._employees;
                if(this.environment.hasOwnProperty('sale.features.employee_planning.activity_filter') && this.environment['sale.features.employee_planning.activity_filter']) {
                    if(this._product_model_ids.length === 0) {
                        employees = [];
                    }
                    else {
                        employees = this._employees.filter((e) => {
                            let hasProductModel = false;
                            for(let activityId of e.activity_product_models_ids) {
                                if(this._product_model_ids.includes(activityId)) {
                                    hasProductModel = true;
                                    break;
                                }
                            }

                            return hasProductModel;
                        });
                    }
                }

                this.partners_ids = [
                    ...employees.map((e) => e.id),
                    ...this._providers.map((p) => p.id)
                ];
            }

            this.timeout = undefined;
            const new_state = this.getState();
            if(new_state != this.state) {
                this.state = new_state;
                this._duration = Math.abs(this.treatAsUTC(this._date_to).getTime() - this.treatAsUTC(this._date_from).getTime()) / millisecondsPerDay;
                this.observable.next(this.state);
            }
        }, 150);
    }

    /**
     * Allow init request from other components
     */
    public async init() {
        this._duration = 7;
        this._date_from = this.getPreviousMonday();
        this._date_to = new Date(this._date_from.getTime());
        this._date_to.setDate(this._date_from.getDate() + this._duration);
        this._partners_ids = [];
        this._show_only_transport = false;
        this._product_category_id = 0;
        this._product_model_id = null;
        this._product_model_ids = [];
        this._employee_role_ids = [];
        this.state = this.getState();

        this.environment = await this.env.getEnv();
    }

    private getPreviousMonday() {
        const today = new Date();
        const dayOfWeek = today.getDay();

        if(dayOfWeek === 1) {
            return today;
        }

        const daysToSubtract = (dayOfWeek === 0) ? 6 : dayOfWeek - 1;
        today.setDate(today.getDate() - daysToSubtract);
        return today;
    }

    public getObservable(): Subject<any> {
        return this.observable;
    }

    public async loadPartners(centers_ids: number[]) {
        this._providers = await this.api.collect(
            'sale\\provider\\Provider',
            ['relationship', '=', 'provider'],
            Object.getOwnPropertyNames(new Provider()),
            'name', 'asc', 0, 500
        );

        const employees: Employee[] = await this.api.collect(
            'hr\\employee\\Employee',
            [
                ['center_id', 'in', centers_ids],
                ['relationship', '=', 'employee'],
                ['is_active', '=', true]
            ],
            Object.getOwnPropertyNames(new Employee()),
            'name', 'asc', 0, 500
        );

        this._all_employees = employees.map((employee) => {
            return {
                ...employee,
                date_start: new Date(employee.date_start),
                date_end: employee.date_end ? new Date(employee.date_end) : null
            };
        });

        this.filterEmployees();

        this.partners_ids = [
            ...this._employees.map((e) => e.id),
            ...this._providers.map((p) => p.id)
        ];
    }

    private filterEmployees() {
        const date_from = this._date_from.toISOString().slice(0, 10);

        const date_to_clone = new Date(this._date_to.getTime());
        date_to_clone.setDate(date_to_clone.getDate() - 1);
        const date_to = date_to_clone.toISOString().slice(0, 10);

        this._employees = this._all_employees.filter((employee) => {
            const start = employee.date_start.toISOString().slice(0, 10);
            const end = employee.date_end ? employee.date_end.toISOString().slice(0, 10) : null;

            return start <= date_to && (!end || end >= date_from);
        });
    }


    /***********
     * Setters *
     ***********/

    public set partners_ids(partners_ids: number[]) {
        this._partners_ids = [...partners_ids];
        this.updateRange();
    }

    public set product_model_ids(product_model_ids: number[]) {
        this._product_model_ids = [...product_model_ids];
        this.updateRange();
    }

    public set date_from(date: Date) {
        this._date_from = date;
        this.updateRange(true);
    }

    public set date_to(date: Date) {
        this._date_to = date;
        this.updateRange(true);
    }

    public set show_only_transport(show_only_transport: boolean) {
        this._show_only_transport = show_only_transport;
        this.updateRange();
    }

    public set product_category_id(product_category_id: number) {
        this._product_category_id = product_category_id;
        this.updateRange();
    }

    public set product_model_id(product_model_id: number) {
        this._product_model_id = product_model_id;
        this.updateRange();
    }

    public set employee_role_ids(employee_role_ids: number[]) {
        this._employee_role_ids = employee_role_ids;
        this.updateRange();
    }


    /***********
     * Getters *
     ***********/

    public get partners_ids(): number[] {
        return this._partners_ids;
    }

    public get employees(): Employee[] {
        return this._employees;
    }

    public get providers(): Provider[] {
        return this._providers;
    }

    public get partners(): Partner[] {
        return [...this._employees, ...this._providers];
    }

    public get selected_partners(): Partner[] {
        return [
            ...this._employees.filter((e) => this.partners_ids.includes(e.id)),
            ...this._providers.filter((p) => this.partners_ids.includes(p.id))
        ];
    }

    public get product_model_ids(): number[] {
        return this._product_model_ids;
    }

    public get date_from(): Date {
        return this._date_from;
    }

    public get date_to(): Date {
        return this._date_to;
    }

    public get duration(): number {
        return this._duration;
    }

    public get show_only_transport() {
        return this._show_only_transport;
    }

    public get product_category_id() {
        return this._product_category_id;
    }

    public get product_model_id() {
        return this._product_model_id;
    }

    public get employee_role_ids(): any[] {
        return this._employee_role_ids;
    }
}
