import { cn } from '@/lib/utils';
import { DatePicker } from '@/components/date-picker';
import { TimePicker } from '@/components/time-picker';

export function DateTimePicker({
    value,
    onChange,
    id,
    disabled = false,
    className,
}: {
    value: string;
    onChange: (value: string) => void;
    id?: string;
    disabled?: boolean;
    className?: string;
}) {
    const [datePart, timePart] = value ? value.split('T') : ['', ''];
    const time = (timePart ?? '').slice(0, 5);

    return (
        <div className={cn('flex gap-2', className)}>
            <div className="flex-1">
                <DatePicker
                    id={id}
                    value={datePart ?? ''}
                    disabled={disabled}
                    onChange={(nextDate) => {
                        if (!nextDate) {
                            onChange('');
                            return;
                        }

                        onChange(`${nextDate}T${time || '00:00'}`);
                    }}
                />
            </div>
            <div className="w-28">
                <TimePicker
                    value={time}
                    disabled={disabled || !datePart}
                    onChange={(nextTime) => {
                        if (!datePart) {
                            return;
                        }

                        onChange(`${datePart}T${nextTime}`);
                    }}
                />
            </div>
        </div>
    );
}
