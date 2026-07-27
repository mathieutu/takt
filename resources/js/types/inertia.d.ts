/* eslint-disable ts/consistent-type-definitions */
// global.d.ts
import '@inertiajs/core'

export type Flash = {
  success?: string,
  info?: string,
  warn?: string,
  error?: string,
  message?: string,
}

declare module '@inertiajs/core' {
  export interface InertiaConfig {
    sharedPageProps: {
      auth: { user: { id: string, name: string, email: string, avatar: string } | null, isDemo: boolean },
      updatedAt: string,
    },
    flashDataType: Flash,
    errorValueType: string,
  }
}
