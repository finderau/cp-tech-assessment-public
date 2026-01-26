interface RadioOption<T extends string> {
  value: T;
  label: string;
}

interface RadioFilterProps<T extends string> {
  name: string;
  options: RadioOption<T>[];
  value: T;
  onChange: (value: T) => void;
}

/**
 * Radio button filter for single-select options.
 */
export function RadioFilter<T extends string>({
  name,
  options,
  value,
  onChange,
}: RadioFilterProps<T>) {
  return (
    <div className="space-y-2">
      {options.map((option) => (
        <label
          key={option.value}
          className="flex items-center gap-3 cursor-pointer group"
        >
          <input
            type="radio"
            name={name}
            value={option.value}
            checked={value === option.value}
            onChange={() => onChange(option.value)}
            className="w-4 h-4 text-primary-600 border-gray-300 focus:ring-primary-500"
          />
          <span className="text-sm text-gray-700 group-hover:text-gray-900">
            {option.label}
          </span>
        </label>
      ))}
    </div>
  );
}
