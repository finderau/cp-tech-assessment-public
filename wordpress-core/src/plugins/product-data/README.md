# Product Data

## Overview
This plugin serves product data through a REST API. The API accepts an optional request body that allows API users to `filter`, `sort`, and `pagination`.

## Tech Design
The Plugin reads data from JSON files in the `data` folder:
- `data.json` - product data
- `providers.json` - provider data.


## API Interfaces

### Products Endpoint
`POST /wp-json/api/v1/product-data`

Returns filtered, sorted, and paginated product data.

**Request Body (optional):**
```json
{
    "filters": [],
    "sort": [],
    "pagination": {}
}
```

**Response:**
```json
{
    "products": [...],
    "totalCount": 500,
    "offset": 0,
    "pageSize": 20
}
```

### Providers Endpoint
`GET /wp-json/api/v1/providers`

Returns all available providers.

**Response:**
```json
{
    "providers": [
        { "id": "provider-id-1", "providerName": "Provider Name" }
    ],
    "totalCount": 9
}
```

---
### Filters
`filters` accepts an array of filter objects containing a `field`, `comparator`, and `value`.
```
{
    "field": string,
    "comparator: enum,
    "value": string | number | boolean
}
```
- Filter combinations are combined with an `AND` operator.

#### Available comparators
- eq
- gt
- gte
- lt
- lte
- like
- notlike
- contains
- notcontains
- noteq
- empty
- notempty
- in
- notin
- within

#### Supported filters
1. Hospital treatments - For Hospital/Combined products, checks for products that have a treatment under details -> Hospital -> attributes matching the `treatmentName` and has `isAvailable` true.
    ```
    {
        "field": "hospitalTreatments",
        "comparator": "contains",
        "value" : "${treatmentName}"
    }
    ```
2. Extras treatments - For Extras/Combined products, checks for products that have a treatment under details -> Extras -> attributes matching the `treatmentName` and has `isAvailable` true.
```
    {
        "field": "extrasTreatments",
        "comparator": "contains",
        "value" : "${treatmentName}"
    }
```
3. Hospital cover - For Hospital/Combined products, checks if `Hospital tier` under details -> Hospital -> attributes matches the `coverValue` provided.
```
    {
        "field": "hospitalCover",
        "comparator": "like",
        "value" : "${coverValue}"
    }
```

5. Cover type - Checks details array, if coverType = Hospital, returns any product with a `Hospital` title and subtitle with > 0 included treatments. if coverType = Extras, returns any product with an `Extras` title and subtitle with > 0 included treatments. if coverType = Combined, returns product that have both Hospital and Extras covers.
```
    {
        "field": "coverType",
        "comparator": "contains",
        "value" : "${coverType}"
    }
```

6. Provider - Returns products under the selected provider
```
    {
        "field": "providerName",
        "comparator": "in",
        "value" : ["${providerName}","${providerName2}","${providerName3}"]
    }
```


### Sort
`sort` accepts an array of sort configurations. Each sort configuration contains a field and a direction (either `ASCENDING` or `DESCENDING`)

```
{
    "field": string,
    "direction": "ASCENDING" | "DESCENDING"
}
```

### Pagination
`pagination` accepts a pagination object containing offset and pagesize.
```
{
    "offset": number,
    "pageSize": number
}
```

## WP Hooks

### Actions

| Hook | Description | Parameters |
|------|-------------|------------|
| `finder/product_data/initialized` | Fires after plugin initialization | `Plugin $plugin` |
| `finder/product_data/enabled` | Fires when plugin is enabled | - |
| `finder/product_data/disabled` | Fires when plugin is disabled | - |
| `finder/product_data/activated` | Fires on plugin activation | - |
| `finder/product_data/deactivated` | Fires on plugin deactivation | - |
| `finder/product_data/before_register_rest_routes` | Fires before REST routes registration | - |
| `finder/product_data/after_register_rest_routes` | Fires after REST routes registration | - |
| `finder/product_data/admin/register_settings` | Fires after settings registration | `string $pageSlug`, `string $optionGroup` |

### Filters

| Hook | Description | Parameters |
|------|-------------|------------|
| `finder/product_data/capabilities` | Modify plugin capabilities | `array $capabilities` |
| `finder/product_data/get_product_id` | Filter product ID before retrieval | `int $productId` |
| `finder/product_data/get_product` | Filter product data after retrieval | `array\|null $productData`, `int $productId` |

## Testing

The plugin includes comprehensive unit and integration tests using PHPUnit.

### Test Structure

```
tests/
├── Unit/
│   └── Data/Filters/
│       └── BasicFiltersImplTest.php    # 32 unit tests for filter logic
└── Integration/
    └── Data/
        └── DataRepositoryTest.php      # 19 integration tests
```

### Running Tests

From the `wordpress-core` directory:

```bash
# Run all tests
composer test

# Run unit tests only
composer test:unit

# Run integration tests only
composer test:integration

# Run with PHPUnit directly
./vendor/bin/phpunit
```

### Test Coverage

#### Unit Tests (BasicFiltersImplTest)
Tests for the `BasicFiltersImpl` filter implementation:

- **Generic Comparators**: `eq`, `noteq`, `gt`, `gte`, `lt`, `lte`, `like`, `notlike`, `contains`, `notcontains`, `empty`, `notempty`, `in`, `notin`, `within`
- **Special Filters**:
  - `hospitalTreatments` - Filter by available hospital treatments
  - `extrasTreatments` - Filter by available extras treatments
  - `hospitalCover` - Filter by hospital tier value
  - `coverType` - Filter by cover type (Hospital, Extras, Combined)
- **Edge Cases**: Invalid filters, nested field access, case-insensitive matching, multiple filter combinations

#### Integration Tests (DataRepositoryTest)
Tests for the full `DataRepository` flow with real JSON data:

- Pagination (offset, pageSize, totalCount)
- Filter by provider name, price range, cover type, treatments
- Sorting by price and provider name (ascending/descending)
- Combined filter and sort operations
- Product and provider lookup by ID
