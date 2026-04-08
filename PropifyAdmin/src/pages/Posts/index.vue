<script setup>
import { ref } from 'vue'
import PageHeader from '@/components/shared/PageHeader.vue'
import PostsFilter from './PostsFilter.vue'
import PostsTable from './PostsTable.vue'

const searchQuery = ref('')
const filterStatus = ref('all')
const filterType = ref('all')
</script>

<template>
  <div>
    <PageHeader
      title="Quản lý tin đăng"
      subtitle="Duyệt, quản lý và kiểm soát tin đăng bất động sản"
    />

    <PostsFilter
      v-model:search="searchQuery"
      v-model:status="filterStatus"
      v-model:type="filterType"
    />

    <PostsTable
      :search="searchQuery"
      :filter-status="filterStatus"
      :filter-type="filterType"
    />
  </div>
</template>
