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
            televisions.value = response.data.data;
            pagination.value = {
                current_page: response.data.current_page,
                last_page: response.data.last_page,
                per_page: response.data.per_page,
                total: response.data.total,
                from: response.data.from,
                to: response.data.to,
            };
        } catch (err) {
            error.value = err.message || 'Failed to fetch televisions';
            console.error('Error fetching televisions:', err);
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

