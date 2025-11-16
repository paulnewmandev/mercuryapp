/**
 * @fileoverview Configura Axios con cabeceras por defecto para MercuryApp.
 */
import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
