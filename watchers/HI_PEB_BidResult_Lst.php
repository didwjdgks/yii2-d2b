<?php
namespace d2b\watchers;

use d2b\Http;
use d2b\WatchEvent;

class HI_PEB_BidResult_Lst extends \yii\base\Component
{
  const URL='http://www.d2b.go.kr/Internet/jsp/peb/HI_PEB_Main.jsp?md=428&cfn=HI_PEB_BidResult_Lst';

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
      'txtBidxDateFrom'=>date('Y/m/d',strtotime('-7 day')),
      'txtBidxDateTo'=>date('Y/m/d'),
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
            ' <td>(?<constnm>[^<]*)</td>'.
            ' <td>(?<org>[^<]*)</td>'.
            ' <td>(?<contract>[^<]*)</td>'.
            ' <td>(?<bidcls>[^<]*)</td>'.
            ' <td>(?<succls>[^<]*)</td>'.
            ' <td>(?<constdt>[^<]*)</td>'.
            ' <td> (?<bidproc>[^<]*)'.
              ' <input type="hidden" name="hidDprtCode" value="(?<hidDprtCode>.*)" />'.
              ' <input type="hidden" name="hidCsrtNumb" value="(?<hidCsrtNumb>.*)" />'.
              ' <input type="hidden" name="hidBenfDegr" value="(?<hidBenfDegr>.*)" />'.
              ' <input type="hidden" name="hidAnmtNumb" value="(?<hidAnmtNumb>.*)" />'.
              ' <input type="hidden" name="hidRqstDegr" value="(?<hidRqstDegr>.*)" />'.
              ' <input type="hidden" name="hidBenfPldt" value="(?<hidBenfPldt>.*)" />'.
            ' </td> </tr>#i';
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
              'hidDprtCode'=>trim($m['hidDprtCode']),
              'hidCsrtNumb'=>trim($m['hidCsrtNumb']),
              'hidBenfDegr'=>trim($m['hidBenfDegr']),
              'hidAnmtNumb'=>trim($m['hidAnmtNumb']),
              'hidRqstDegr'=>trim($m['hidRqstDegr']),
              'hidBenfPldt'=>trim($m['hidBenfPldt']),
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
    $html=strip_tags($html,'<tr><td><input>');
    $html=str_replace('&nbsp;','',$html);
    $html=preg_replace('/<tr[^>]*>/i','<tr>',$html);
    $html=preg_replace('/<td[^>]*>/i','<td>',$html);
    return $html;
  }
}

