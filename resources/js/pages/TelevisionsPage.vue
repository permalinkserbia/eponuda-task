<template>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">Televisions</h1>

        <div v-if="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            <p class="mt-4 text-gray-600">Loading televisions...</p>
        </div>

        <div v-else-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ error }}
        </div>

        <div v-else>
            <div v-if="televisions.length === 0" class="text-center py-12 text-gray-500">
                No televisions found.
            </div>

            <div v-else>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <TelevisionCard
                        v-for="television in televisions"
                        :key="television.id"
                        :television="television"
                    />
                </div>

                <Pagination
                    :pagination="pagination"
                    @page-change="handlePageChange"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useTelevisions } from '../composables/useTelevisions';
import TelevisionCard from '../components/TelevisionCard.vue';
import Pagination from '../components/Pagination.vue';

const {
    televisions,
    loading,
    error,
    pagination,
    fetchTelevisions,
} = useTelevisions();

const handlePageChange = (page) => {
    fetchTelevisions(page);
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

onMounted(() => {
    fetchTelevisions(1);
});
</script>

