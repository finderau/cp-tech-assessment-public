<?php

declare(strict_types=1);

namespace Finder\ProductData\Rest;

use Finder\ProductData\Data\DataRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST API controller for product data endpoints.
 *
 * @package Finder\ProductData\Rest
 */
final class RestController
{
    private const NAMESPACE = 'api/v1';
    private const ROUTE_PRODUCTS = '/product-data';
    private const ROUTE_PROVIDERS = '/providers';

    /**
     * Valid filter comparators.
     */
    private const VALID_COMPARATORS = [
        'eq',
        'gt',
        'gte',
        'lt',
        'lte',
        'like',
        'notlike',
        'contains',
        'notcontains',
        'noteq',
        'empty',
        'notempty',
        'in',
        'notin',
        'within',
    ];

    /**
     * Valid sort directions.
     */
    private const VALID_DIRECTIONS = ['ASCENDING', 'DESCENDING'];

    private DataRepository $repository;

    /**
     * Constructor.
     *
     * @param DataRepository $repository Data repository instance.
     */
    public function __construct(DataRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Register REST API routes.
     *
     * @return void
     */
    public function registerRoutes(): void
    {
        register_rest_route(
            self::NAMESPACE,
            self::ROUTE_PRODUCTS,
            [
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'getProducts'],
                    'permission_callback' => [$this, 'checkPermission'],
                    'args' => $this->getProductsArgs(),
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            self::ROUTE_PROVIDERS,
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'getProviders'],
                    'permission_callback' => [$this, 'checkPermission'],
                ],
            ]
        );
    }

    /**
     * Check permission for accessing the endpoint.
     *
     * @return bool Always returns true for public access.
     */
    public function checkPermission(): bool
    {
        /**
         * Filter the permission check result for product data API.
         *
         * @since 1.0.0
         * @param bool $hasPermission Whether the user has permission.
         */
        return apply_filters('finder/product_data/rest/permission', true);
    }

    /**
     * Get products endpoint handler.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response|WP_Error Response or error.
     */
    public function getProducts(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $filters = $request->get_param('filters') ?? [];
        $sort = $request->get_param('sort') ?? [];
        $pagination = $request->get_param('pagination') ?? [];

        // Validate filters.
        $filtersValidation = $this->validateFilters($filters);
        if (is_wp_error($filtersValidation)) {
            return $filtersValidation;
        }

        // Validate sort.
        $sortValidation = $this->validateSort($sort);
        if (is_wp_error($sortValidation)) {
            return $sortValidation;
        }

        // Validate pagination.
        $paginationValidation = $this->validatePagination($pagination);
        if (is_wp_error($paginationValidation)) {
            return $paginationValidation;
        }

        /**
         * Filter the request parameters before querying.
         *
         * @since 1.0.0
         * @param array $params The request parameters.
         * @param WP_REST_Request $request The REST request.
         */
        $params = apply_filters('finder/product_data/rest/request_params', [
            'filters' => $filters,
            'sort' => $sort,
            'pagination' => $pagination,
        ], $request);

        $result = $this->repository->getProducts(
            $params['filters'],
            $params['sort'],
            $params['pagination']
        );

        /**
         * Filter the response data before sending.
         *
         * @since 1.0.0
         * @param array $result The result data.
         * @param WP_REST_Request $request The REST request.
         */
        $result = apply_filters('finder/product_data/rest/response', $result, $request);

        return new WP_REST_Response($result, 200);
    }

    /**
     * Get providers endpoint handler.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response Response containing all providers.
     */
    public function getProviders(WP_REST_Request $request): WP_REST_Response
    {
        $providers = $this->repository->getProviders();

        /**
         * Filter the providers response data before sending.
         *
         * @since 1.0.0
         * @param array $providers The providers data.
         * @param WP_REST_Request $request The REST request.
         */
        $providers = apply_filters('finder/product_data/rest/providers_response', $providers, $request);

        return new WP_REST_Response([
            'providers' => $providers,
            'totalCount' => count($providers),
        ], 200);
    }

    /**
     * Get argument schema for products endpoint.
     *
     * @return array<string, mixed> Arguments schema.
     */
    private function getProductsArgs(): array
    {
        return [
            'filters' => [
                'type' => 'array',
                'default' => [],
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'field' => [
                            'type' => 'string',
                            'required' => true,
                        ],
                        'comparator' => [
                            'type' => 'string',
                            'required' => true,
                            'enum' => self::VALID_COMPARATORS,
                        ],
                        'value' => [
                            'required' => false,
                        ],
                    ],
                ],
            ],
            'sort' => [
                'type' => 'array',
                'default' => [],
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'field' => [
                            'type' => 'string',
                            'required' => true,
                        ],
                        'direction' => [
                            'type' => 'string',
                            'required' => true,
                            'enum' => self::VALID_DIRECTIONS,
                        ],
                    ],
                ],
            ],
            'pagination' => [
                'type' => 'object',
                'default' => [],
                'properties' => [
                    'offset' => [
                        'type' => 'integer',
                        'default' => 0,
                        'minimum' => 0,
                    ],
                    'pageSize' => [
                        'type' => 'integer',
                        'default' => 20,
                        'minimum' => 1,
                        'maximum' => 100,
                    ],
                ],
            ],
        ];
    }

    /**
     * Validate filters array.
     *
     * @param mixed $filters Filters to validate.
     * @return true|WP_Error True if valid, WP_Error otherwise.
     */
    private function validateFilters(mixed $filters): true|WP_Error
    {
        if (!is_array($filters)) {
            return new WP_Error(
                'invalid_filters',
                __('Filters must be an array.', 'product-data'),
                ['status' => 400]
            );
        }

        foreach ($filters as $index => $filter) {
            if (!is_array($filter)) {
                return new WP_Error(
                    'invalid_filter',
                    sprintf(
                        /* translators: %d: filter index */
                        __('Filter at index %d must be an object.', 'product-data'),
                        $index
                    ),
                    ['status' => 400]
                );
            }

            if (!isset($filter['field']) || !is_string($filter['field'])) {
                return new WP_Error(
                    'invalid_filter_field',
                    sprintf(
                        /* translators: %d: filter index */
                        __('Filter at index %d must have a valid "field" property.', 'product-data'),
                        $index
                    ),
                    ['status' => 400]
                );
            }

            if (!isset($filter['comparator']) || !in_array($filter['comparator'], self::VALID_COMPARATORS, true)) {
                return new WP_Error(
                    'invalid_filter_comparator',
                    sprintf(
                        /* translators: %d: filter index */
                        __('Filter at index %d must have a valid "comparator" property.', 'product-data'),
                        $index
                    ),
                    ['status' => 400]
                );
            }
        }

        return true;
    }

    /**
     * Validate sort array.
     *
     * @param mixed $sort Sort configurations to validate.
     * @return true|WP_Error True if valid, WP_Error otherwise.
     */
    private function validateSort(mixed $sort): true|WP_Error
    {
        if (!is_array($sort)) {
            return new WP_Error(
                'invalid_sort',
                __('Sort must be an array.', 'product-data'),
                ['status' => 400]
            );
        }

        foreach ($sort as $index => $sortConfig) {
            if (!is_array($sortConfig)) {
                return new WP_Error(
                    'invalid_sort_config',
                    sprintf(
                        /* translators: %d: sort config index */
                        __('Sort configuration at index %d must be an object.', 'product-data'),
                        $index
                    ),
                    ['status' => 400]
                );
            }

            if (!isset($sortConfig['field']) || !is_string($sortConfig['field'])) {
                return new WP_Error(
                    'invalid_sort_field',
                    sprintf(
                        /* translators: %d: sort config index */
                        __('Sort configuration at index %d must have a valid "field" property.', 'product-data'),
                        $index
                    ),
                    ['status' => 400]
                );
            }

            if (!isset($sortConfig['direction']) || !in_array($sortConfig['direction'], self::VALID_DIRECTIONS, true)) {
                return new WP_Error(
                    'invalid_sort_direction',
                    sprintf(
                        /* translators: %d: sort config index */
                        __('Sort configuration at index %d must have a valid "direction" property.', 'product-data'),
                        $index
                    ),
                    ['status' => 400]
                );
            }
        }

        return true;
    }

    /**
     * Validate pagination object.
     *
     * @param mixed $pagination Pagination configuration to validate.
     * @return true|WP_Error True if valid, WP_Error otherwise.
     */
    private function validatePagination(mixed $pagination): true|WP_Error
    {
        if (!is_array($pagination)) {
            return new WP_Error(
                'invalid_pagination',
                __('Pagination must be an object.', 'product-data'),
                ['status' => 400]
            );
        }

        if (isset($pagination['offset']) && (!is_int($pagination['offset']) || $pagination['offset'] < 0)) {
            return new WP_Error(
                'invalid_pagination_offset',
                __('Pagination offset must be a non-negative integer.', 'product-data'),
                ['status' => 400]
            );
        }

        if (isset($pagination['pageSize'])) {
            if (!is_int($pagination['pageSize']) || $pagination['pageSize'] < 1 || $pagination['pageSize'] > 100) {
                return new WP_Error(
                    'invalid_pagination_pagesize',
                    __('Pagination pageSize must be an integer between 1 and 100.', 'product-data'),
                    ['status' => 400]
                );
            }
        }

        return true;
    }
}
