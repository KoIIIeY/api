<?php
/**
 * User: Michael Lazarev <mihailaz.90@gmail.com>
 * Date: 09.07.16
 * Time: 17:52
 */

use Route as RouteFacade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Illuminate\Database\Eloquent\Relations\Relation;

RouteFacade::bind('model', function($model){
	$class  = 'App\\' . studly_case(str_singular($model));

	if (!class_exists($class) || !is_subclass_of($class, Model::class)){
		abort(404, sprintf('Unknown entity: `%s`', $model));
	}
	return $class;
});

RouteFacade::bind('entity', function($id, Route $route){
	$model = $route->parameter('model');
	$with  = app('request')->get('with', []);

	/**
	 * @var \Illuminate\Database\Eloquent\Builder $query
	 */

	$mod = new $model;

	$query = $mod->newQuery();

	if (is_array($with)){
		foreach ($with as $relation){
			$query->with($relation);
		}
	}
	$entity = $query->where($mod->getPrimaryName(), $id)->first();

	if (!$entity){
		abort(404, sprintf('Entity with id `%s` is not found', $id));
	}
	return $entity;
});

RouteFacade::bind('parent', function($id, Route $route){
	$model  = $route->parameter('model');
	$parent = call_user_func([$model, 'find'], $id);

	if (!$parent){
		abort(404, sprintf('Parent %s with id `%s` is not found', $model, $id));
	}
	return $parent;
});

RouteFacade::bind('relation', function($relation_name, Route $route){
	$parent = $route->parameter('parent');

	if (!method_exists($parent, $relation_name)){
		abort(404, sprintf('Relation `%s` is not found', $relation_name));
	}
	$relation = call_user_func([$parent, $relation_name]);

	if (!is_object($relation) || !$relation instanceof Relation){
		abort(404, sprintf('Relation `%s` is not found', $relation_name));
	}
	return $relation;
});

RouteFacade::bind('child', function($id, Route $route){
	/**
	 * @var Relation $relation
	 */
	$relation = $route->parameter('relation');
	$with     = app('request')->get('with', []);

	if (is_array($with)){
		foreach ($with as $relation){
			$relation->with($relation);
		}
	}
	$child = $relation->find($id);

	if (!$child){
		abort(404, sprintf('Child %s with id `%s` is not found', get_class($relation->getRelated()), $id));
	}
	return $child;
});