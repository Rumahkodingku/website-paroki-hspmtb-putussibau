import '@testing-library/jest-dom/vitest';
import { cleanup } from '@testing-library/react';
import { afterEach } from 'vite-plus/test';

/**
 * Testing Library only cleans up automatically when Vitest globals are
 * enabled, and `globals` is off here on purpose. Without this call, every test
 * file leaves its rendered DOM mounted, and a later assertion like
 * getByRole('button') can match an element from the previous test instead of
 * failing on its own.
 */
afterEach(() => {
    cleanup();
});

/**
 * An in-memory localStorage, installed only when the environment has none.
 *
 * happy-dom runs on Node here, and Node only exposes localStorage when it is
 * started with --localstorage-file; without it `window.localStorage` is
 * undefined rather than empty. hooks/use-appearance.tsx guards on
 * `typeof document === "undefined"` - a check meant for SSR - so in this
 * environment a click on the appearance toggle threw
 * "Cannot read properties of undefined (reading 'setItem')".
 *
 * This is a gap in the environment, not in the hook: a browser always has
 * localStorage, and the hook's guard is the right one for the case it was
 * written for. So the environment is fixed here rather than adding a
 * second, browser-only guard to production code.
 *
 * The stub is deliberately minimal - get, set, remove, clear and a key() that
 * preserves insertion order, because a test that cannot tell a re-assignment
 * from a new key cannot tell two of these states apart.
 */
if (typeof window !== 'undefined' && !window.localStorage) {
    const entries = new Map<string, string>();

    const storage: Storage = {
        get length() {
            return entries.size;
        },
        clear: () => entries.clear(),
        getItem: (key: string) => entries.get(String(key)) ?? null,
        key: (index: number) => [...entries.keys()][index] ?? null,
        removeItem: (key: string) => entries.delete(String(key)),
        setItem: (key: string, value: string) => {
            entries.set(String(key), String(value));
        },
    };

    Object.defineProperty(window, 'localStorage', {
        configurable: true,
        value: storage,
        writable: true,
    });
}
