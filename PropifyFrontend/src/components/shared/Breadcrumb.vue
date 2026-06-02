<template>
  <nav class="flex text-xs text-slate-500 mb-4" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-1.5 flex-wrap">
      <li v-for="(crumb, index) in crumbs" :key="index" class="inline-flex items-center">
        <span v-if="index > 0" class="mx-1.5 text-slate-300 font-normal">/</span>
        <RouterLink 
          v-if="crumb.to && index < crumbs.length - 1" 
          :to="crumb.to" 
          class="inline-flex items-center hover:text-blue-600 transition-colors font-normal text-slate-500"
        >
          {{ crumb.label }}
        </RouterLink>
        <span v-else class="text-slate-600 font-normal select-none truncate max-w-[200px] md:max-w-xs block">
          {{ crumb.label }}
        </span>
      </li>
    </ol>
  </nav>
</template>

<script setup>
defineProps({
  crumbs: {
    type: Array,
    required: true
  }
});
</script>
