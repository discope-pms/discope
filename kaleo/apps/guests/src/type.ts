export interface GuestUser {
    booking_id: number,
    email: string
}

export interface Booking {
    id: number,
    name: string,
    center_id: Center,
    booking_lines_groups_ids: BookingLineGroup[],
    guest_list_id: GuestList
}

export interface Center {
    id: number,
    name: string,
    email: string,
    phone: string,
    address_street: string,
    address_city: string,
    address_zip: string,
    address_country: string,
}

export interface BookingLineGroup {
    id: number,
    name: string,
    date_from: string,
    date_to: string,
    group_type: 'sojourn',
    nb_pers: number,
    nb_children: number,
    nb_adults: number,
}

export type GuestListStatus = 'pending'|'sent';

export interface GuestList {
    id: number,
    status: GuestListStatus,
    guest_list_items_ids: { [key: number]: GuestListItem }
}

export type Gender = 'M'|'F'|'X';

export interface GuestListItem {
    id: number,
    booking_line_group_id: number,
    firstname: string|null,
    lastname: string|null,
    is_coordinator: boolean|null,
    gender: Gender|null,
    date_of_birth: number|null,
    citizen_identification: string|null,
    address_street: string|null,
    address_zip: string|null,
    address_city: string|null,
    address_country: string
}
