<template>
    <div class="min-h-screen bg-gray-50">
        <div class="container mx-auto px-4 py-6">
            <!-- Page Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-semibold text-gray-900 mb-2">Televizorji</h1>
                <p class="text-sm text-gray-600">Primerjaj cene in najdi najboljšo ponudbo</p>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="text-center py-12">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                <p class="mt-4 text-gray-600">Nalaganje televizorjev...</p>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                {{ error }}
            </div>

            <!-- Content -->
            <div v-else>
                <div v-if="televisions.length === 0" class="text-center py-12 text-gray-500">
                    <p class="text-lg">Ni najdenih televizorjev.</p>
                </div>

                <div v-else>
                    <!-- Products Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4 mb-6">
                        <TelevisionCard
                            v-for="television in televisions"
                            :key="television.id"
                            :television="television"
                        />
                    </div>

                    <!-- Pagination -->
                    <Pagination
                        :pagination="pagination"
                        @page-change="handlePageChange"
                    />
                </div>
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

