

import Alpine from 'alpinejs';
import { initBankAutocomplete } from './bank-autocomplete';

window.Alpine = Alpine;
Alpine.start();

document.addEventListener('DOMContentLoaded', initBankAutocomplete);
