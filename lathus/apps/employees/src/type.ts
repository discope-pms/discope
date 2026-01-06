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
