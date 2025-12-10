<template>
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-md transition-all duration-200 flex flex-col h-full">
        <a
            v-if="television.product_link"
            :href="television.product_link"
            target="_blank"
            rel="noopener noreferrer"
            class="block"
        >
            <div class="aspect-square bg-gray-100 relative overflow-hidden">
                <img
                    v-if="television.image && !imageError"
                    :src="television.image"
                    :alt="television.name"
                    class="w-full h-full object-contain p-2"
                    @error="handleImageError"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-xs">
                    Brez slike
                </div>
            </div>
        </a>
        <div v-else class="aspect-square bg-gray-100 relative overflow-hidden">
            <img
                v-if="television.image && !imageError"
                :src="television.image"
                :alt="television.name"
                class="w-full h-full object-contain p-2"
                @error="handleImageError"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-xs">
                Brez slike
            </div>
        </div>
        <div class="p-3 flex-1 flex flex-col">
            <h3 class="text-sm font-medium text-gray-900 mb-2 line-clamp-2 min-h-[2.5rem] leading-tight">
                {{ television.name }}
            </h3>
            <div class="mt-auto">
                <div v-if="television.price" class="text-lg font-bold text-gray-900 mb-1">
                    {{ formatPrice(television.price) }} €
                </div>
                <div v-else class="text-sm text-gray-500 mb-1">Cena ni na voljo</div>
                <a
                    v-if="television.product_link"
                    :href="television.product_link"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-block w-full text-center text-xs text-blue-600 hover:text-blue-800 font-medium py-1 mt-2"
                >
                    Poglej ponudbo →
                </a>
            </div>
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

