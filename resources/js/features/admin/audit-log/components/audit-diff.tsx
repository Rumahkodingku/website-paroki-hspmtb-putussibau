import { Text } from '@/components/text';

type Snapshot = Record<string, unknown>;

function isEqual(left: unknown, right: unknown): boolean {
    return JSON.stringify(left) === JSON.stringify(right);
}

function isEmpty(value: unknown): boolean {
    return value === null || value === undefined;
}

function Value({ value }: { value: unknown }) {
    if (isEmpty(value)) {
        return (
            <Text as="span" variant="body-sm" color="muted">
                Kosong
            </Text>
        );
    }

    if (typeof value === 'boolean') {
        return <Text as="span">{value ? 'Ya' : 'Tidak'}</Text>;
    }

    if (typeof value === 'string') {
        return (
            <Text as="span" variant="body-sm" className="break-words">
                {value}
            </Text>
        );
    }

    return (
        <pre className="max-h-64 overflow-auto rounded-md bg-muted/40 p-2 text-xs break-words whitespace-pre-wrap">
            {JSON.stringify(value, null, 2)}
        </pre>
    );
}

function fieldLabel(key: string): string {
    return key.replaceAll('_', ' ');
}

export function AuditDiff({
    before,
    after,
}: {
    before: Snapshot | null;
    after: Snapshot | null;
}) {
    const keys = Array.from(
        new Set([...Object.keys(before ?? {}), ...Object.keys(after ?? {})]),
    ).filter((key) => !isEqual(before?.[key], after?.[key]));

    if (keys.length === 0) {
        return (
            <Text as="p" variant="body-sm" color="muted">
                Tidak ada field yang berubah pada snapshot ini.
            </Text>
        );
    }

    return (
        <div className="scrollbar-thin overflow-x-auto rounded-md border">
            <table className="w-full text-sm">
                <thead>
                    <tr className="border-b bg-muted/40 text-left">
                        <th className="px-3 py-2 font-semibold">Field</th>
                        <th className="px-3 py-2 font-semibold">Sebelum</th>
                        <th className="px-3 py-2 font-semibold">Sesudah</th>
                    </tr>
                </thead>
                <tbody>
                    {keys.map((key) => (
                        <tr key={key} className="border-b last:border-0">
                            <td className="px-3 py-2 align-top font-medium capitalize">
                                {fieldLabel(key)}
                            </td>
                            <td className="min-w-48 px-3 py-2 align-top">
                                <Value value={before?.[key]} />
                            </td>
                            <td className="min-w-48 px-3 py-2 align-top">
                                <Value value={after?.[key]} />
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
