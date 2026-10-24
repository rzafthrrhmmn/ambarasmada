<template>
  <Transition
    :name="transitionName"
    :mode="mode"
    :appear="appear"
  >
    <component :is="tag" v-if="show" :class="wrapperClass">
      <slot />
    </component>
  </Transition>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  tag: {
    type: String,
    default: 'div',
  },
  mode: {
    type: String,
    default: 'out-in',
  },
  appear: {
    type: Boolean,
    default: true,
  },
  type: {
    type: String,
    default: 'slide-left',
  },
  wrapperClass: {
    type: String,
    default: '',
  },
});

const transitionName = ref('section-' + props.type);
const show = ref(true);
</script>

<style>
.section-slide-left-enter-active,
.section-slide-left-leave-active {
  transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.section-slide-left-enter-from {
  opacity: 0;
  transform: translateX(40px);
}

.section-slide-left-leave-to {
  opacity: 0;
  transform: translateX(-40px);
}

.section-slide-left-enter-to,
.section-slide-left-leave-from {
  opacity: 1;
  transform: translateX(0);
}

.section-slide-up-enter-active,
.section-slide-up-leave-active {
  transition: all 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.section-slide-up-enter-from {
  opacity: 0;
  transform: translateY(30px);
}

.section-slide-up-leave-to {
  opacity: 0;
  transform: translateY(-30px);
}

.section-slide-up-enter-to,
.section-slide-up-leave-from {
  opacity: 1;
  transform: translateY(0);
}

.section-fade-scale-enter-active,
.section-fade-scale-leave-active {
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.section-fade-scale-enter-from {
  opacity: 0;
  transform: scale(0.95);
}

.section-fade-scale-leave-to {
  opacity: 0;
  transform: scale(1.05);
}

.section-fade-scale-enter-to,
.section-fade-scale-leave-from {
  opacity: 1;
  transform: scale(1);
}
</style>
