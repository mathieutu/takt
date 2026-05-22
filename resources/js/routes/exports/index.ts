import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\ExportsController::index
* @see app/Http/Controllers/ExportsController.php:159
* @route '/exports'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/exports',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ExportsController::index
* @see app/Http/Controllers/ExportsController.php:159
* @route '/exports'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ExportsController::index
* @see app/Http/Controllers/ExportsController.php:159
* @route '/exports'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ExportsController::index
* @see app/Http/Controllers/ExportsController.php:159
* @route '/exports'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ExportsController::csv
* @see app/Http/Controllers/ExportsController.php:17
* @route '/exports/csv'
*/
export const csv = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: csv.url(options),
    method: 'get',
})

csv.definition = {
    methods: ["get","head"],
    url: '/exports/csv',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ExportsController::csv
* @see app/Http/Controllers/ExportsController.php:17
* @route '/exports/csv'
*/
csv.url = (options?: RouteQueryOptions) => {
    return csv.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ExportsController::csv
* @see app/Http/Controllers/ExportsController.php:17
* @route '/exports/csv'
*/
csv.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: csv.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ExportsController::csv
* @see app/Http/Controllers/ExportsController.php:17
* @route '/exports/csv'
*/
csv.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: csv.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ExportsController::xlsx
* @see app/Http/Controllers/ExportsController.php:69
* @route '/exports/xlsx'
*/
export const xlsx = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: xlsx.url(options),
    method: 'get',
})

xlsx.definition = {
    methods: ["get","head"],
    url: '/exports/xlsx',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ExportsController::xlsx
* @see app/Http/Controllers/ExportsController.php:69
* @route '/exports/xlsx'
*/
xlsx.url = (options?: RouteQueryOptions) => {
    return xlsx.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ExportsController::xlsx
* @see app/Http/Controllers/ExportsController.php:69
* @route '/exports/xlsx'
*/
xlsx.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: xlsx.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ExportsController::xlsx
* @see app/Http/Controllers/ExportsController.php:69
* @route '/exports/xlsx'
*/
xlsx.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: xlsx.url(options),
    method: 'head',
})

const exports = {
    index: Object.assign(index, index),
    csv: Object.assign(csv, csv),
    xlsx: Object.assign(xlsx, xlsx),
}

export default exports