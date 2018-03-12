<?php

namespace Koiiiey\Api\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Class RelationController
 * @package App\Http\Controllers\Api
 */
class RelationController extends \Illuminate\Routing\Controller
{

    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

	/**
	 * @param Request  $request
	 * @param string   $model
	 * @param Model    $parent
	 * @param Relation $relation
	 * @return JsonResponse
	 */
    public function index(Request $request, $model, $parent, $relation)
    {
	    $this->authorize('read', $parent);
	    $this->authorize('read', [$relation->getRelated(), $parent]);

	    $with   = $request->get('with', []);
	    $filter = $request->get('filter', []);
	    $scope  = $request->get('scope', []);

	    if (is_array($with) && $with){
		    $relation->with($with);
	    }
	    if (is_array($filter)){
		    apply_query_filters($relation, $filter);
	    }
	    if (is_array($scope)){
		    apply_query_scopes($relation, $scope);
	    }
	    return new JsonResponse($relation->getResults(), 200, [], JSON_NUMERIC_CHECK);
    }

	/**
	 * @param Request  $request
	 * @param string   $model
	 * @param Model    $parent
	 * @param Relation $relation
	 * @return JsonResponse
	 */
	public function store(Request $request, $model, $parent, $relation)
	{

		$related = $relation->getRelated()->fill($request->all());

		$this->authorize('read', $parent);
		$this->authorize('create', [$related, $parent]);

		if (method_exists($relation->getRelated(), 'rules')){
			$rules = $relation->getRelated()->rules($request);
			$this->validate($request, $rules);
		}
		$related->save();

		return new JsonResponse($related, 200, [], JSON_NUMERIC_CHECK);
	}

	/**
	 * @param Request  $request
	 * @param string   $model
	 * @param Model    $parent
	 * @param Relation $relation
	 * @param Model    $child
	 * @return JsonResponse
	 */
	public function show(Request $request, $model, $parent, $relation, $child)
	{
		$this->authorize('read', $parent);
		$this->authorize('read', [$child, $parent]);
		return new JsonResponse($child, 200, [], JSON_NUMERIC_CHECK);
	}

	/**
	 * @param Request  $request
	 * @param string   $model
	 * @param Model    $parent
	 * @param Relation $relation
	 * @param Model    $child
	 * @return JsonResponse
	 */
	public function update(Request $request, $model, $parent, $relation, $child)
	{
		$this->authorize('read', $parent);
		$this->authorize('update', [$child, $parent]);

		if (method_exists($child, 'rules')){
			$rules = $child->rules($request);
			$this->validate($request, $rules);
		}
		$child->fill($request->all());


		return new JsonResponse($child, $child->save() ? 200 : 422, [], JSON_NUMERIC_CHECK);
	}

	/**
	 * @param string   $model
	 * @param Model    $parent
	 * @param Relation $relation
	 * @param Model    $child
	 * @return JsonResponse
	 * @throws \Exception
	 */
	public function destroy($model, $parent, $relation, $child)
	{
		$this->authorize('read', $parent);
		$this->authorize('destroy', [$child, $parent]);


		return new JsonResponse([], $child->delete() ? 200 : 422, [], JSON_NUMERIC_CHECK);
	}
}
