<?php

namespace Koiiiey\Api\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\App;

/**
 * Class ApiController
 * @package App\Http\Controllers\Api
 */
class AdditionalController extends \Illuminate\Routing\Controller
{

    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    public function __construct()
    {
        $this->middleware(StartSession::class, []); // socialite fix
    }




    public function findModel($model){
        $model = '\App\\' . ucfirst($model);
        if(class_exists($model)){
            $model = new $model;
        } else {

            $model = (str_replace(['ies'], 'y', $model));

            if (substr($model, -1) == 's')
            {
                $model = substr($model, 0, -1);
            }


            $model = new $model;
        }


        return $model;
    }

    public function getModel($model)
    {
        $forModel = $model;
        $model = $this->findModel($model);

        $trans = [];

        $trans[$model->primaryKey] = trans('model.' . $model->primaryKey);
        foreach ($model->getFillable() as $field) {

            if (\Lang::has('model.' . $forModel . '.' . $field)) {
                $trans[$field] = trans('model.' . $forModel . '.' . $field);
            } else {
                $trans[$field] = trans('model.' . $field);
            }
        }

        return response()->json($trans, 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function getRules($model, Request $request)
    {
        $model = $this->findModel($model);
        if (method_exists($model, 'rules')) {
            return response()->json($model->rules($request));
        }
        return response()->json([]);
    }

    public function getTypes($model)
    {

        \DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        $model = $this->findModel($model);
        $trans[$model->primaryKey] = \DB::connection()->getDoctrineColumn($model->getTable(), $model->primaryKey)->getType()->getName();
        foreach ($model->getFillable() as $field) {
            try{
                $trans[$field] = \DB::connection()->getDoctrineColumn($model->getTable(), $field)->getType()->getName();
            } catch (\Exception $e){

            }

        }

        if(!is_array($model->adminRel)){
            $model->adminRel = [];
        }
        if(!is_array($model->with)){
            $model->with = [];
        }
        $model->with = array_merge($model->with, $model->adminRel);

        if ($model->with) {
            foreach ($model->with as $with) {


                if (str_replace($model->getTable() . '.', '', $model->$with()->getQualifiedParentKeyName()) == $model->getKeyName()) {
                    if (method_exists($model->$with(), 'getForeignKey') && $model->$with()->getForeignKey() != $model->getKeyName()) {
                        //                                        dd($model->$with()->getForeignKey(), $model->getKeyName());
                        $trans[$model->$with()->getForeignKey()] = 'relation:' . (str_replace('App\\', '', get_class($model->$with()->getRelated()))) . ':' . $model->$with()->getOwnerKey();
                    }
                    continue;
                }
                $trans[str_replace($model->getTable() . '.', '', $model->$with()->getQualifiedParentKeyName())] = 'relation:' . (str_replace('App\\', '', get_class($model->$with()->getRelated()))) . ':' . $model->$with()->getForeignKeyName();
            }
        }

        if (isset($model->files) && is_array($model->files)) {
            foreach ($model->files as $file) {
                $trans[$file] = 'file:' . (isset($model->fileCasts) && isset($model->fileCasts[$file]) ? $model->fileCasts[$file] : 'Image');
            }
        }

        if (method_exists($model, 'getEnumVariants')) {
            $model->enumVariants = $model->getEnumVariants();
        }

        if (isset($model->enumVariants) && is_array($model->enumVariants)) {
            foreach ($model->enumVariants as $enumField => $enumVars) {
                $enum = new \stdClass();
                $enum->enum = $enumVars;
                $trans[$enumField] = $enum;
            }
        }

        return response()->json($trans, 200, [], JSON_UNESCAPED_UNICODE);
    }
}
