<?php

namespace Koiiiey\Api\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;

/**
 * Class ApiController
 * @package App\Http\Controllers\Api
 */
class AdditionalController extends Controller
{
    public function __construct()
    {
        $this->middleware(StartSession::class, []); // socialite fix
    }


    public function getFullModel($model)
    {
        /*
         *  field => [
         *      name => '',
         *      type => '',
         *      enum? => []
         *  ]
         */

        $forModel = $model;
        $model = ucfirst($model);
        $model = new $model;

        $full = [];


        // fields
        $fields = [];
        $fields[] = $model->primaryKey;
        $fields = array_merge($fields, $model->getFillable());

        if ($model->readonly) {
            $fields = array_merge($fields, $model->readonly);
            $fields = array_unique($fields);
        }


        // translate
        foreach ($fields as $field) {

            if (\Lang::has('model.' . $forModel . '.' . $field)) {
                $full[$field]['name'] = trans('model.' . $forModel . '.' . $field);
            } else if (\Lang::has('model.' . $field)) {
                $full[$field]['name'] = trans('model.' . $field);
            } else {
                $full[$field]['name'] = $field;
            }
        }



        // types
        foreach ($fields as $field) {
            try {
                $full[$field]['type'] = \DB::connection()->getDoctrineColumn($model->getTable(), $field)->getType()->getName();
            } catch (\Exception $e) {
                if ($model->fieldType && is_array($model->fieldType)) {
                    $full[$field]['type'] = $model->fieldType[$field];
                } else {
                    $full[$field]['type'] = isset($model->readonly[$field]) ? 'readonly' : 'string';
                }
            }
        }

        if ($model->adminWith) {
            if (!is_array($model->with)) {
                $model->with = [];
            }

            $model->with = array_merge($model->with, $model->adminWith);
            $model->with = array_unique($model->with);
        }
        // relation:{Model}:{foreign_key}:{with}
        if ($model->with) {
            foreach ($model->with as $with) {

                if (str_replace($model->getTable() . '.', '', $model->$with()->getQualifiedParentKeyName()) == $model->getKeyName()) {
                    if (method_exists($model->$with(), 'getForeignKey') && $model->$with()->getForeignKey() != $model->getKeyName()) {
                        //                                        dd($model->$with()->getForeignKey(), $model->getKeyName());
                        $full[$model->$with()->getForeignKey()]['type'] = 'relation:' . (str_replace('App\\', '', get_class($model->$with()->getRelated()))) . ':' . $model->$with()->getOwnerKey() . ':' . $with;
                    }
                    continue;
                }
                $field = str_replace($model->getTable() . '.', '', $model->$with()->getQualifiedParentKeyName());
                $readonly = false;
                if ($model->readonly) {
                    $readonly = in_array($field, $model->readonly);
                }
                $full[$field]['type'] = ($readonly ? 'readonly:' : 'relation:') . (str_replace('App\\', '', get_class($model->$with()->getRelated()))) . ':' . $model->$with()->getForeignKeyName() . ':' . $with;
            }
        }

        if (isset($model->files) && is_array($model->files)) {
            foreach ($model->files as $file) {
                $full[$file]['type'] = 'file:' . (isset($model->fileCasts) && isset($model->fileCasts[$file]) ? $model->fileCasts[$file] : 'Image');
            }
        }

        if (method_exists($model, 'getEnumVariants')) {
            $model->enumVariants = $model->getEnumVariants();
        }

        if (isset($model->enumVariants) && is_array($model->enumVariants)) {
            foreach ($model->enumVariants as $enumField => $enumVars) {
                $full[$enumField]['type'] = 'enum';
                $enumVarsObj = new \stdClass();
                foreach ($enumVars as $objIndex => $objVal) {
                    $enumVarsObj->$objIndex = $objVal;
                }

                $full[$enumField]['enum'] = $enumVarsObj;
            }
        }

        return response()->json($full, 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function getModel($model)
    {
        $forModel = $model;
        $model = ucfirst($model);
        $model = new $model;

        $trans = [];


        //		$trans['forModel'] = trans('model.table.'.$forModel);
        $trans[$model->primaryKey] = trans('model.' . $model->primaryKey);

        $fields = $model->getFillable();

        if ($model->readonly) {
            $fields = array_merge($fields, $model->readonly);
            $fields = array_unique($fields);
        }

        foreach ($fields as $field) {

            if (\Lang::has('model.' . $forModel . '.' . $field)) {
                $trans[$field] = trans('model.' . $forModel . '.' . $field);
            } else {
                $trans[$field] = trans('model.' . $field);
            }
        }

        return response()->json($trans, 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function getFields($model)
    {
        $model = ucfirst($model);
        $model = new $model;

        $fields = [];
        $fields[] = $model->primaryKey;
        $fields = array_merge($fields, $model->getFillable());

        if ($model->readonly) {
            $fields = array_merge($fields, $model->readonly);
            $fields = array_unique($fields);
        }

        return response()->json($fields, 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function getModels()
    {
        $schema = \DB::getDoctrineSchemaManager();
        $tables = [];

        foreach ($schema->listTables() as $table) {
            $tables[] = $table->getName();
        }

        return response()->json($tables, 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function getRules($model, Request $request)
    {
        $model = ucfirst($model);
        $model = new $model;
        if (method_exists($model, 'rules')) {
            return response()->json($model->rules($request));
        }
        return response()->json([]);
    }

    public function getTypes($model)
    {
        $model = ucfirst($model);
        $model = new $model;
        $trans[$model->primaryKey] = \DB::connection()->getDoctrineColumn($model->getTable(), $model->primaryKey)->getType()->getName();


        $fields = $model->getFillable();

        foreach ($fields as $field) {
            try {
                $trans[$field] = \DB::connection()->getDoctrineColumn($model->getTable(), $field)->getType()->getName();
            } catch (\Exception $e) {
                if ($model->fieldType && is_array($model->fieldType)) {
                    $trans[$field] = $model->fieldType[$field];
                } else {
                    $trans[$field] = 'string';
                }
            }
        }

        if ($model->readonly) {
            foreach ($model->readonly as $field) {
                try {
                    if ($model->fieldType && is_array($model->fieldType)) {
                        $trans[$field] = $model->fieldType[$field];
                    } else {
                        $trans[$field] = 'readonly';
                    }
                } catch (\Exception $e) {
                }
            }
        }

        if ($model->adminWith) {
            if (!is_array($model->with)) {
                $model->with = [];
            }

            $model->with = array_merge($model->with, $model->adminWith);
            $model->with = array_unique($model->with);
        }
        // relation:{Model}:{foreign_key}:{with}
        if ($model->with) {
            foreach ($model->with as $with) {

                if (str_replace($model->getTable() . '.', '', $model->$with()->getQualifiedParentKeyName()) == $model->getKeyName()) {
                    if (method_exists($model->$with(), 'getForeignKey') && $model->$with()->getForeignKey() != $model->getKeyName()) {
                        //                                        dd($model->$with()->getForeignKey(), $model->getKeyName());
                        $trans[$model->$with()->getForeignKey()] = 'relation:' . (str_replace('App\\', '', get_class($model->$with()->getRelated()))) . ':' . $model->$with()->getOwnerKey() . ':' . $with;
                    }
                    continue;
                }
                $field = str_replace($model->getTable() . '.', '', $model->$with()->getQualifiedParentKeyName());
                $readonly = false;
                if ($model->readonly) {
                    $readonly = in_array($field, $model->readonly);
                }
                $trans[$field] = ($readonly ? 'readonly:' : 'relation:') . (str_replace('App\\', '', get_class($model->$with()->getRelated()))) . ':' . $model->$with()->getForeignKeyName() . ':' . $with;
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
                $trans[$enumField] = 'enum';
                $enumVarsObj = new \stdClass();
                foreach ($enumVars as $objIndex => $objVal) {
                    $enumVarsObj->$objIndex = $objVal;
                }

                $trans[$enumField . '_enum'] = $enumVarsObj;
            }
        }


        return response()->json($trans, 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function test(Request $request)
    {
        return response()->json(['user' => auth()->user()]);
    }

    public function resizeImage($image, $width = 0)
    {
        $image = 'files/' . $image;
        $i = \Storage::disk('public')->get($image);
//        dd($i);
        $img = Image::cache(function ($img) use ($width, $image, $i) {
            $img = $img->make($i)->orientate();
            if ($width != 0) {
                $img->resize($width, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }
            $img->encode(null, 60);
        }, 10, true);

//        $img->insert('public/watermark.png');
//        $img->save($image);
        return $img->response('jpg');
    }
}
