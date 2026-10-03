<template>
  <label class="block">
    <span class="text-xs font-medium text-[#d4dc9a]">
      {{ label }}<span v-if="required" class="text-[#EDD330]"> *</span>
    </span>

    <select
      v-if="type === 'select'"
      :value="modelValue ?? ''"
      @change="$emit('update:modelValue', selectValue($event.target.value))"
      class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"
    >
      <option v-for="option in normalizedOptions" :key="String(option.value)" :value="option.value">
        {{ option.label }}
      </option>
    </select>

    <textarea
      v-else-if="type === 'textarea'"
      :value="modelValue"
      :rows="rows"
      :placeholder="placeholder"
      @input="$emit('update:modelValue', $event.target.value)"
      class="mt-1 w-full resize-y rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"
    ></textarea>

    <input
      v-else
      :value="modelValue ?? ''"
      :type="type"
      :required="required"
      :placeholder="placeholder"
      @input="$emit('update:modelValue', $event.target.value)"
      class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"
    />

    <p v-if="error" class="mt-1 text-xs text-[#ef4419]">{{ error }}</p>
  </label>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: { type: [String, Number, null], default: '' },
  label: { type: String, required: true },
  type: { type: String, default: 'text' },
  options: { type: Array, default: () => [] },
  error: { type: String, default: '' },
  required: { type: Boolean, default: false },
  rows: { type: [String, Number], default: 4 },
  placeholder: { type: String, default: '' },
});

const normalizedOptions = computed(() =>  props.options.map((option) =>
    typeof option === 'object' && option !== null
      ? { value: option.value ?? null, label: option.label ?? String(option.value ?? '-') }
      : { value: option, label: String(option) }
  )
);

/**
 * Nilai <select> selalu string. Kembalikan nilai asli dari daftar opsi supaya
 * id tetap angka dan perbandingan dengan data server tidak gagal.
 */
function selectValue(raw) {
  if (raw === '') {
    return null;
  }

  const match = normalizedOptions.value.find((option) => String(option.value) === raw);

  return match ? match.value : raw;
}
</script>
