import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

export const categoryApi = {
    getCategories: () => {
        return api.get('/tv-categories');
    },
    getCategoryProducts: (categoryId, page = 1) => {
        return api.get(`/tv-categories/${categoryId}/products`, { params: { page } });
    },
};

