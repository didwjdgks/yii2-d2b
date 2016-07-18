<?php
namespace d2b\watchers;

use d2b\Http;
use d2b\WatchEvent;

class HI_PEB_OpenNegoResult_Lst extends \yii\base\Component
{
  const URL='http://www.d2b.go.kr/Internet/jsp/peb/HI_PEB_Main.jsp?md=443&cfn=HI_PEB_OpenNegoResult_Lst';

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
      'txtNegoDateFrom'=>date('Y/m/d',strtotime('-7 day')),
      'txtNegoDateTo'=>date('Y/m/d'),
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
            ' <td>(?<constdt>[^<]*)</td>'. //공개협상일시
            ' <td>(?<constno>[^<]*)</td>'. //공사번호
            ' <td>(?<degree>[^<]*)</td>'. //차수
            ' <td>(?<constnm>[^<]*)</td>'. //공개협상건명
            ' <td>(?<org>[^<]*)</td>'. //발주기관
            ' <td>(?<budget>[^<]*)</td>'. //예산금액
            ' <td>(?<bidcls>[^<]*)</td>'. //입찰방법
            ' <td> (?<bidproc>[^<]*)'. //협상결과
              ' <input type="hidden" name="hidCsrtNumb" value="(?<hidCsrtNumb>.*)" />'.
              ' <input type="hidden" name="hidDprtCode" value="(?<hidDprtCode>.*)" />'.
              ' <input type="hidden" name="hidNegnPldt" value="(?<hidNegnPldt>.*)" />'.
              ' <input type="hidden" name="hidNegnDegr" value="(?<hidNegnDegr>.*)" />'.
            ' </td> </tr>#i';
        $p=str_replace(' ','\s*',$p);
        if(preg_match_all($p,$html,$matches,PREG_SET_ORDER)){
          foreach($matches as $m){
            $data=[
              'constno'=>trim($m['constno']),
              'degree'=>trim($m['degree']),
              'constnm'=>trim($m['constnm']),
              'org'=>trim($m['org']),
              'budget'=>trim($m['budget']),
              'bidcls'=>trim($m['bidcls']),
              'constdt'=>trim($m['constdt']),
              'bidproc'=>trim($m['bidproc']),
              'hidDprtCode'=>trim($m['hidDprtCode']),
              'hidCsrtNumb'=>trim($m['hidCsrtNumb']),
              'hidNegnPldt'=>trim($m['hidNegnPldt']),
              'hidNegnDegr'=>trim($m['hidNegnDegr']),
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

