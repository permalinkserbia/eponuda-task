<template>
    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
        <div class="aspect-w-16 aspect-h-9 bg-gray-200">
            <img
                v-if="television.image"
                :src="television.image"
                :alt="television.name"
                class="w-full h-48 object-cover"
                @error="handleImageError"
            />
            <div v-else class="w-full h-48 flex items-center justify-center text-gray-400">
                No Image
            </div>
        </div>
        <div class="p-4">
            <h3 class="text-lg font-semibold mb-2 line-clamp-2 min-h-[3rem]">
                {{ television.name }}
            </h3>
            <div v-if="television.price" class="text-2xl font-bold text-blue-600 mb-2">
                {{ formatPrice(television.price) }} €
            </div>
            <div v-else class="text-gray-500 mb-2">Price not available</div>
            <a
                v-if="television.product_link"
                :href="television.product_link"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-block mt-2 text-blue-600 hover:text-blue-800 font-medium"
            >
                View Product →
            </a>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    television: {
        type: Object,
        required: true,
    },
});

const imageError = ref(false);

const formatPrice = (price) => {
    return new Intl.NumberFormat('sl-SI', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(price);
};

const handleImageError = () => {
    imageError.value = true;
};
</script>

