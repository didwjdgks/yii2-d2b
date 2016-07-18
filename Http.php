<?php
namespace d2b;

class Http extends \yii\base\Component
{
  protected $client;

  public function init(){
    parent::init();
    $this->client=new \GuzzleHttp\Client([
      'base_uri'=>'http://www.d2b.go.kr',
      'cookies'=>true,
      'allow_redirects'=>false,
      'headers'=>[
        'User-Agent'=>'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 10.0; WOW64; Trident/7.0)',
        'Connection'=>'keep-alive',
        'Prgama'=>'no-cache',
      ],
    ]);
  }

  public function request($method,$uri='',array $options=[]){
    $res=$this->client->request($method,$uri,$options);
    $body=$res->getBody();
    $html=iconv('euckr','utf-8//IGNORE',$body);
    return $html;
  }

  public function post($uri,array $options=[]){
    return $this->request('POST',$uri,$options);
  }

  public static function sleep(){
    sleep(mt_rand(1,3));
  }
}

