<script setup lang="ts">
import Header from './Header.vue'
import Sidebar from './Sidebar.vue'

interface User {
    name: string
    email: string
    role: string
}

withDefaults(defineProps<{
    user?: User
    pageTitle?: string
}>(), {
    user: () => ({ name: '', email: '', role: '' }),
    pageTitle: '',
})
</script>

<template>
    <Header :title="pageTitle" :user="user" />
    <Sidebar :user="user" />
</template>
