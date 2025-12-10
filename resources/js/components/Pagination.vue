<template>
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-6 pt-6 border-t border-gray-200">
        <div class="text-sm text-gray-600">
            <span v-if="pagination.from && pagination.to">
                Prikazujem {{ pagination.from }}–{{ pagination.to }} od {{ pagination.total }} rezultatov
            </span>
            <span v-else>
                Skupaj: {{ pagination.total }} rezultatov
            </span>
        </div>
        <div class="flex items-center gap-2">
            <button
                @click="$emit('page-change', pagination.current_page - 1)"
                :disabled="!hasPreviousPage"
                :class="[
                    'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                    hasPreviousPage
                        ? 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'
                        : 'bg-gray-100 text-gray-400 cursor-not-allowed',
                ]"
            >
                Nazaj
            </button>
            <span class="px-4 py-2 text-sm text-gray-700">
                Stran {{ pagination.current_page }} od {{ pagination.last_page }}
            </span>
            <button
                @click="$emit('page-change', pagination.current_page + 1)"
                :disabled="!hasNextPage"
                :class="[
                    'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                    hasNextPage
                        ? 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'
                        : 'bg-gray-100 text-gray-400 cursor-not-allowed',
                ]"
            >
                Naprej
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

