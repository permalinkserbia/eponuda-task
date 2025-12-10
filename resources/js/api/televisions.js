import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

export const televisionApi = {
    getTelevisions: (page = 1, categoryId = null) => {
        const params = { page };
        if (categoryId) {
            params.category_id = categoryId;
        }
        return api.get('/televisions', { params });
    },
};

