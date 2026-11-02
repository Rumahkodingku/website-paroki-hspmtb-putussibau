import * as React from 'react';
import { CalendarIcon } from 'lucide-react';
import { format } from 'date-fns';
import { id as idLocale } from 'date-fns/locale';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

function toDateString(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function parseDate(value: string): Date | undefined {
    if (value.trim() === '') {
        return undefined;
    }

    const parsed = new Date(`${value}T00:00:00`);

    return Number.isNaN(parsed.getTime()) ? undefined : parsed;
}

export function DatePicker({
    value,
    onChange,
    placeholder = 'Pilih tanggal',
    id,
    disabled = false,
    className,
}: {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    id?: string;
    disabled?: boolean;
    className?: string;
}) {
    const selected = parseDate(value);

    return (
        <Popover>
            <PopoverTrigger asChild>
                <button
                    id={id}
                    type="button"
                    disabled={disabled}
                    className={cn(
                        'flex h-11 w-full items-center gap-2 rounded-md border border-input bg-background px-3 py-2 text-left text-base font-normal shadow-none transition-[color,box-shadow] outline-none md:text-sm',
                        'focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50',
                        'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50',
                        !selected && 'text-muted-foreground',
                        className,
                    )}
                >
                    <CalendarIcon className="size-4 shrink-0" />
                    {selected
                        ? format(selected, 'd MMMM yyyy', { locale: idLocale })
                        : placeholder}
                </button>
            </PopoverTrigger>
            <PopoverContent className="w-auto p-0" align="start">
                <Calendar
                    mode="single"
                    selected={selected}
                    onSelect={(date) =>
                        onChange(date ? toDateString(date) : '')
                    }
                />
            </PopoverContent>
        </Popover>
    );
}
