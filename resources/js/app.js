

import Alpine from 'alpinejs';
import { initBankAutocomplete } from './bank-autocomplete';

window.Alpine = Alpine;
Alpine.start();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBankAutocomplete);
} else {
    initBankAutocomplete();
}
