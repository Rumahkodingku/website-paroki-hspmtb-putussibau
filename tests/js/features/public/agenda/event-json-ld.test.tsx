import { describe, expect, it } from 'vitest';

import { serializeJsonLd } from '@/lib/json-ld';

describe('serializeJsonLd', () => {
    it('cannot close the script element from a value', () => {
        const title = '</script><img src=x onerror=alert(1)>';

        const json = serializeJsonLd({ name: title });

        expect(json).not.toContain('</script>');
        expect(JSON.parse(json).name).toBe(title);
    });

    it('keeps Indonesian text and quotes valid JSON', () => {
        const data = {
            name: 'Misa "Syukur" — Paroki HSPMTB',
            description: 'Doa bersama & syukur.',
        };

        expect(JSON.parse(serializeJsonLd(data))).toEqual(data);
    });
});
