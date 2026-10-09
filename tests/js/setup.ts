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
 * A link click never reaches the network, and this is why.
 *
 * Inertia's Link issues its XHR before the component resolver runs, so
 * renderWithInertia's throwing resolver cannot stop one. parish-navbar's drawer
 * test clicks a real link, and the request to localhost:3000/profil went out and
 * came back ECONNREFUSED.
 *
 * On main that failure was invisible. The rejection settled after Vitest had
 * already reported its results and was on its way out, so the run exited 0 and
 * printed an unattributed "Error: socket hang up" on the way out with it. The 107
 * additional test cases in the Text suite moved that settlement inside the
 * reporting window, where Vitest counts an unhandled rejection as a failure -
 * so the suite went red on a defect that had been present since the navbar was
 * written. Reproduced on main before the change, not inferred from it.
 *
 * The fix belongs in the environment rather than in production code, for the
 * same reason as the localStorage stub below: a unit test does not navigate, and
 * a browser is never the thing that is wrong here. Nothing in the suite asserts
 * on a response, so an XHR that never fires costs nothing and removes a real
 * network attempt from every render - including on a developer machine, where it
 * could reach a dev server that happens to be running.
 *
 * The resolver's throw in support/inertia.tsx stays. It still catches a
 * navigation that gets past this; it is simply no longer the first line.
 */
if (typeof XMLHttpRequest !== 'undefined') {
    class InertXmlHttpRequest extends XMLHttpRequest {
        /**
         * Reaches OPENED without opening anything.
         *
         * Assigning to `readyState` is not enough - happy-dom defines it as an
         * accessor backed by a private field - so this redefines it as an own
         * property. The constant comes off the constructor rather than off
         * `this`, because on an instance it is undefined.
         */
        override open(): void {
            Object.defineProperty(this, 'readyState', {
                configurable: true,
                value: XMLHttpRequest.OPENED,
                writable: false,
            });
        }

        /**
         * A no-op, because there is no request object to hold a header.
         *
         * Skipping this one is not an option even though the state now reads as
         * OPENED: happy-dom's open() is what builds the private request record,
         * and setRequestHeader writes into it. Letting the real implementation
         * run throws "Cannot read properties of null" instead.
         */
        override setRequestHeader(): void {
            // Intentionally empty.
        }

        override send(): void {
            // Deliberately never dispatches readyState, load or error. The visit
            // simply never settles, which is what a unit test wants: nothing
            // awaits the response, so nothing has to be resolved.
        }
    }

    globalThis.XMLHttpRequest = InertXmlHttpRequest;
}

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
