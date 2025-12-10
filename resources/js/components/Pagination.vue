<template>
    <div class="flex items-center justify-between mt-6">
        <div class="text-sm text-gray-700">
            <span v-if="pagination.from && pagination.to">
                Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
            </span>
            <span v-else>
                Total: {{ pagination.total }} results
            </span>
        </div>
        <div class="flex gap-2">
            <button
                @click="$emit('page-change', pagination.current_page - 1)"
                :disabled="!hasPreviousPage"
                :class="[
                    'px-4 py-2 rounded-md',
                    hasPreviousPage
                        ? 'bg-blue-600 text-white hover:bg-blue-700'
                        : 'bg-gray-300 text-gray-500 cursor-not-allowed',
                ]"
            >
                Previous
            </button>
            <span class="px-4 py-2 text-gray-700">
                Page {{ pagination.current_page }} of {{ pagination.last_page }}
            </span>
            <button
                @click="$emit('page-change', pagination.current_page + 1)"
                :disabled="!hasNextPage"
                :class="[
                    'px-4 py-2 rounded-md',
                    hasNextPage
                        ? 'bg-blue-600 text-white hover:bg-blue-700'
                        : 'bg-gray-300 text-gray-500 cursor-not-allowed',
                ]"
            >
                Next
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    pagination: {
        type: Object,
        required: true,
    },
});

const hasNextPage = computed(() => {
    return props.pagination.current_page < props.pagination.last_page;
});

const hasPreviousPage = computed(() => {
    return props.pagination.current_page > 1;
});

defineEmits(['page-change']);
</script>

