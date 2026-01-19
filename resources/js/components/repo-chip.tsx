import { X } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

interface RepoChipProps {
    repo: string;
    onRemove?: () => void;
    disabled?: boolean;
}

export function RepoChip({ repo, onRemove, disabled }: RepoChipProps) {
    return (
        <Badge variant="secondary" className="gap-1 py-1 pr-1 pl-2 text-sm">
            <span className="max-w-[200px] truncate">{repo}</span>
            {onRemove && (
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="size-4 rounded-full hover:bg-destructive/20"
                    onClick={onRemove}
                    disabled={disabled}
                    aria-label={`Remove ${repo}`}
                >
                    <X className="size-3" />
                </Button>
            )}
        </Badge>
    );
}
