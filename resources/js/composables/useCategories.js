import { ref, computed } from 'vue';
import { categoryApi } from '../api/categories';

export function useCategories() {
    const categories = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const selectedCategoryId = ref(null);

    const fetchCategories = async () => {
        loading.value = true;
        error.value = null;

        try {
            const response = await categoryApi.getCategories();
            categories.value = response.data.data;
        } catch (err) {
            error.value = err.message || 'Failed to fetch categories';
        } finally {
            loading.value = false;
        }
    };

    const selectCategory = (categoryId) => {
        selectedCategoryId.value = categoryId;
    };

    const clearSelection = () => {
        selectedCategoryId.value = null;
    };

    return {
        categories,
        loading,
        error,
        selectedCategoryId,
        fetchCategories,
        selectCategory,
        clearSelection,
    };
}

