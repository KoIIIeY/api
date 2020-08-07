<?php
/**
 * User: Michael Lazarev <mihailaz.90@gmail.com>
 * Date: 10.06.16
 * Time: 11:42
 */

if (!function_exists('apply_query_filters'))
{
	/**
	 * @param Illuminate\Database\Eloquent\Builder|Illuminate\Database\Query\Builder|Illuminate\Database\Eloquent\Relations\Relation $query
	 * @param array $filter
	 */
	function apply_query_filters($query, array $filter)
	{
		foreach ($filter as $key => $value){
			if (!is_array($value)){
				if($fromJson = json_decode($value)){
					$value = $fromJson;
				} else {
					$value = [$value];
				}
			}
			if(!is_array($value)){
				$value = [$value];
			}
			if (!is_numeric($key)){
				array_unshift($value, $key);
			}

			if(isset($value[0]) && strpos($value[0], '.') !== false){
				$exploded = explode('.', $value[0]);
				$field = $exploded[count($exploded)-1];
				$relation = $exploded;
				unset($relation[count($exploded)-1]);

				$relation  = implode('.', $relation);

				$val = $value;
				$val[0] = $field;

                if ($value[1] == 'in') {
                    $val2 = [];
                    $val2[] = $field;
                    $val2[] = $value[2];

                    $query->whereHas($relation, function($q) use ($val2, $query, $relation) {
                        $val2[0] = $q->getModel()->getTable() . '.' . $val2[0];
                        call_user_func_array([$q, 'whereIn'], $val2);
                    });
                    continue;
                }

				$query->whereHas($relation, function($q) use ($val, $query, $relation) {
                    $val[0] = $q->getModel()->getTable() . '.' . $val[0];
					call_user_func_array([$q, 'where'], $val);
				});

				continue;
			}

            if ($value[1] == 'in') {
                $val = [];
                $val[] = $value[0];
                $val[] = $value[2];
                call_user_func_array([$query, 'whereIn'], $val);
                continue;
            }

			call_user_func_array([$query, 'where'], $value);
		}
	}
}

if (!function_exists('apply_query_scopes'))
{
	/**
	 * @param Illuminate\Database\Eloquent\Builder|Illuminate\Database\Eloquent\Relations\Relation $query
	 * @param array $scopes
	 */
	function apply_query_scopes(&$query, array $scopes)
	{
		foreach ($scopes as $key => $value2){
			$value = explode(',', $value2, 2);
			if(mb_strtolower($value) === 'withoutGlobalScopes'){
			    continue;
            }

			$method = new ReflectionMethod(get_class($query->getModel()), 'scope'.ucfirst($value[0]));
			$count = $method->getNumberOfParameters();

            $value = explode(',', $value2, $count);
			if (!method_exists($query->getModel(), 'scope'.ucfirst($value[0]))){
				continue;
			}
			if(!isset($value[1])){
				$value[1] = json_encode([]);
			}
//			$query->{$value[0]}(json_decode($value[1], true));
            $method = $value[0];
			array_shift($value);
//			array_unshift($value, $query);

			call_user_func_array([$query, $method], $value);
		}
		return $query;
	}
}

if (!function_exists('apply_query_with'))
{
	/**
	 * @param Illuminate\Database\Eloquent\Builder|Illuminate\Database\Eloquent\Relations\Relation $query
	 * @param array $with
	 */
	function apply_query_with($query, array $with)
	{
		$query->with((array) $with);
	}
}