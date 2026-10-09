import { useState } from 'react';
import { IMaskInput } from 'react-imask';
import { cn } from '@/lib/utils';

type MaskedInputProps = {
    id?: string;
    name: string;
    mask: string;
    defaultValue?: string;
    placeholder?: string;
    required?: boolean;
    autoComplete?: string;
    inputMode?: React.HTMLAttributes<HTMLInputElement>['inputMode'];
    className?: string;
};

const inputClassName =
    'border-input file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive';

/**
 * Shows a masked value for typing guidance while submitting the unmasked
 * digits under `name`, since Inertia's uncontrolled Form reads native inputs.
 */
export function MaskedInput({
    id,
    name,
    mask,
    defaultValue = '',
    placeholder,
    required,
    autoComplete,
    inputMode,
    className,
}: MaskedInputProps) {
    const [unmaskedValue, setUnmaskedValue] = useState(defaultValue);

    return (
        <>
            <IMaskInput
                mask={mask}
                unmask
                defaultValue={defaultValue}
                onAccept={(value: string) => setUnmaskedValue(value)}
                id={id}
                placeholder={placeholder}
                required={required}
                autoComplete={autoComplete}
                inputMode={inputMode}
                className={cn(inputClassName, className)}
            />
            <input type="hidden" name={name} value={unmaskedValue} />
        </>
    );
}
