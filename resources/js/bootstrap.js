/**
 * bootstrap.js
 * ตั้งค่า axios สำหรับเรียก API (Laravel)
 * Author :
 * Created :
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
