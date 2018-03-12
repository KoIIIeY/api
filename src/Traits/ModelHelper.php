<?php

namespace Koiiiey\Api\Traits;

/**
 * Created by PhpStorm.
 * User: Makhnev
 * Date: 08.08.2016
 * Time: 14:45
 */
trait ModelHelper
{

    public function getModelKeys(){
        return $this->fillable();
    }

    public function getKeys(){
//        return $this->visible;
        return array_merge($this->visible, $this->appends);
    }

    public function getPrimaryName(){
        return $this->primaryKey;
    }

    public function getPrimary(){
        return $this->{$this->primaryKey};
    }

    public function save(array $options = []){

        if(method_exists($this, 'checkAccess')){
            if($this->checkAccess()){
                return parent::save($options);
            }
            return false;
        }

        return parent::save($options);

    }
}