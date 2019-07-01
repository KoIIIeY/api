<?php

namespace Koiiiey\Api\Http\Controllers\Api;

use App\Visibility\Visibility;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use App\Log;

/**
 * Class ApiController
 * @package App\Http\Controllers\Api
 */
class EntityController extends Controller
{
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
        $entity = new $model;

        $this->authorize('read', $entity);

        Log::out('index', $model, $request->all());

        /**
         * @var \Illuminate\Database\Eloquent\Builder $query
         */
        $query = $entity->newQuery();
        $where = $request->get('where', []);                // where[]: ["field","=","123"]
        $whereNull = $request->get('whereNull', "[]");
        $scope = $request->get('scope', "[]");
        $relscope = $request->get('relscope', "[]");
        $with = $request->get('with', "[]");                // with=["rel1","rel2"]
        $withCount = $request->get('withCount', "[]");      // withCount=["rel1","rel2"]
        $select = $request->get('select', "[]");
        $per_page = $request->get('per_page', 30);
        $order = $request->get('order', []);
        $count = $request->get('count', false);

        $with = json_decode($with);
        $withCount = json_decode($withCount);
        $select = json_decode($select);

        try{
            $scope = json_decode($scope);
        }catch(\Exception $e){
            $scope = $request->get('scope', []);
        }

        $whereNull = json_decode($whereNull);

        $relationWithScope = json_decode($relscope);


        if (is_array($select) && !empty($select)) {
            $query->select($select);
        }


        if(is_array($relationWithScope) && !empty($relationWithScope)){

            $forDelete = [];

            foreach($relationWithScope as $i => $relScope){
//                    dd($relScope);
                $break = 0;
                list($withName, $relScopeSmall) = explode(',', $relScope, 2);
                foreach($order as $ord){
                    if(mb_strpos($ord, $withName.'.') !== false){
                        $break = 1;
                        continue;
                    }
                }

                if($break){
                    continue;
                }

//                $withName = $withName . ' as a';
//                dd($withName, $relScopeSmall);
                $query->with([$withName => function($q) use ($relScopeSmall){
//                    dd($q);
                    apply_query_scopes($q, [$relScopeSmall]);
                }]);

                $forDelete[] = $i;

                if(isset($with[$withName])){
                    unset($with[$withName]);
                }

//                    dd($relScope);
            }

            foreach($forDelete as $del){
                unset($relationWithScope[$del]);
            }
//            dd($relationWithScope);
        }

        if (is_array($with) && !empty($with)) {
            $query->with($with);
        }
        if (is_array($withCount) && !empty($withCount)) {
            $query->withCount($withCount);
        }
        if (is_array($whereNull) && !empty($whereNull)) {
            foreach ($whereNull as $item) {
                $query->whereNull($item);
            }
        }
        if (is_array($where)) {
            apply_query_filters($query, $where);
        }
        if (is_array($scope)) {
            apply_query_scopes($query, $scope);
        }


        $ordersBlock = [];
        if (is_array($order)) {

            foreach ($order as $ord) {
                if (!is_array($ord)) {
                    $ord = json_decode($ord);
                }
                list($field, $direction) = $ord;
                if(strpos($field, '.') !== false){
//                    continue;
                    $ordersBlock[] = ['f' => $field, 'd' => $direction];

                    $ordField = str_replace('.', '_', explode('.', $field)[0].' as '.$field);

                    $query->withCount([$ordField => function ($q) use ($direction, $field) {
                        $q->select(explode('.', $field)[1])->orderBy(explode('.', $field)[1], $direction)->take(1);
                    }])->orderBy(str_replace('.', '_',  $field), $direction);
                    continue;
                }
                $query = $query->orderBy($field, $direction);
            }
        }


        if ($count) {
            return new JsonResponse(['count' => $query->count()]);
        }

//        foreach($ordersBlock as $ord){
//
//        }

//        $query->addGlobalScope(new \App\Scopes\My());

        if($request->get('toSql', false)){
            $queryA = str_replace(array('?'), array('\'%s\''), $query->toSql());
            $queryA = vsprintf($queryA, $query->getBindings());
            dd($queryA);
        }



        $paginator = $query->paginate($per_page);
        $paginator->appends($request->except('page'));

//        foreach ($paginator->items() as $item) {
//            $this->authorize('read', $item);
//            foreach ($item->getRelations() as $relation) {
//                $this->authorize('read', $relation);
//            }
//        }

        return new JsonResponse($paginator);
    }

    /**
     * @param Request $request
     * @param string $model
     * @param Model $entity
     * @return JsonResponse
     */
    public function show(Request $request, $model, $model_id)
    {
        $query = (new $model)->newQuery();
        $with = $request->get('with', "[]");                // with=["rel1","rel2"]
        $withCount = $request->get('withCount', "[]");      // withCount=["rel1","rel2"]
        $select = $request->get('select', "[]");

        $with = json_decode($with);
        $withCount = json_decode($withCount);
        $select = json_decode($select);

        if (is_array($select) && !empty($select)) {
            $query->select($select);
        }
        if (is_array($with) && !empty($with)) {
            $query->with($with);
        }
        if (is_array($withCount) && !empty($withCount)) {
            $query->withCount($withCount);
        }

        $entity = $query->find($model_id);
//        dd($entity);

        $this->authorize('read', $entity);

//        dd($entity->getRelations());
//        foreach ($entity->getRelations() as $relation) {
//            $this->authorize('read', $relation);
//        }

//        Visibility::authorize($model, 'read', $entity);

        Log::out('show', $model, $request->all());

        return new JsonResponse($entity);
    }

    /**
     * @param Request $request
     * @param string $model
     * @return JsonResponse
     */
    public function create(Request $request, $model)
    {

        $reqAll = $request->all();
        foreach ($reqAll as $field => $value) {
            if (is_object($value)) {
                unset($reqAll[$field]);
            }
        }

        /**
         * @var \Illuminate\Database\Eloquent\Model $entity
         */
        $model = ucfirst($model);
        $entity = new $model($reqAll);

        $this->authorize('create', $entity);

        Log::out('store', $model, $reqAll);

        if (method_exists($entity, 'rules')) {
            $rules = $entity->rules($request);

            try {
                $this->validate($request, $rules);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return response()->json(['error' => $e->errors()], 422);
                return $e->getResponse();
            }
        }

        try {
            $entity->fill($request->all());
            $s = $entity->save();
            if(is_bool($s)){
                $entity = $entity->fresh();
            } else if(is_object($s)){
                $entity = $s;
            }

        } catch (\Exception $e) {
            return response()->json(['error' => [$e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()]], 422);
        }


        return new JsonResponse($entity, $s ? 200 : 422);
    }

    /**
     * @param Request $request
     * @param string $model
     * @param Model $entity
     * @return JsonResponse
     */
    public function update(Request $request, $model, $model_id)
    {
        $query = (new $model)->newQuery();
        $with = $request->get('with', "[]");

        $with = json_decode($with);

        $entity = $model::find($model_id);

        $this->authorize('update', $entity);

        Log::out('update', $model, $request->all());

        if (method_exists($entity, 'rules')) {
            $rules = $entity->rules($request);
//            dd($rules);
            try {
                $this->validate($request, $rules);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return response()->json(['error' => $e->errors()], 422);
                return $e->getResponse();
            }
        }

        try {
            $entity->fill($request->all());
            $s = $entity->save();
            $entity = $entity->fresh();
        } catch (\Exception $e) {
            return response()->json(['error' => [$e->getMessage()]], 422);
        }

        if (is_array($with) && !empty($with)) {
            $query->with($with);
        }
//        $entity = $model::find($entity[$entity->getKeyName()]);
        $entity = $query->find($model_id);

        return new JsonResponse($entity, $s ? 200 : 422);
    }

    /**
     * @param string $model
     * @param Model $entity
     * @return JsonResponse
     * @throws \Exception
     */
    public function destroy($model, $model_id)
    {
        $entity = $model::find($model_id);
        $this->authorize('destroy', $entity);

        Log::out('delete', $model, $entity);

        try {
            $s = $entity->delete();
        } catch (\Exception $e) {
            return response()->json(['error' => [$e->getMessage()]], 422);
        }


        return new JsonResponse([], $s ? 200 : 422);
    }

    public function call(Request $request, $model, $method){
        $response = $model::$method($request);

        if($response instanceof JsonResponse){
            return $response;
        }
        return new JsonResponse($response, 200, [], JSON_NUMERIC_CHECK);
    }
}
