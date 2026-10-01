import './bootstrap';
import '../css/app.css';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router'; // Import router

const app = createApp(App);
app.use(router); // Gunakan router
app.mount('#vue-app');