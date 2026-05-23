// global.d.ts
import '@inertiajs/core'

export interface Flash {
    success?: string
    info?: string
    warn?: string
    error?: string
    message?: string
}

declare module "@inertiajs/core" {
    export interface InertiaConfig {
        sharedPageProps: {
            auth: { user: { id: number; name: string } | null };
            appName: string;
        };
        flashDataType: Flash;
        errorValueType: string[];
    }
}
