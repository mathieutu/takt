import './bootstrap'
import { createApp } from 'vue'
import Sidebar from './components/Sidebar.vue'
import Header from './components/Header.vue'
import LoginPage from './views/LoginPage.vue'
import RegisterPage from './views/RegisterPage.vue'
import DashboardPage from './views/DashboardPage.vue'
import ClientsPage from './views/ClientsPage.vue'
import ProjectsPage from './views/ProjectsPage.vue'
import ActivityReportPage from './views/ActivityReportPage.vue'
import SettingsPage from './views/SettingsPage.vue'
import ExportPage from './views/ExportPage.vue'

const sidebarEl = document.getElementById('vue-sidebar')
if (sidebarEl) {
    createApp(Sidebar, JSON.parse(sidebarEl.dataset.props ?? '{}')).mount(sidebarEl)
}

const headerEl = document.getElementById('vue-header')
if (headerEl) {
    createApp(Header, JSON.parse(headerEl.dataset.props ?? '{}')).mount(headerEl)
}

const pages = {
    'vue-login': LoginPage,
    'vue-register': RegisterPage,
    'vue-dashboard': DashboardPage,
    'vue-clients': ClientsPage,
    'vue-projects': ProjectsPage,
    'vue-activity-reports': ActivityReportPage,
    'vue-settings': SettingsPage,
    'vue-export':   ExportPage,
}

for (const [id, component] of Object.entries(pages)) {
    const el = document.getElementById(id)
    if (el) {
        createApp(component, JSON.parse(el.dataset.props ?? '{}')).mount(el)
    }
}
