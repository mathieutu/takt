import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\ShareController::apply
* @see app/Http/Controllers/ShareController.php:37
* @route '/share/{share}'
*/
export const apply = (args: { share: string | { id: string } } | [share: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: apply.url(args, options),
    method: 'get',
})

apply.definition = {
    methods: ["get","head"],
    url: '/share/{share}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ShareController::apply
* @see app/Http/Controllers/ShareController.php:37
* @route '/share/{share}'
*/
apply.url = (args: { share: string | { id: string } } | [share: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { share: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { share: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            share: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        share: typeof args.share === 'object'
        ? args.share.id
        : args.share,
    }

    return apply.definition.url
            .replace('{share}', parsedArgs.share.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ShareController::apply
* @see app/Http/Controllers/ShareController.php:37
* @route '/share/{share}'
*/
apply.get = (args: { share: string | { id: string } } | [share: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: apply.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ShareController::apply
* @see app/Http/Controllers/ShareController.php:37
* @route '/share/{share}'
*/
apply.head = (args: { share: string | { id: string } } | [share: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: apply.url(args, options),
    method: 'head',
})

const share = {
    apply: Object.assign(apply, apply),
}

export default share