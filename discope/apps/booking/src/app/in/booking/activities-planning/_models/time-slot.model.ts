export class TimeSlot {
    constructor(
        public id: number = 0,
        public name: string = '',
        public code: 'AM'|'PM'|'EV' = 'AM'
    ) {}
}
