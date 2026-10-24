<template>
  <Transition
    :name="transitionName"
    mode="out-in"
    appear
    @before-leave="onBeforeLeave"
    @after-enter="onAfterEnter"
  >
    <component :is="component" v-if="component" :key="pageKey" />
  </Transition>
</template>

<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
  component: {
    type: Object,
    required: true,
  },
});

const page = usePage();
const pageKey = computed(() => page.component);
const transitionName = ref('page-slide');

const onBeforeLeave = () => {
  document.body.classList.add('page-transitioning');
};

const onAfterEnter = () => {
  document.body.classList.remove('page-transitioning');
};
</script>

<style>
.page-slide-enter-active,
.page-slide-leave-active {
  transition: all 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.page-slide-enter-from {
  opacity: 0;
  transform: translateX(30px);
}

.page-slide-leave-to {
  opacity: 0;
  transform: translateX(-30px);
}

.page-slide-enter-to,
.page-slide-leave-from {
  opacity: 1;
  transform: translateX(0);
}

.page-transitioning {
  overflow-x: hidden;
}
</style>
