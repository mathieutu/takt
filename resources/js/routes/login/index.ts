import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\AuthController::disabled
* @see app/Http/Controllers/AuthController.php:49
* @route '/login/disabled'
*/
export const disabled = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: disabled.url(options),
    method: 'post',
})

disabled.definition = {
    methods: ["post"],
    url: '/login/disabled',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AuthController::disabled
* @see app/Http/Controllers/AuthController.php:49
* @route '/login/disabled'
*/
disabled.url = (options?: RouteQueryOptions) => {
    return disabled.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuthController::disabled
* @see app/Http/Controllers/AuthController.php:49
* @route '/login/disabled'
*/
disabled.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: disabled.url(options),
    method: 'post',
})

const login = {
    disabled: Object.assign(disabled, disabled),
}

export default login