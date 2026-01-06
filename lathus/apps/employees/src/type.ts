export interface Model {
    id: number,
    state: 'instance'|'draft'|'archive'
    modified: string,
    name: string
}

export interface Center extends Model {
}

export interface TimeSlot extends Model {
    code: 'B'|'AM'|'L'|'PM'|'D'|'EV',
    schedule_from: string,
    schedule_to: string
}

export interface Category extends Model {
    code: string
}

export interface Partner extends Model {
    relationship: 'employee'|'provider',
    is_active: boolean
}

export interface Employee extends Partner {
    relationship: 'employee',
    activity_product_models_ids: number[]
}

export interface Provider extends Partner {
    relationship: 'provider'
}

export interface ProductModel extends Model {
}

export interface ActivityMap {
    [index: number]: { // partner_id or 0 if not assigned
        [date: string]: ActivityMapDay;
    };
}

interface ActivityMapDay {
    AM: ActivityMapActivity[];
    PM: ActivityMapActivity[];
    EV: ActivityMapActivity[];
}

interface ActivityMapActivity {
    counter: number;
    counter_total: number;
    id: number;
    name: string;
    has_staff_required: boolean;
    employee_id: number | null;
    has_provider: boolean;
    qty: number;
    providers_ids: number[];
    activity_date: string; // ISO date string
    time_slot_id: number;
    is_exclusive: boolean;
    schedule_from: string; // HH:mm:ss
    schedule_to: string;   // HH:mm:ss
    booking_id: ActivityMapBooking | null;
    booking_line_group_id: ActivityMapBookingLineGroup | null;
    product_model_id: ActivityMapProductModel;
    activity_booking_line_id: number | null;
    camp_id: ActivityMapCamp | null;
    group_num: number | null;
    is_partner_event: boolean;
    time_slot: "AM" | "PM" | "EV";
    customer_id: ActivityMapCustomer | null;
    partner_identity_id: ActivityMapPartnerIdentity | null;
    age_range_assignments_ids: ActivityMapAgeRangeAssignment[];
    partner_id: ActivityMapPartner | null;
}

interface ActivityMapBooking {
    id: number;
    name: string;
    description: string;
    status: "quote" | "option" | "confirmed" | "validated" | "checkedin" | "checkedout" | "proforma" | "invoiced" | "debit_balance" | "credit_balance" | "balanced" | "cancelled";
    date_from: string; // DD/MM/YYYY
    date_to: string;   // DD/MM/YYYY
    payment_status: "due" | "paid";
    customer_id: number;
    nb_pers: number;
}

interface ActivityMapBookingLineGroup {
    id: number;
    nb_pers: number;
    has_person_with_disability: boolean;
    person_disability_description: string | null;
    age_range_assignments_ids: number[];
}

interface ActivityMapCamp {
    id: number,
    status: "draft" | "published" | "cancelled",
    name: string,
    short_name: string,
    date_from: string, // ISO date string
    date_to: string, // ISO date string
    min_age: number,
    max_age: number,
    enrollments_qty: number,
    employee_ratio: number
}

interface ActivityMapProductModel {
    id: number;
    name: string;
    activity_color: string | null;
    providers_ids: number[];
}

interface ActivityMapCustomer {
    id: number;
    name: string;
    partner_identity_id: number;
}

interface ActivityMapPartnerIdentity {
    id: number;
    name: string;
    address_city: string;
}

interface ActivityMapPartner {
    id: number,
    name: string,
    relationship: "employee" | "provider"
}

interface ActivityMapAgeRangeAssignment {
    id: number;
    booking_line_group_id: number;
    qty: number;
    age_from: number;
    age_to: number;
}

export interface CustomerIdentity extends Model {
    display_name: string
}

export interface Booking extends Model {
    date_from: string,
    date_to: string,
    customer_identity_id: CustomerIdentity|number,
    center_id: Center|number
}

export type TypeMeter = 'water'|'gas'|'electricity'|'gas tank'|'oil tank';
export type MeterUnit = 'm3'|'kWh'|'L'|'%'|'cm';

export interface ConsumptionMeter extends Model {
    center_id: number,
    type_meter: TypeMeter,
    index_value: number,
    coefficient: number,
    meter_number: string,
    has_ean: boolean,
    meter_ean: string,
    meter_unit: MeterUnit,
    product_id: number,
    description_meter: string
}

export type TypeInspection = 'checkedin'|'checkedout';
export type InspectionStatus = 'pending'|'submitted'|'billed';

export interface BookingInspection extends Model {
    booking_id: Booking|number,
    type_inspection: TypeInspection,
    date_inspection: string,
    status: InspectionStatus
}

export interface ConsumptionMeterReading extends Model {
    booking_inspection_id: BookingInspection|number,
    consumption_meter_id: ConsumptionMeter|number,
    booking_id: Booking|number,
    center_id: Center|number,
    date_reading: string,
    index_value: number,
    unit_price: number
}

export interface Product extends Model {
}

export interface ContactIdentity extends Model {
    email: string|null
}

export interface BookingContact extends Model {
    booking_id: Booking|number,
    partner_identity_id: ContactIdentity|number,
    type: 'booking'|'invoice'|'contract'|'sojourn'
}
