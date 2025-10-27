<script setup>
import { computed } from 'vue';

const props = defineProps({
  selectedLetter: {
    type: String,
    default: 'Todos',
  },
});

const emit = defineEmits(['select']);

const letters = computed(() =>
  Array.from({ length: 26 }, (_, index) => String.fromCharCode(65 + index)),
);

const normalizedSelected = computed(() =>
  (props.selectedLetter ?? 'Todos').toUpperCase(),
);

const handleSelect = (letter) => {
  emit('select', letter);
};
</script>

<template>
  <div class="filtro-alfabetico">
    <button
      type="button"
      class="filtro-btn"
      :class="{ active: normalizedSelected === 'TODOS' }"
      @click="handleSelect('Todos')"
    >
      Todos
    </button>
    <button
      v-for="letter in letters"
      :key="letter"
      type="button"
      class="filtro-btn"
      :class="{ active: normalizedSelected === letter }"
      @click="handleSelect(letter)"
    >
      {{ letter }}
    </button>
  </div>
</template>

<style scoped>
.filtro-alfabetico {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.filtro-btn {
  border: none;
  background: transparent;
  padding: 0.5rem 0.75rem;
  border-radius: 0.5rem;
  color: #0d6efd;
  font-weight: 600;
  transition: background-color 0.15s ease, color 0.15s ease;
}

.filtro-btn:hover {
  background-color: rgba(13, 110, 253, 0.1);
}

.filtro-btn.active {
  background-color: #0d6efd;
  color: #fff;
}
</style>
