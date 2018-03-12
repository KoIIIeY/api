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
				$query->whereHas($relation, function($q) use ($val, $query, $relation) {
					call_user_func_array([$q, 'where'], $val);
				});

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
	function apply_query_scopes($query, array $scopes)
	{
		foreach ($scopes as $key => $value){
			$value = explode(',', $value, 2);
			if (!method_exists($query->getModel(), 'scope'.ucfirst($value[0]))){
				continue;
			}
			if(!isset($value[1])){
				$value[1] = json_encode([]);
			}
			$query->{$value[0]}(json_decode($value[1], true));
//			call_user_func_array($query, $value);
		}
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

if (!function_exists('test_user_answers'))
{
    function test_user_answers($testid, $userid)
    {
        if (!$testid || !$userid)
        {
            return false;
        }

        $correctAnswers = 0;
        $wrongAnswers = 0;

        $test =
            \App\Test::withoutGlobalScopes()
                ->where('test_id', '=', $testid)
                ->first();

        $questionCount = 0;

        if ($test)
        {
            $questionCount = count($test->questions);
        }

        $userAnswers =
            \App\UserAnswer::withoutGlobalScopes()
                ->where('user_id', '=', $userid)
                ->where('test_id', '=', $testid)
                ->get();

        $userAnswersCount = count($userAnswers);

        if ($userAnswersCount)
        {
            foreach ($userAnswers as $answer)
            {
                if ($answer->answer->is_correct)
                {
                    $correctAnswers++;
                }
                else
                {
                    $wrongAnswers++;
                }
            }
        }

        if ($test)
        {
            $delta = $questionCount - $userAnswersCount;

            if ($delta >= 0)
            {
                $wrongAnswers += $delta;
            }
        }

        return [
            'correct_answers' => $correctAnswers,
            'wrong_answers' => $wrongAnswers
        ];
    }
}

if (!function_exists('entity_enum_variants'))
{
    function entity_enum_variants() {
        return [
            'get' => [
                'deny' => 'Запретить',
                'allow'=> 'Разрешить',
                'inherit'=> 'Наследовать',
            ],
            'post' => [
                'deny' => 'Запретить',
                'allow'=> 'Разрешить',
                'inherit'=> 'Наследовать',
            ],
            'put' => [
                'deny' => 'Запретить',
                'allow'=> 'Разрешить',
                'inherit'=> 'Наследовать',
            ],
            'delete' => [
                'deny' => 'Запретить',
                'allow'=> 'Разрешить',
                'inherit'=> 'Наследовать',
            ],
            'entity' => [
                'Test' => 'Тесты',
                'Question' => 'Вопросы',
                'Answer' => 'Ответы',
                'UserAnswer' => 'Ответы пользователей',
                'UserStartedTest' => 'Начатые тесты пользователей',
                'UserCompletedTest' => 'Выполненные тесты пользователей',
                'UserStartedQuestion' => 'Начатые вопросы пользователей в тестах',
                'UserCompletedQuestion' => 'Выполненные вопросы пользователей в тестах',
                'User' => 'Пользователи',
                'Role' => 'Роли',
                'UserRole' => 'Роли пользователей',
                'ManagerRole' => 'Роли менеджеров',
                'RolePermission' => 'Глобальные права ролей',
                'RoleEntityIdPermission' => 'Локальные права ролей',
                'UserEntityPermission' => 'Глобальные права пользователей',
                'UserEntityIdPermission' => 'Локальные права пользователей',
                'Tutorial' => 'Обучающие материалы',
                'News' => 'Новости',
                'Meal' => 'Блюда',
                'MealCategory' => 'Категории блюд',
            ]
        ];
    }
}