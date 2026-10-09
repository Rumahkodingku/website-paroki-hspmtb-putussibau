import { AlertCircleIcon } from 'lucide-react';
import { Text } from '@/components/text';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

export default function AlertError({
    errors,
    title,
}: {
    errors: string[];
    title?: string;
}) {
    return (
        <Alert variant="destructive">
            <AlertCircleIcon />
            <AlertTitle>{title || 'Something went wrong.'}</AlertTitle>
            <AlertDescription>
                {/*
                    `caption`, not `body`. AlertDescription already sets
                    text-sm in the shadcn primitive, and this is a list of short
                    validation strings read at a glance rather than prose - the
                    14px token is the same size and carries the design system's
                    own tracking, rather than Tailwind's scale step.
                */}
                <Text
                    as="ul"
                    variant="caption"
                    className="list-inside list-disc"
                >
                    {Array.from(new Set(errors)).map((error, index) => (
                        <li key={index}>{error}</li>
                    ))}
                </Text>
            </AlertDescription>
        </Alert>
    );
}
