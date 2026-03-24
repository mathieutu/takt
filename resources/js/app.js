import './bootstrap';
import { createApp } from 'vue';

import HomePage from './views/HomePage.vue';

const app = createApp({});

app.component('home-page', HomePage);

app.mount('#app');
