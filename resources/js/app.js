

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

import axios from 'axios';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const token = document
    .querySelector('meta[name="csrf-token"]');

if (token) {

    window.axios.defaults.headers.common['X-CSRF-TOKEN'] =
        token.content;

} else {

    console.error('CSRF token not found');

}