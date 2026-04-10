use Govorun\Framework\Routing\Router;
@foreach($routes as $route)
@if($route['handler_type'] === 'controller')
use App\Controllers\{{ $route['controller_class'] }};
@else
use App\Flows\{{ $route['flow_class'] }};
@endif
@endforeach

return function (Router $router) {
@foreach($routes as $route)
@if($route['type'] === 'fallback')
    $router->fallback({{ $route['handler_type'] === 'controller' ? $route['controller_class'] . '::class' : $route['flow_class'] . '::class' }})@if(!empty($route['middleware']))->middleware({!! json_encode($route['middleware']) !!})@endif;
@else
    $router->{{ $route['type'] }}({!! $route['match'] !== null ? "'" . $route['match'] . "', " : '' !!}{{ $route['handler_type'] === 'controller' ? $route['controller_class'] . '::class' : $route['flow_class'] . '::class' }})@if(!empty($route['middleware']))->middleware({!! json_encode($route['middleware']) !!})@endif;
@endif
@endforeach
};
