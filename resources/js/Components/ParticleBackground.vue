<template>
  <div class="particle-background" ref="canvasContainer" aria-hidden="true">
    <canvas ref="canvas" class="absolute inset-0 w-full h-full" />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
  particleCount: { type: Number, default: 60 },
  colors: { type: Array, default: () => ['#A7B92B', '#EDD330', '#6F9435', '#F0EAD8'] },
  interactive: { type: Boolean, default: true },
  connectionDistance: { type: Number, default: 120 },
  mouseInfluence: { type: Number, default: 150 },
});

const canvasContainer = ref(null);
const canvas = ref(null);
let ctx = null;
let animationId = null;
let particles = [];
let mouse = { x: null, y: null, radius: 0 };
let width = 0;
let height = 0;
let prefersReducedMotion = false;

class Particle {
  constructor() {
    this.reset();
  }

  reset() {
    this.x = Math.random() * width;
    this.y = Math.random() * height;
    this.size = Math.random() * 2.5 + 0.5;
    this.color = props.colors[Math.floor(Math.random() * props.colors.length)];
    this.speedX = (Math.random() - 0.5) * 0.4;
    this.speedY = (Math.random() - 0.5) * 0.4;
    this.opacity = Math.random() * 0.5 + 0.1;
    this.targetOpacity = this.opacity;
    this.pulsePhase = Math.random() * Math.PI * 2;
    this.pulseSpeed = Math.random() * 0.01 + 0.002;
  }

  update() {
    if (!prefersReducedMotion) {
      this.x += this.speedX;
      this.y += this.speedY;

      if (this.x < 0 || this.x > width) this.speedX *= -1;
      if (this.y < 0 || this.y > height) this.speedY *= -1;

      this.x = Math.max(0, Math.min(width, this.x));
      this.y = Math.max(0, Math.min(height, this.y));
    }

    this.pulsePhase += this.pulseSpeed;
    this.targetOpacity = 0.15 + Math.sin(this.pulsePhase) * 0.1;
    this.opacity += (this.targetOpacity - this.opacity) * 0.05;

    if (props.interactive && mouse.x !== null) {
      const dx = mouse.x - this.x;
      const dy = mouse.y - this.y;
      const distance = Math.sqrt(dx * dx + dy * dy);
      
      if (distance < mouse.radius) {
        const force = (mouse.radius - distance) / mouse.radius;
        const angle = Math.atan2(dy, dx);
        this.x -= Math.cos(angle) * force * 2;
        this.y -= Math.sin(angle) * force * 2;
      }
    }
  }

  draw() {
    ctx.beginPath();
    ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
    ctx.fillStyle = this.color;
    ctx.globalAlpha = this.opacity;
    ctx.fill();
    ctx.globalAlpha = 1;
  }
}

function initParticles() {
  particles = [];
  for (let i = 0; i < props.particleCount; i++) {
    particles.push(new Particle());
  }
}

function drawConnections() {
  for (let i = 0; i < particles.length; i++) {
    for (let j = i + 1; j < particles.length; j++) {
      const dx = particles[i].x - particles[j].x;
      const dy = particles[i].y - particles[j].y;
      const distance = Math.sqrt(dx * dx + dy * dy);

      if (distance < props.connectionDistance) {
        const opacity = (1 - distance / props.connectionDistance) * 0.15;
        ctx.beginPath();
        ctx.moveTo(particles[i].x, particles[i].y);
        ctx.lineTo(particles[j].x, particles[j].y);
        ctx.strokeStyle = '#A7B92B';
        ctx.globalAlpha = opacity;
        ctx.lineWidth = 0.5;
        ctx.stroke();
        ctx.globalAlpha = 1;
      }
    }
  }
}

function animate() {
  if (!ctx || !canvas.value) return;

  ctx.clearRect(0, 0, width, height);

  particles.forEach(p => {
    p.update();
    p.draw();
  });

  if (props.interactive) {
    drawConnections();
  }

  animationId = requestAnimationFrame(animate);
}

function resize() {
  if (!canvas.value || !canvasContainer.value) return;
  
  width = canvasContainer.value.offsetWidth;
  height = canvasContainer.value.offsetHeight;
  
  canvas.value.width = width * window.devicePixelRatio;
  canvas.value.height = height * window.devicePixelRatio;
  canvas.value.style.width = width + 'px';
  canvas.value.style.height = height + 'px';
  
  ctx.scale(window.devicePixelRatio, window.devicePixelRatio);
  
  particles.forEach(p => {
    if (p.x > width) p.x = width;
    if (p.y > height) p.y = height;
  });
}

function handleMouseMove(e) {
  if (!props.interactive || !canvasContainer.value) return;
  
  const rect = canvasContainer.value.getBoundingClientRect();
  mouse.x = e.clientX - rect.left;
  mouse.y = e.clientY - rect.top;
  mouse.radius = props.mouseInfluence;
}

function handleMouseLeave() {
  mouse.x = null;
  mouse.y = null;
  mouse.radius = 0;
}

function handleVisibilityChange() {
  if (document.hidden && animationId) {
    cancelAnimationFrame(animationId);
    animationId = null;
  } else if (!document.hidden && !animationId) {
    animate();
  }
}

onMounted(() => {
  prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  
  if (!canvas.value) return;
  
  ctx = canvas.value.getContext('2d');
  
  resize();
  initParticles();
  animate();
  
  window.addEventListener('resize', resize);
  canvasContainer.value.addEventListener('mousemove', handleMouseMove);
  canvasContainer.value.addEventListener('mouseleave', handleMouseLeave);
  document.addEventListener('visibilitychange', handleVisibilityChange);
});

onUnmounted(() => {
  window.removeEventListener('resize', resize);
  if (canvasContainer.value) {
    canvasContainer.value.removeEventListener('mousemove', handleMouseMove);
    canvasContainer.value.removeEventListener('mouseleave', handleMouseLeave);
  }
  document.removeEventListener('visibilitychange', handleVisibilityChange);
  if (animationId) cancelAnimationFrame(animationId);
});

watch(() => props.particleCount, () => {
  initParticles();
});

watch(() => props.colors, () => {
  particles.forEach(p => {
    p.color = props.colors[Math.floor(Math.random() * props.colors.length)];
  });
});
</script>

<style scoped>
.particle-background {
  position: absolute;
  inset: 0;
  overflow: hidden;
  pointer-events: none;
  z-index: 0;
}

.particle-background canvas {
  display: block;
}

@media (prefers-reduced-motion: reduce) {
  .particle-background {
    display: none;
  }
}
</style>