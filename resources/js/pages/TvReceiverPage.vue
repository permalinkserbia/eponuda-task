<template>
    <div class="min-h-screen bg-gray-50">
        <div class="container mx-auto px-4 py-6">
            <!-- Page Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-semibold text-gray-900 mb-2">TV Sprejemniki</h1>
                <p class="text-sm text-gray-600">Primerjaj cene in najdi najboljšo ponudbo</p>
            </div>

            <div class="flex flex-col lg:flex-row gap-6">
                <!-- Categories Sidebar -->
                <aside class="lg:w-64 flex-shrink-0">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 sticky top-4">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 pb-3 border-b border-gray-200">Kategorije</h2>

                        <div v-if="categoriesLoading" class="text-center py-4">
                            <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                        </div>

                        <div v-else-if="categoriesError" class="text-red-600 text-sm">
                            {{ categoriesError }}
                        </div>

                        <div v-else class="space-y-1">
                            <button
                                @click="handleCategorySelect(null)"
                                :class="[
                                    'w-full text-left px-3 py-2 rounded text-sm transition-colors',
                                    selectedCategoryId === null
                                        ? 'bg-blue-600 text-white font-medium'
                                        : 'bg-gray-50 hover:bg-gray-100 text-gray-700',
                                ]"
                            >
                                Vse kategorije
                            </button>

                            <button
                                v-for="category in categories"
                                :key="category.id"
                                @click="handleCategorySelect(category.id)"
                                :class="[
                                    'w-full text-left px-3 py-2 rounded text-sm transition-colors',
                                    selectedCategoryId === category.id
                                        ? 'bg-blue-600 text-white font-medium'
                                        : 'bg-gray-50 hover:bg-gray-100 text-gray-700',
                                ]"
                            >
                                {{ category.name }}
                            </button>
                        </div>
                    </div>
                </aside>

                <!-- Main Content -->
                <main class="flex-1">
                    <!-- Loading State -->
                    <div v-if="loading" class="text-center py-12">
                        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                        <p class="mt-4 text-gray-600">Nalaganje produktov...</p>
                    </div>

                    <!-- Error State -->
                    <div v-else-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                        {{ error }}
                    </div>

                    <!-- Content -->
                    <div v-else>
                        <div v-if="televisions.length === 0" class="text-center py-12 text-gray-500">
                            <p class="text-lg">V tej kategoriji ni najdenih produktov.</p>
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
                </main>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useTelevisions } from '../composables/useTelevisions';
import { useCategories } from '../composables/useCategories';
import TelevisionCard from '../components/TelevisionCard.vue';
import Pagination from '../components/Pagination.vue';
import { categoryApi } from '../api/categories';
import { televisionApi } from '../api/televisions';

const {
    televisions,
    loading,
    error,
    pagination,
    fetchTelevisions,
} = useTelevisions();

const {
    categories,
    loading: categoriesLoading,
    error: categoriesError,
    selectedCategoryId,
    fetchCategories,
    selectCategory,
} = useCategories();

const handleCategorySelect = (categoryId) => {
    selectCategory(categoryId);
    fetchCategoryProducts(categoryId, 1);
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const fetchCategoryProducts = async (categoryId, page = 1) => {
    loading.value = true;
    error.value = null;

    try {
        let response;
        if (categoryId) {
            response = await categoryApi.getCategoryProducts(categoryId, page);
        } else {
            response = await televisionApi.getTelevisions(page, null);
        }

        if (response.data?.data && response.data?.meta) {
            televisions.value = response.data.data;
            pagination.value = {
                current_page: response.data.meta.current_page ?? 1,
                last_page: response.data.meta.last_page ?? 1,
                per_page: response.data.meta.per_page ?? 20,
                total: response.data.meta.total ?? 0,
                from: response.data.meta.from ?? null,
                to: response.data.meta.to ?? null,
            };
        } else {
            televisions.value = Array.isArray(response.data) ? response.data : [];
        }
    } catch (err) {
        error.value = err.message || 'Failed to fetch products';
    } finally {
        loading.value = false;
    }
};

const handlePageChange = (page) => {
    fetchCategoryProducts(selectedCategoryId.value, page);
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

onMounted(() => {
    fetchCategories();
    fetchCategoryProducts(null, 1);
});
</script>

