<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm">
      <div class="bg-[#335233] rounded-2xl border border-[#6F9435] w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col">
        <div class="flex items-center justify-between p-4 border-b border-[#6F9435]/30">
          <h3 class="text-lg font-semibold text-[#f0ead8]">Crop Foto Profil</h3>
          <p class="text-xs text-[#8fa06a]">Aspect ratio 3:4 (KTA format)</p>
          <button @click="close" class="text-[#8fa06a] hover:text-[#EDD330] transition">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        
        <div class="p-4 flex-1 overflow-auto flex items-center justify-center bg-[#263D26] min-h-[300px]">
          <div ref="cropperContainer" class="w-full max-w-[600px]">
            <img ref="cropperImage" :src="imageUrl" alt="Crop preview" />
          </div>
        </div>

        <div class="flex items-center justify-between p-4 border-t border-[#6F9435]/30 gap-3">
          <div class="flex gap-2">
            <button @click="rotate(-90)" class="px-3 py-1.5 rounded-lg border border-[#6F9435] text-sm text-[#d4dc9a] hover:bg-[#6F9435]/20 transition" :disabled="cropping">
              <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.19 6.03A10.92 10.92 0 0121 12c0 2.06-.65 3.94-1.81 5.47L12 10.5V6.03z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v2.5m0 0l-7.5 4.5"/></svg>
              Putar
            </button>
            <button @click="reset" class="px-3 py-1.5 rounded-lg border border-[#6F9435] text-sm text-[#d4dc9a] hover:bg-[#6F9435]/20 transition" :disabled="cropping">
              Reset
            </button>
          </div>
          <div class="flex gap-2 ml-auto">
            <button @click="close" class="px-4 py-2 rounded-lg border border-[#6F9435] text-sm font-medium text-[#d4dc9a] hover:bg-[#6F9435]/20 transition" :disabled="cropping">
              Batal
            </button>
            <button @click="confirmCrop" class="px-4 py-2 rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] text-sm font-semibold text-white hover:brightness-110 transition" :disabled="cropping">
              <span v-if="cropping" class="flex items-center gap-2"><svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>Memproses...</span>
              <span v-else>Gunakan Foto</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue';
import Cropper from 'cropperjs';

defineProps({
  show: Boolean,
  imageUrl: String,
});

const emit = defineEmits(['close', 'crop']);

const cropperContainer = ref(null);
const cropperImage = ref(null);
let cropper = null;
const cropping = ref(false);

const initCropper = () => {
  if (cropper) cropper.destroy();
  
  const img = cropperImage.value;
  if (!img || !img.complete) {
    img.onload = () => createCropper();
    return;
  }
  createCropper();
};

const createCropper = () => {
  cropper = new Cropper(cropperImage.value, {
    aspectRatio: 3 / 4,
    viewMode: 2,
    autoCropArea: 1,
    responsive: true,
    restore: true,
    guides: true,
    center: true,
    highlight: true,
    cropBoxMovable: true,
    cropBoxResizable: false,
    toggleDragModeOnDblclick: false,
    background: true,
    minContainerWidth: 300,
    minContainerHeight: 200,
    ready() {
      this.crop();
    },
  });
};

const destroyCropper = () => {
  if (cropper) {
    cropper.destroy();
    cropper = null;
  }
};

watch(() => props.show, (val) => {
  if (val && props.imageUrl) {
    nextTick(() => initCropper());
  } else {
    destroyCropper();
  }
});

watch(() => props.imageUrl, (val) => {
  if (props.show && val && cropper) {
    const img = cropperImage.value;
    img.onload = () => {
      if (cropper) cropper.replace(val);
    };
  }
});

onUnmounted(() => destroyCropper());

const rotate = (deg) => {
  if (cropper) cropper.rotate(deg);
};

const reset = () => {
  if (cropper) cropper.reset();
};

const confirmCrop = async () => {
  if (!cropper) return;
  
  cropping.value = true;
  
  try {
    const canvas = cropper.getCroppedCanvas({
      width: 300,
      height: 400,
      imageSmoothingEnabled: true,
      imageSmoothingQuality: 'high',
    });
    
    canvas.toBlob((blob) => {
      const file = new File([blob], 'profile-crop.jpg', { type: 'image/jpeg', lastModified: Date.now() });
      emit('crop', file);
      emit('close');
    }, 'image/jpeg', 0.9);
  } finally {
    cropping.value = false;
  }
};

const close = () => {
  destroyCropper();
  emit('close');
};
</script>

<style scoped>
/* Cropper.js inline styles - since cropperjs doesn't bundle CSS anymore */
.cropper-container {
  width: 100%;
  height: auto;
  direction: ltr;
  font-size: 0;
  line-height: 0;
  position: relative;
  touch-action: none;
  user-select: none;
}

.cropper-container img {
  display: block;
  width: 100%;
  height: auto;
  min-width: 0 !important;
  min-height: 0 !important;
  max-width: none !important;
  max-height: none !important;
  image-orientation: 0deg;
}

.cropper-wrap-box,
.cropper-crop-box {
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
}

.cropper-wrap-box {
  overflow: hidden;
}

.cropper-crop-box {
  border: 2px solid #EDD330;
  outline: 1px solid #fff;
}

.cropper-view-box {
  outline: 1px solid #39f;
  overflow: hidden;
}

.cropper-view-box,
.cropper-face {
  border-radius: 0;
}

.cropper-dashed.dashed-h::before,
.cropper-dashed.dashed-v::before {
  position: absolute;
  display: block;
  width: 0;
  height: 0;
  border: 0 dashed #eee;
  content: " ";
}

.cropper-dashed.dashed-h::before {
  top: 33.33333333%;
  left: 0;
  width: 100%;
  border-top-width: 1px;
}

.cropper-dashed.dashed-v::before {
  top: 0;
  left: 33.33333333%;
  height: 100%;
  border-left-width: 1px;
}

.cropper-center {
  position: absolute;
  top: 50%;
  left: 50%;
  display: block;
  width: 0;
  height: 0;
  opacity: 0.75;
  transform: translate(-50%, -50%);
}

.cropper-center::before,
.cropper-center::after {
  position: absolute;
  display: block;
  width: 0;
  height: 0;
  background-color: #EDD330;
  content: " ";
}

.cropper-center::before {
  top: 0;
  left: -3px;
  width: 7px;
  height: 1px;
}

.cropper-center::after {
  top: -3px;
  left: 0;
  width: 1px;
  height: 7px;
}

.cropper-face {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: #fff;
  opacity: 0.1;
  cursor: move;
}

.cropper-line.line-e,
.cropper-line.line-n,
.cropper-line.line-w,
.cropper-line.line-s {
  position: absolute;
  display: block;
  background-color: #EDD330;
  opacity: 0.9;
  filter: alpha(opacity=90);
}

.cropper-line.line-e {
  top: 0;
  right: -3px;
  width: 5px;
  height: 100%;
  cursor: ew-resize;
}

.cropper-line.line-n {
  top: -3px;
  left: 0;
  width: 100%;
  height: 5px;
  cursor: ns-resize;
}

.cropper-line.line-w {
  top: 0;
  left: -3px;
  width: 5px;
  height: 100%;
  cursor: ew-resize;
}

.cropper-line.line-s {
  bottom: -3px;
  left: 0;
  width: 100%;
  height: 5px;
  cursor: ns-resize;
}

.cropper-point.point-e,
.cropper-point.point-n,
.cropper-point.point-w,
.cropper-point.point-s,
.cropper-point.point-ne,
.cropper-point.point-nw,
.cropper-point.point-sw,
.cropper-point.point-se {
  position: absolute;
  display: block;
  width: 10px;
  height: 10px;
  opacity: 0.95;
  background-color: #EDD330;
  filter: alpha(opacity=95);
}

.cropper-point.point-e {
  top: 50%;
  right: -3px;
  margin-top: -3px;
  cursor: ew-resize;
}

.cropper-point.point-n {
  top: -3px;
  left: 50%;
  margin-left: -3px;
  cursor: ns-resize;
}

.cropper-point.point-w {
  top: 50%;
  left: -3px;
  margin-top: -3px;
  cursor: ew-resize;
}

.cropper-point.point-s {
  bottom: -3px;
  left: 50%;
  margin-left: -3px;
  cursor: ns-resize;
}

.cropper-point.point-ne {
  top: -3px;
  right: -3px;
  cursor: nesw-resize;
}

.cropper-point.point-nw {
  top: -3px;
  left: -3px;
  cursor: nwse-resize;
}

.cropper-point.point-sw {
  bottom: -3px;
  left: -3px;
  cursor: nesw-resize;
}

.cropper-point.point-se {
  right: -3px;
  bottom: -3px;
  cursor: nwse-resize;
}

.cropper-invisible {
  opacity: 0;
}

.cropper-bg {
  background-image: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQAQMAAAAlPW0iAAAABlBMVEUAAAD///+l2Z/dAAAAM0lEQVR4nGP4/5/h/1+G/58ZDrAz3D/McH8yw83NDDeNGe4Ug9C9zwz3gVLMDA/A6P9/AFGGFyjOXZtQAAAAAElFTkSuQmCC");
}

.cropper-hidden {
  display: none !important;
}

.cropper-move {
  cursor: move;
}

.cropper-crop {
  cursor: crosshair;
}

.cropper-disabled .cropper-face,
.cropper-disabled .cropper-line,
.cropper-disabled .cropper-point {
  cursor: not-allowed;
}

.cropper-crop-box {
  border: 2px solid #EDD330 !important;
}

.cropper-center-touch::before,
.cropper-center-touch::after {
  background-color: #EDD330 !important;
}

.cropper-point {
  background-color: #EDD330 !important;
}

.cropper-line {
  background-color: #EDD330 !important;
}

.cropper-view-box {
  border: 1px solid #EDD330 !important;
}
</style>