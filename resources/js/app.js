import './bootstrap'
import { createApp } from 'vue'
import Layout from './components/layout.vue'

const layoutEl = document.getElementById('vue-layout-app')
if (layoutEl) {
    const props = JSON.parse(layoutEl.dataset.props ?? '{}')
    createApp(Layout, props).mount(layoutEl)
}
