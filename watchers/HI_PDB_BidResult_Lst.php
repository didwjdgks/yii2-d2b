<?php
namespace d2b\watchers;

use d2b\Http;
use d2b\WatchEvent;

/**
 * 국내조달 경쟁입찰 결과 watcher
 */
class HI_PDB_BidResult_Lst extends \yii\base\Component
{
  const URL='http://www.d2b.go.kr/Internet/jsp/pdb/HI_PDB_Main.jsp?cfn=HI_PDB_BidResult_Lst&md=227';

  public $http;
  public $params;

  public function init(){
    parent::init();
    $this->http=new Http;
  }

  public function watch(){
    $this->params=[
      'pageNo'=>1,
      'startPageNo'=>1,
      'pagePerRow'=>10,
      'txtBidDateFrom'=>date('Y/m/d',strtotime('-7 day')),
      'txtBidDateTo'=>date('Y/m/d'),
    ];
    try{
      $html=$this->http->post(static::URL,['form_params'=>$this->params]);
      $html=static::strip_tags($html);

      if(preg_match('/전체 페이지 : (?<total_page>\d+)/i',$html,$m)){
        $total_page=$m['total_page'];
      }
      if(!$total_page) throw new \Exception('Empty total_page');

      for($page=1; $page<=$total_page; $page++){
        if($page>1){
          $this->params['pageNo']=$page;
          $html=$this->http->post(static::URL,['form_params'=>$this->params]);
          $html=static::strip_tags($html);
        }

        $p='#<tr>'.
            ' <td>(?<status>[^A-Z]*)(?<notinum>[0-9A-Z]{7}-\d{1})</td>'.
            ' <td>(?<constno>[^<]*)</td>'.
            ' <td>[^<]*</td>'.
            //' <td> <a[^>]*gotoInf\(\'(?<link>[^;]+)\'\);[^>]*>(?<constnm>[^<]*)</a> </td>'.
            ' <td> <a[^>]*>(?<constnm>[^<]*)</a> </td>'.
            ' <td>(?<org>[^<]*)</td>'.
            ' <td>(?<contract>[^<]*)</td>'.
            ' <td>(?<bidcls>[^<]*)</td>'.
            ' <td>(?<succls>[^<]*)</td>'.
            ' <td>(?<constdt>[^<]*)</td>'.
            ' <td>(?<bidproc>[^<]*)</td>'.
           ' </tr>#i';
        $p=str_replace(' ','\s*',$p);
        if(preg_match_all($p,$html,$matches,PREG_SET_ORDER)){
          foreach($matches as $m){
            $data=[
              'status'=>trim($m['status']),
              'notinum'=>trim($m['notinum']),
              'constno'=>trim($m['constno']),
              'constnm'=>trim($m['constnm']),
              'org'=>trim($m['org']),
              'contract'=>trim($m['contract']),
              'bidcls'=>trim($m['bidcls']),
              'succls'=>trim($m['succls']),
              'constdt'=>trim($m['constdt']),
              'bidproc'=>trim($m['bidproc']),
            ];
            $event=new WatchEvent;
            $event->row=$data;
            $this->trigger(WatchEvent::EVENT_ROW,$event);
          }
        }
        \d2b\Http::sleep();
      }
    }
    catch(\Exception $e){
      throw $e;
    }
  }

  public static function strip_tags($html){
    $html=strip_tags($html,'<tr><td><a>');
    $html=str_replace('&nbsp;','',$html);
    $html=preg_replace('/<tr[^>]*>/i','<tr>',$html);
    $html=preg_replace('/<td[^>]*>/i','<td>',$html);
    return $html;
  }
}

