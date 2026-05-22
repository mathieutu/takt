import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\TrackingController::index
* @see app/Http/Controllers/TrackingController.php:16
* @route '/tracking'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/tracking',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TrackingController::index
* @see app/Http/Controllers/TrackingController.php:16
* @route '/tracking'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TrackingController::index
* @see app/Http/Controllers/TrackingController.php:16
* @route '/tracking'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TrackingController::index
* @see app/Http/Controllers/TrackingController.php:16
* @route '/tracking'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const tracking = {
    index: Object.assign(index, index),
}

export default tracking