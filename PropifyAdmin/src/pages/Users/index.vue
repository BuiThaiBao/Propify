<script setup>
import { ref } from 'vue'
import PageHeader from '@/components/shared/PageHeader.vue'
import UsersFilter from './UsersFilter.vue'
import UsersTable from './UsersTable.vue'

const searchQuery = ref('')
const filterRole = ref('all')
</script>

<template>
  <div>
    <PageHeader
      title="Quản lý tài khoản"
      subtitle="Quản lý người dùng và môi giới trên hệ thống"
    />

    <UsersFilter
      v-model:search="searchQuery"
      v-model:role="filterRole"
    />

    <UsersTable
      :search="searchQuery"
      :filter-role="filterRole"
    />
  </div>
</template>
