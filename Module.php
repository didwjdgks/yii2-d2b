<?php
namespace d2b;

use yii\db\Connection;
use yii\di\Instance;

class Module extends \yii\base\Module
{
  public $db='i2db';
  public $gman_server;

  public function init(){
    parent::init();

    $this->db=Instance::ensure($this->db,Connection::className());
  }
}

