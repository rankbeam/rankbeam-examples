import { defineConfig, devices } from '@playwright/test'

/**
 * Each project targets one running reference app by base URL (set by CI or, for
 * a local single-stack run, defaulted to a conventional port). The contract is
 * renderer-agnostic, so a single browser engine (Chromium, pinned by the
 * @playwright/test version in package.json) is enough to prove it — adding
 * WebKit/Firefox would multiply cost without testing a new contract clause.
 *
 * `retries: 0` is deliberate: a Rendering Contract assertion must be
 * deterministic. A flaky contract test is a failing contract test, not
 * something to paper over with retries.
 */
const url = (envKey: string, fallback: string) => process.env[envKey] ?? fallback

export default defineConfig({
  testDir: './tests',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: process.env.CI ? [['github'], ['html', { open: 'never' }]] : [['list']],
  use: {
    trace: 'on-first-retry',
    // No animations / consistent viewport for stable head reads.
    viewport: { width: 1280, height: 720 },
  },
  projects: [
    {
      name: 'oracle',
      testMatch: /oracle\.spec\.ts/,
    },
    {
      name: 'blade',
      testMatch: /blade\.spec\.ts/,
      use: { ...devices['Desktop Chrome'], baseURL: url('BLADE_URL', 'http://127.0.0.1:8210') },
    },
    {
      name: 'inertia-vue',
      testMatch: /inertia\.spec\.ts/,
      use: { ...devices['Desktop Chrome'], baseURL: url('INERTIA_VUE_URL', 'http://127.0.0.1:8211') },
      metadata: {
        ssrUrlEnv: 'INERTIA_VUE_SSR_URL',
        csrUrlEnv: 'INERTIA_VUE_CSR_URL',
      },
    },
    {
      name: 'inertia-react',
      testMatch: /inertia\.spec\.ts/,
      use: { ...devices['Desktop Chrome'], baseURL: url('INERTIA_REACT_URL', 'http://127.0.0.1:8212') },
      metadata: {
        ssrUrlEnv: 'INERTIA_REACT_SSR_URL',
        csrUrlEnv: 'INERTIA_REACT_CSR_URL',
      },
    },
    {
      name: 'inertia-svelte',
      testMatch: /inertia\.spec\.ts/,
      use: { ...devices['Desktop Chrome'], baseURL: url('INERTIA_SVELTE_URL', 'http://127.0.0.1:8213') },
      metadata: {
        ssrUrlEnv: 'INERTIA_SVELTE_SSR_URL',
        csrUrlEnv: 'INERTIA_SVELTE_CSR_URL',
      },
    },
    {
      name: 'livewire',
      testMatch: /livewire\.spec\.ts/,
      use: { ...devices['Desktop Chrome'], baseURL: url('LIVEWIRE_URL', 'http://127.0.0.1:8214') },
    },
  ],
})
