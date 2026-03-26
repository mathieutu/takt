import './bootstrap'
import { createApp } from 'vue'
import Sidebar from './components/Sidebar.vue'
import Header from './components/Header.vue'
import DashboardPage from './views/DashboardPage.vue'
import CRAPage from './views/CRAPage.vue'
import ClientsPage from './views/ClientsPage.vue'
import ProjectsPage from './views/ProjectsPage.vue'
import RecapPage from './views/RecapPage.vue'
import ExportPage from './views/ExportPage.vue'
import SettingsPage from './views/SettingsPage.vue'

const sidebarEl = document.getElementById('vue-sidebar')
if (sidebarEl) {
    createApp(Sidebar, JSON.parse(sidebarEl.dataset.props ?? '{}')).mount(sidebarEl)
}

const headerEl = document.getElementById('vue-header')
if (headerEl) {
    createApp(Header, JSON.parse(headerEl.dataset.props ?? '{}')).mount(headerEl)
}

const pages = {
    'vue-dashboard': DashboardPage,
    'vue-cra': CRAPage,
    'vue-clients': ClientsPage,
    'vue-projects': ProjectsPage,
    'vue-recap': RecapPage,
    'vue-export': ExportPage,
    'vue-settings': SettingsPage,
}

for (const [id, component] of Object.entries(pages)) {
    const el = document.getElementById(id)
    if (el) {
        createApp(component, JSON.parse(el.dataset.props ?? '{}')).mount(el)
    }
}
