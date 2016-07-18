<?php
namespace d2b\models;

class BidKey extends \i2\models\BidKey
{
  public static function getDb(){
    return \d2b\Module::getInstance()->db;
  }
}

