import * as React from 'react';
import { Clock } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';

const HOURS = Array.from({ length: 24 }, (_, index) =>
    String(index).padStart(2, '0'),
);

const MINUTES = Array.from({ length: 60 }, (_, index) =>
    String(index).padStart(2, '0'),
);

export function TimePicker({
    value,
    onChange,
    placeholder = 'Pilih waktu',
    id,
    disabled = false,
    className,
    ariaLabel,
}: {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    id?: string;
    disabled?: boolean;
    className?: string;
    ariaLabel?: string;
}) {
    const [hourPart, minutePart] = value ? value.split(':') : ['', ''];

    const hour = hourPart ?? '';
    const minute = minutePart ?? '';

    const hourRef = React.useRef(hour || '00');
    const minuteRef = React.useRef(minute || '00');

    React.useEffect(() => {
        if (hour) {
            hourRef.current = hour;
        }
    }, [hour]);

    React.useEffect(() => {
        if (minute) {
            minuteRef.current = minute;
        }
    }, [minute]);

    const label = value ? value : placeholder;

    return (
        <Popover>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant="outline"
                    disabled={disabled}
                    aria-label={ariaLabel}
                    className={cn(
                        'w-full justify-start text-left font-normal',
                        !value && 'text-muted-foreground',
                        className,
                    )}
                >
                    <Clock className="mr-2 h-4 w-4" />
                    {label}
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-56 p-3" align="start">
                <div className="flex items-center gap-2">
                    <Select
                        value={hour}
                        onValueChange={(next) => {
                            hourRef.current = next;
                            onChange(`${next}:${minuteRef.current}`);
                        }}
                    >
                        <SelectTrigger className="w-[90px]">
                            <SelectValue placeholder="Jam" />
                        </SelectTrigger>
                        <SelectContent className="max-h-60">
                            {HOURS.map((item) => (
                                <SelectItem key={item} value={item}>
                                    {item}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <span className="text-muted-foreground">:</span>
                    <Select
                        value={minute}
                        onValueChange={(next) => {
                            minuteRef.current = next;
                            onChange(`${hourRef.current}:${next}`);
                        }}
                    >
                        <SelectTrigger className="w-[90px]">
                            <SelectValue placeholder="Menit" />
                        </SelectTrigger>
                        <SelectContent className="max-h-60">
                            {MINUTES.map((item) => (
                                <SelectItem key={item} value={item}>
                                    {item}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            </PopoverContent>
        </Popover>
    );
}
