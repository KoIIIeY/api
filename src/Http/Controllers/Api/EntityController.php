<?php

namespace Koiiiey\Api\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Database\Eloquent\Model;
use App\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Class ApiController
 * @package App\Http\Controllers\Api
 */
class EntityController extends \Illuminate\Routing\Controller
{

    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    /**
     * @param Request $request
     * @param string $model
     * @return JsonResponse
     */
    public function index(Request $request, $model)
    {
        /**
         * @var \Illuminate\Database\Eloquent\Model $entity
         */
        $model = ucfirst($model);
        $entity = new $model;

        $this->authorize('read', $entity);

//        Log::out('index', $model, $request->all());

        /**
         * @var \Illuminate\Database\Eloquent\Builder $query
         */
        $query = $entity->newQuery();
        $filter = $request->get('filter', []);
        $whereNull = $request->get('whereNull', []);
        $scope = $request->get('scope', []);
        $with = $request->get('with', []);
        $select = $request->get('select', []);
        $per_page = $request->get('per_page', 30);
        $order = $request->get('order', []);


        if (is_array($select) && !empty($select)) {
            $query->select($select);
        }
        if (is_array($with) && !empty($with)) {
            $query->with($with);
        }
        if (is_array($whereNull) && !empty($whereNull)) {
            foreach ($whereNull as $item) {
                $query->whereNull($item);
            }
        }
        if (is_array($filter)) {
            apply_query_filters($query, $filter);
        }
        if (is_array($scope)) {
            apply_query_scopes($query, $scope);
        }

        if (is_array($order)) {

            foreach ($order as $ord) {
                if (!is_array($ord)) {
                    $ord = json_decode($ord);
                }
                list($field, $direction) = $ord;
                $query = $query->orderBy($field, $direction);
            }
        }

//        $query->addGlobalScope(new \App\Scopes\My());

        $paginator = $query->paginate($per_page);
        $paginator->appends($request->except('page'));

        return new JsonResponse($paginator, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * @param Request $request
     * @param string $model
     * @param Model $entity
     * @return JsonResponse
     */
    public function show(Request $request, $model, $entity)
    {
        $model = ucfirst($model);

        $query = (new $model)->newQuery();
        $with = $request->get('with', []);
        $select = $request->get('select', []);

        if (is_array($select) && !empty($select)) {
            $query->select($select);
        }
        if (is_array($with) && !empty($with)) {
            $query->with($with);
        }

        $entity = $query->find($entity);

        $this->authorize('read', $entity);

        Log::out('show', $model, $request->all());

        return new JsonResponse($entity, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * @param Request $request
     * @param string $model
     * @return JsonResponse
     */
    public function store(Request $request, $model)
    {
        $data = $request->all();

        /**
         * @var \Illuminate\Database\Eloquent\Model $entity
         */
        $model = ucfirst($model);
        $model = new $model($data);

        $this->authorize('create', $model);

        if (method_exists($model, 'rules')) {
            $rules = $model->rules($data);

            try {
                $this->validate($request, $rules);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return $e->getResponse();
            }
        }

        try {
            $save = $model->save();
            $model = $model->fresh();

            $filled = $model->getAttributes();
            $filtered = array_diff_assoc($data, $filled);

            if (count($filtered))
            {
                foreach($filtered as $key => $value)
                {
                    if (method_exists($model, $key))
                    {
                        $class = get_class($model);
                        if ($pos = strrpos($class, '\\'))
                        {
                            $class = substr($class, $pos + 1);
                        }
                        if (is_array($value))
                        {
                            foreach($value as $index => &$val) {
                                if (is_array($val) && !isset($val['entity']))
                                {
                                    $val['entity'] = $class;
                                }
                            }
                        }
                        $this->fillRelation($model->$key(), $value);
                    }
                }
            }

        } catch (\Exception $e) {
            return response()->json(['error' => [
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ]
            ], 422);
        }

        return new JsonResponse($model, $save ? 200 : 422);
    }

    public function fillRelation(Relation $relation, $items) {

        $model = $relation->getModel();

        $created = null;

        foreach($items as $data)
        {
            if (is_array($data))
            {
                if (method_exists($model, 'rules')) {
                    $rules = $model->rules($data);

                    try {
                        Validator::make($data, $rules)->validate();
                    } catch (\Illuminate\Validation\ValidationException $e) {
                        return $e->getResponse();
                    }
                }
                $created = $relation->create($data);

                $filled = $created->getAttributes();
                $filtered = array_diff_assoc($data, $filled);

                if (count($filtered))
                {
                    foreach($filtered as $key => $value)
                    {
                        if (method_exists($created, $key))
                        {
                            $class = get_class($created);
                            if ($pos = strrpos($class, '\\'))
                            {
                                $class = substr($class, $pos + 1);
                            }
                            if (is_array($value))
                            {
                                foreach($value as $index => &$val) {
                                    if (is_array($val) && !isset($val['entity']))
                                    {
                                        $val['entity'] = $class;
                                    }
                                }
                            }
                            $this->fillRelation($created->$key(), $value);
                        }
                    }
                }
            }
        }

        return $created;
    }

    /**
     * @param Request $request
     * @param string $model
     * @param Model $entity
     * @return JsonResponse
     */
    public function update(Request $request, $model, $entity)
    {
        $model = ucfirst($model);
        $entity = $model::find($entity);

        $this->authorize('update', $entity);

        Log::out('update', $model, $request->all());

        if (method_exists($entity, 'rules')) {
            $rules = $entity->rules($request);
//            dd($rules);
            try {
                $this->validate($request, $rules);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return $e->getResponse();
            }
        }


        try {
            $entity->fill($request->all());
            $s = $entity->save();
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage(), 'ex' => [$e->getFile(), $e->getLine()] ], 422);
        }

        return new JsonResponse($entity, $s ? 200 : 422, [], JSON_NUMERIC_CHECK);
    }

    /**
     * @param string $model
     * @param Model $entity
     * @return JsonResponse
     * @throws \Exception
     */
    public function destroy($model, $entity)
    {
        $model = ucfirst($model);
        $entity = $model::find($entity);
        $this->authorize('destroy', $entity);

        Log::out('delete', $model, $entity);

        try {
            $s = $entity->delete();
        } catch (\Exception $e) {
            return response()->json(['error' => [$e->getMessage()]], 422);
        }


        return new JsonResponse([], $s ? 200 : 422, [], JSON_NUMERIC_CHECK);
    }
}
