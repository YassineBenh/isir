import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { type DigestFrequency } from '@/types';

const DAYS_OF_WEEK = [
    { value: '0', label: 'Sunday' },
    { value: '1', label: 'Monday' },
    { value: '2', label: 'Tuesday' },
    { value: '3', label: 'Wednesday' },
    { value: '4', label: 'Thursday' },
    { value: '5', label: 'Friday' },
    { value: '6', label: 'Saturday' },
];

const HOURS = Array.from({ length: 24 }, (_, i) => {
    const hour = i.toString().padStart(2, '0');
    const ampm = i < 12 ? 'AM' : 'PM';
    const hour12 = i === 0 ? 12 : i > 12 ? i - 12 : i;
    return { value: `${hour}:00`, label: `${hour12}:00 ${ampm}` };
});

interface ScheduleSelectorProps {
    frequency: DigestFrequency;
    sendTime: string;
    sendDayOfWeek: number | null;
    timezone: string;
    timezones: string[];
    onFrequencyChange: (value: DigestFrequency) => void;
    onSendTimeChange: (value: string) => void;
    onSendDayOfWeekChange: (value: number | null) => void;
    onTimezoneChange: (value: string) => void;
    errors?: {
        frequency?: string;
        send_time?: string;
        send_day_of_week?: string;
        timezone?: string;
    };
}

export function ScheduleSelector({
    frequency,
    sendTime,
    sendDayOfWeek,
    timezone,
    timezones,
    onFrequencyChange,
    onSendTimeChange,
    onSendDayOfWeekChange,
    onTimezoneChange,
    errors,
}: ScheduleSelectorProps) {
    return (
        <div className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="frequency">Frequency</Label>
                    <Select
                        value={frequency}
                        onValueChange={(value) =>
                            onFrequencyChange(value as DigestFrequency)
                        }
                    >
                        <SelectTrigger id="frequency">
                            <SelectValue placeholder="Select frequency" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="daily">Daily</SelectItem>
                            <SelectItem value="weekly">Weekly</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors?.frequency} />
                </div>

                {frequency === 'weekly' && (
                    <div className="grid gap-2">
                        <Label htmlFor="send_day_of_week">Day of Week</Label>
                        <Select
                            value={
                                sendDayOfWeek !== null
                                    ? sendDayOfWeek.toString()
                                    : ''
                            }
                            onValueChange={(value) =>
                                onSendDayOfWeekChange(parseInt(value, 10))
                            }
                        >
                            <SelectTrigger id="send_day_of_week">
                                <SelectValue placeholder="Select day" />
                            </SelectTrigger>
                            <SelectContent>
                                {DAYS_OF_WEEK.map((day) => (
                                    <SelectItem
                                        key={day.value}
                                        value={day.value}
                                    >
                                        {day.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors?.send_day_of_week} />
                    </div>
                )}
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="send_time">Send Time</Label>
                    <Select value={sendTime} onValueChange={onSendTimeChange}>
                        <SelectTrigger id="send_time">
                            <SelectValue placeholder="Select time" />
                        </SelectTrigger>
                        <SelectContent>
                            {HOURS.map((hour) => (
                                <SelectItem key={hour.value} value={hour.value}>
                                    {hour.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors?.send_time} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="timezone">Timezone</Label>
                    <Select value={timezone} onValueChange={onTimezoneChange}>
                        <SelectTrigger id="timezone">
                            <SelectValue placeholder="Select timezone" />
                        </SelectTrigger>
                        <SelectContent>
                            {timezones.map((tz) => (
                                <SelectItem key={tz} value={tz}>
                                    {tz.replace(/_/g, ' ')}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors?.timezone} />
                </div>
            </div>
        </div>
    );
}
