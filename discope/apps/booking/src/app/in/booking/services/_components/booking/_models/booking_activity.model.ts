export class BookingActivity {
    // index signature
    [key: string]: any;
    // model entity
    public get entity():string { return 'sale\\booking\\BookingActivity' };
    // constructor with public properties
    constructor(
        public id: number = 0,
        public name: string = '',
        public activity_booking_line_id: any = {},
        public booking_line_group_id: any = {},
        public supplies_booking_lines_ids: any[] = [],
        public transports_booking_lines_ids: any[] = [],
        public counter: number = 1,
        public total: number = 0,
        public price: number = 0,
        public is_virtual: false,
        public activity_date: Date = new Date(),
        public time_slot_id: number = 0,
        public providers_ids: any[] = [],
        public rental_unit_id: any = {}
    ) {}
}
