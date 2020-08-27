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

    function str_between( $string,  $start,  $end, $includeDelimiters = false, &$offset = 0)
    {
        if ($string === '' || $start === '' || $end === '') return null;

        $startLength = strlen($start);
        $endLength = strlen($end);

        $startPos = strpos($string, $start, $offset);
        if ($startPos === false) return null;

        $endPos = strpos($string, $end, $startPos + $startLength);
        if ($endPos === false) return null;

        $length = $endPos - $startPos + ($includeDelimiters ? $endLength : -$startLength);
        if (!$length) return '';

        $offset = $startPos + ($includeDelimiters ? 0 : $startLength);

        $result = substr($string, $offset, $length);

        return ($result !== false ? $result : null);
    }

    function str_between_all( $string,  $start,  $end,  $includeDelimiters = false,  &$offset = 0)
    {
        $strings = [];
        $length = strlen($string);

        while ($offset < $length)
        {
            $found = \str_between($string, $start, $end, $includeDelimiters, $offset);
            if ($found === null) break;

            $strings[] = $found;
            $offset += strlen($includeDelimiters ? $found : $start . $found . $end); // move offset to the end of the newfound string
        }

        return $strings;
    }


    /**
     * @param Illuminate\Database\Eloquent\Builder|Illuminate\Database\Eloquent\Relations\Relation $query
     * @param array $scopes
     */
    function apply_query_scopes(&$query, array $scopes)
    {
//        dd(123);
        foreach ($scopes as $key => $value2){

            $value = explode(',', $value2, 2);
            if(mb_strtolower($value[0]) === 'withoutglobalscopes'){
                continue;
            }

            $method = new ReflectionMethod(get_class($query->getModel()), 'scope'.ucfirst($value[0]));
            $count = $method->getNumberOfParameters();

            $replaced =  str_between_all($value2, '[', ']', true);
            foreach($replaced as $rep){
                $value2 = str_replace($rep, str_replace(',', '||||||||++|', $rep), $value2);
            }
            $value = explode(',', $value2, $count);
            if (!method_exists($query->getModel(), 'scope'.ucfirst($value[0]))){
                continue;
            }

            foreach($value as &$val){
                $val = str_replace('||||||||++|', ',', $val);
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