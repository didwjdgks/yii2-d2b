<?php
namespace d2b\watchers;

use d2b\Http;
use d2b\WatchEvent;

/**
 * 국내조달 공개수의협상 결과 watcher
 */
class HI_PDB_OpenNegoResult_Lst extends \yii\base\Component
{
  const URL='http://www.d2b.go.kr/Internet/jsp/pdb/HI_PDB_Main.jsp?md=243&cfn=HI_PDB_OpenNegoResult_Lst';

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
            ' <td>[^<]*</td>'. //항목번호
            ' <td>(?<degree>[^<]*)</td>'. //차수
            ' <td> <a[^>]*submitForm\(\'(?<link>[^\)]+)\'[^>]*>(?<constnm>[^<]*)</a> </td>'. //공개협상건명
            ' <td>(?<org>[^<]*)</td>'. //발주기관
            ' <td>(?<budget>[^<]*)</td>'. //예산금액
            ' <td>(?<bidcls>[^<]*)</td>'. //입찰방법
            ' <td>(?<bidproc>[^<]*)</td>'.
           ' </tr>#i';
        $p=str_replace(' ','\s*',$p);
        if(preg_match_all($p,$html,$matches,PREG_SET_ORDER)){
          foreach($matches as $m){
            $link=preg_replace('/\s*/','',$m['link']);
            $links=explode("','",$link);
            $data=[
              'constno'=>trim($m['constno']),
              'degree'=>trim($m['degree']),
              'constnm'=>trim($m['constnm']),
              'org'=>trim($m['org']),
              'budget'=>trim($m['budget']),
              'bidcls'=>trim($m['bidcls']),
              'constdt'=>trim($m['constdt']),
              'bidproc'=>trim($m['bidproc']),
              'numb'=>$links[0],
              'hidOrdrYear'=>$links[1],
              'hidDcsnNumb'=>$links[2],
              'hidDprtCode'=>$links[3],
              'hidNegnPldt'=>$links[4],
              'hidNegnDegr'=>$links[5],
              'hidDmstItnb'=>$links[6],
              'hidAnmtNumb'=>$links[7],
              'hidBidxMthd'=>$links[8],
              'hidOrdrAmnt'=>$links[9],
              'action'=>$links[10],
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

