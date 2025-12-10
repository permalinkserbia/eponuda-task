import { ref, computed } from 'vue';
import { televisionApi } from '../api/televisions';

export function useTelevisions() {
    const televisions = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const pagination = ref({
        current_page: 1,
        last_page: 1,
        per_page: 20,
        total: 0,
    });

    const fetchTelevisions = async (page = 1, categoryId = null) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await televisionApi.getTelevisions(page, categoryId);
            
            // Laravel Resource collection with pagination returns: { data: [...], links: {...}, meta: {...} }
            if (response.data && response.data.data) {
                televisions.value = response.data.data;
                
                // Pagination info is in meta
                if (response.data.meta) {
                    pagination.value = {
                        current_page: response.data.meta.current_page || 1,
                        last_page: response.data.meta.last_page || 1,
                        per_page: response.data.meta.per_page || 20,
                        total: response.data.meta.total || 0,
                        from: response.data.meta.from || null,
                        to: response.data.meta.to || null,
                    };
                }
            } else {
                // Fallback if structure is different
                televisions.value = Array.isArray(response.data) ? response.data : [];
            }
        } catch (err) {
            error.value = err.message || 'Failed to fetch televisions';
        } finally {
            loading.value = false;
        }
    };

    const hasNextPage = computed(() => {
        return pagination.value.current_page < pagination.value.last_page;
    });

    const hasPreviousPage = computed(() => {
        return pagination.value.current_page > 1;
    });

    return {
        televisions,
        loading,
        error,
        pagination,
        fetchTelevisions,
        hasNextPage,
        hasPreviousPage,
    };
}

