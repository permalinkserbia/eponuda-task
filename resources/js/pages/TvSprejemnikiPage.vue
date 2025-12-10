<template>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">TV Sprejemniki</h1>

        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Sidebar -->
            <aside class="lg:w-64 flex-shrink-0">
                <div class="bg-white rounded-lg shadow-md p-4">
                    <h2 class="text-xl font-semibold mb-4">Categories</h2>

                    <div v-if="categoriesLoading" class="text-center py-4">
                        <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                    </div>

                    <div v-else-if="categoriesError" class="text-red-600 text-sm">
                        {{ categoriesError }}
                    </div>

                    <div v-else>
                        <button
                            @click="handleCategorySelect(null)"
                            :class="[
                                'w-full text-left px-4 py-2 rounded mb-2',
                                selectedCategoryId === null
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-gray-100 hover:bg-gray-200',
                            ]"
                        >
                            All Categories
                        </button>

                        <button
                            v-for="category in categories"
                            :key="category.id"
                            @click="handleCategorySelect(category.id)"
                            :class="[
                                'w-full text-left px-4 py-2 rounded mb-2',
                                selectedCategoryId === category.id
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-gray-100 hover:bg-gray-200',
                            ]"
                        >
                            {{ category.name }}
                        </button>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="flex-1">
                <div v-if="loading" class="text-center py-12">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                    <p class="mt-4 text-gray-600">Loading products...</p>
                </div>

                <div v-else-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ error }}
                </div>

                <div v-else>
                    <div v-if="televisions.length === 0" class="text-center py-12 text-gray-500">
                        No products found in this category.
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
            </main>
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

        if (response.data) {
            televisions.value = response.data.data;
            pagination.value = {
                current_page: response.data.current_page,
                last_page: response.data.last_page,
                per_page: response.data.per_page,
                total: response.data.total,
                from: response.data.from,
                to: response.data.to,
            };
        }
    } catch (err) {
        error.value = err.message || 'Failed to fetch products';
        console.error('Error fetching products:', err);
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

