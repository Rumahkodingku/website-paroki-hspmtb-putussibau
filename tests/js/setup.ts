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
