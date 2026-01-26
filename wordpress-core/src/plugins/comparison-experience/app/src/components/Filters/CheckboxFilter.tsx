interface CheckboxOption<T extends string> {
  value: T;
  label: string;
}

interface CheckboxFilterProps<T extends string> {
  options: CheckboxOption<T>[];
  selectedValues: T[];
  onChange: (value: T) => void;
}

/**
 * Checkbox filter for multi-select options.
 */
export function CheckboxFilter<T extends string>({
  options,
  selectedValues,
  onChange,
}: CheckboxFilterProps<T>) {
  return (
    <div className="space-y-2">
      {options.map((option) => (
        <label
          key={option.value}
          className="flex items-center gap-3 cursor-pointer group"
        >
          <input
            type="checkbox"
            value={option.value}
            checked={selectedValues.includes(option.value)}
            onChange={() => onChange(option.value)}
            className="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
          />
          <span className="text-sm text-gray-700 group-hover:text-gray-900">
            {option.label}
          </span>
        </label>
      ))}
    </div>
  );
}
