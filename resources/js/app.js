import './bootstrap'
import { createApp } from 'vue'
import Layout from './components/layout.vue'
import Sidebar from './components/Sidebar.vue'
import Header from './components/Header.vue'

const layoutEl = document.getElementById('vue-layout-app')
if (layoutEl) {
    const props = JSON.parse(layoutEl.dataset.props ?? '{}')
    createApp(Layout, props).mount(layoutEl)
}

const sidebarEl = document.getElementById('vue-sidebar')
if (sidebarEl) {
    const props = JSON.parse(sidebarEl.dataset.props ?? '{}')
    createApp(Sidebar, props).mount(sidebarEl)
}

const headerEl = document.getElementById('vue-header')
if (headerEl) {
    const props = JSON.parse(headerEl.dataset.props ?? '{}')
    createApp(Header, props).mount(headerEl)
}
