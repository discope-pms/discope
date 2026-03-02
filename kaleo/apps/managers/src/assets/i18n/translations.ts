export const translations: { [lang: string]: { [key: string]: string } } = {
    'en': {
        'TYPE_METER_WATER': 'Water',
        'TYPE_METER_GAS': 'Gas',
        'TYPE_METER_ELECTRICITY': 'Electricity',
        'TYPE_METER_GAS_TANK': 'Gas tank',
        'TYPE_METER_OIL_TANK': 'oil tank',

        /**
         * /auth
         */
        'SIGN_IN_CARD_TITLE': 'Connection',
        'SIGN_IN_CARD_SUBTITLE': 'Log in',
        'SIGN_IN_FORM_FIELD_LABEL_USERNAME': 'Username',
        'SIGN_IN_FORM_FIELD_LABEL_PASSWORD': 'Password',
        'SIGN_IN_FORM_CONNECTION_BTN': 'Sign in',
        'SIGN_IN_ERROR_MSG_INVALID_USERNAME_OR_PASSWORD': "Invalid username or password.",

        /**
         * /user-settings
         */
        'USER_SETTINGS_TITLE': 'User',
        'USER_SETTINGS_SIGN_OUT_BTN': 'Sign out',

        /**
         * /booking-inspection
         */
        'BOOKING_INSPECTION_TITLE': 'Booking {bookingName}',
        'BOOKING_INSPECTION_NO_CONSUMPTION_READINGS_YET': 'No consumption readings yet',
        'BOOKING_INSPECTION_CONFIRM_BTN': 'Confirm',
        'CONSUMPTION_METER_READING_NEW_TITLE': 'New meter reading',
        'CONSUMPTION_METER_READING_NEW_METER_FIELD': 'Consumption Meter',
        'CONSUMPTION_METER_READING_NEW_INDEX_VALUE_FIELD': 'Actual Index Value',
        'CONSUMPTION_METER_READING_NEW_EMPTY_TABLE': 'No more consumption meter for this center',
        'CONSUMPTION_METER_READING_NEW_SAVE_BTN': 'Save',
        'CONSUMPTION_METER_INSPECTION_CONFIRM_TITLE': 'Confirmation',
        'CONSUMPTION_METER_INSPECTION_CONFIRM_EMPTY_TABLE': 'No consumption meter readings',
        'CONSUMPTION_METER_INSPECTION_CONFIRM_RECIPIENTS_TITLE': 'Recipients',
        'CONSUMPTION_METER_INSPECTION_CONFIRM_RECIPIENT_X': 'Recipient {x}',
        'CONSUMPTION_METER_INSPECTION_CONFIRM_SAVE_BTN': 'Send',
        'CONSUMPTION_METER_INSPECTION_CONFIRM_BOOKING_INFO': 'Booking {bookingName}',
        'CONSUMPTION_METER_INSPECTION_CONFIRM_READING_INDEX': 'Index',
        'CONSUMPTION_METER_INSPECTION_CONFIRM_READING_UNIT_PRICE': 'Unit price',

        /**
         * /bookings
         */
        'BOOKINGS_PENDING_TITLE': 'Pending bookings',
        'BOOKINGS_PENDING_EMPTY_TABLE': 'No pending booking',
        'BOOKINGS_UPCOMING_TITLE': 'Upcoming bookings',
        'BOOKINGS_UPCOMING_EMPTY_TABLE': 'No upcoming booking',

        /**
         * /consumption-meters
         */
        'CONSUMPTION_METERS_TITLE': 'Consumption meters',
        'CONSUMPTION_METERS_EMPTY_TABLE': 'No consumption meter yet',

        /**
         * /consumption-meter/new
         */
        'CONSUMPTION_METER_NEW_TITLE': 'New consumption meter',
        'CONSUMPTION_METER_NEW_SAVE_BTN': 'Save',

        /**
         * /consumption-meter/:consumption_meter_id
         */
        'CONSUMPTION_METER_EDIT_TITLE': 'Consumption meter',
        'CONSUMPTION_METER_EDIT_SAVE_BTN': 'Save',

        /**
         * Consumption meter form
         */
        'CONSUMPTION_METER_FORM_TYPE_METER': 'Type of Meter',
        'CONSUMPTION_METER_FORM_METER_UNIT': 'Meter Unit',
        'CONSUMPTION_METER_FORM_METER_NUMBER': 'Meter Number',
        'CONSUMPTION_METER_FORM_HAS_EAN': 'Has EAN',
        'CONSUMPTION_METER_FORM_METER_EAN': 'Meter EAN',
        'CONSUMPTION_METER_FORM_PRODUCT': 'Product',
        'CONSUMPTION_METER_FORM_INDEX_VALUE': 'Index Value',
        'CONSUMPTION_METER_FORM_COEFFICIENT': 'Coefficient'
    },
    'fr': {
        'TYPE_METER_WATER': 'Eau',
        'TYPE_METER_GAS': 'Gaz',
        'TYPE_METER_ELECTRICITY': 'Électricité',
        'TYPE_METER_GAS_TANK': 'Réservoir de gaz',
        'TYPE_METER_OIL_TANK': 'Cuve à mazout',

        /**
         * /auth
         */
        'SIGN_IN_CARD_TITLE': 'Connexion',
        'SIGN_IN_CARD_SUBTITLE': "S'identifier",
        'SIGN_IN_FORM_FIELD_LABEL_USERNAME': "Nom d'utilisateur",
        'SIGN_IN_FORM_FIELD_LABEL_PASSWORD': 'Mot de passe',
        'SIGN_IN_FORM_CONNECTION_BTN': 'Connexion',
        'SIGN_IN_ERROR_MSG_INVALID_USERNAME_OR_PASSWORD': "Nom d'utilisateur ou mot de passe invalide.",

        /**
         * /user-settings
         */
        'USER_SETTINGS_TITLE': 'Utilisateur',
        'USER_SETTINGS_SIGN_OUT_BTN': 'Se déconnecter',

        /**
         * /booking-inspection
         */
        'BOOKING_INSPECTION_TITLE': 'Réservation {bookingName}',
        'BOOKING_INSPECTION_NO_CONSUMPTION_READINGS_YET': 'Pas encore de relevé de consommation',
        'BOOKING_INSPECTION_CONFIRM_BTN': 'Valider',
        'CONSUMPTION_METER_READING_NEW_TITLE': 'Nouveau relevé compteur',
        'CONSUMPTION_METER_READING_NEW_METER_FIELD': 'Compteur',
        'CONSUMPTION_METER_READING_NEW_INDEX_VALUE_FIELD': 'Index actuel',
        'CONSUMPTION_METER_READING_NEW_EMPTY_TABLE': 'Plus de compteur pour ce centre',
        'CONSUMPTION_METER_READING_NEW_SAVE_BTN': 'Enregistrer',
        'CONSUMPTION_METER_INSPECTION_CONFIRM_TITLE': 'Confirmation',
        'CONSUMPTION_METER_INSPECTION_CONFIRM_EMPTY_TABLE': 'Aucun relevé de compteur',
        'CONSUMPTION_METER_INSPECTION_CONFIRM_RECIPIENTS_TITLE': 'Destinataires',
        'CONSUMPTION_METER_INSPECTION_CONFIRM_RECIPIENT_X': 'Destinataire {x}',
        'CONSUMPTION_METER_INSPECTION_CONFIRM_SAVE_BTN': 'Envoyer',
        'CONSUMPTION_METER_INSPECTION_CONFIRM_BOOKING_INFO': 'Réservation {bookingName}',
        'CONSUMPTION_METER_INSPECTION_CONFIRM_READING_INDEX': 'Index',
        'CONSUMPTION_METER_INSPECTION_CONFIRM_READING_UNIT_PRICE': 'Prix unitaire',

        /**
         * /bookings
         */
        'BOOKINGS_PENDING_TITLE': 'Réservations en cours',
        'BOOKINGS_PENDING_EMPTY_TABLE': 'Aucune réservation',
        'BOOKINGS_UPCOMING_TITLE': 'Réservations à venir',
        'BOOKINGS_UPCOMING_EMPTY_TABLE': 'Aucune réservation',

        /**
         * /consumption-meters
         */
        'CONSUMPTION_METERS_TITLE': 'Compteurs',
        'CONSUMPTION_METERS_EMPTY_TABLE': 'Pas encore de compteur',

        /**
         * /consumption-meter/new
         */
        'CONSUMPTION_METER_NEW_TITLE': 'Nouveau compteur',
        'CONSUMPTION_METER_NEW_SAVE_BTN': 'Enregistrer',

        /**
         * /consumption-meter/:consumption_meter_id
         */
        'CONSUMPTION_METER_EDIT_TITLE': 'Compteur',
        'CONSUMPTION_METER_EDIT_SAVE_BTN': 'Enregistrer',

        /**
         * Consumption meter form
         */
        'CONSUMPTION_METER_FORM_TYPE_METER': 'Type de compteur',
        'CONSUMPTION_METER_FORM_METER_UNIT': 'Unités',
        'CONSUMPTION_METER_FORM_METER_NUMBER': 'Code',
        'CONSUMPTION_METER_FORM_HAS_EAN': 'EAN ?',
        'CONSUMPTION_METER_FORM_METER_EAN': 'EAN',
        'CONSUMPTION_METER_FORM_PRODUCT': 'Produit',
        'CONSUMPTION_METER_FORM_INDEX_VALUE': 'Index initial',
        'CONSUMPTION_METER_FORM_COEFFICIENT': 'Coefficient'
    }
};
