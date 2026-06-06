<template>
  <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <defs>
      <mask id="takt-rod-mask">
        <rect width="100" height="100" fill="white" />
        <line x1="50" y1="90" x2="57" y2="8" stroke-width="3.5" stroke-linecap="round" stroke="black" />
      </mask>
    </defs>
    <path
      d="M57.2,24.3 A8,8 0 0,0 42.8,24.3 L15.8,78.4 A8,8 0 0,0 22.9,90 L77.1,90 A8,8 0 0,0 84.2,78.4 Z"
      mask="url(#takt-rod-mask)"
      class="fill-slate-900 dark:fill-slate-100"
    />
    <circle cx="58.5" cy="5" r="5.5" class="fill-(--ui-primary)" />
  </svg>
</template>
