<?php
namespace d2b\watchers;

use d2b\Http;
use d2b\WatchEvent;

class HI_PDB_Announce_Lst extends \yii\base\Component
{
  const URL='http://www.d2b.go.kr/Internet/jsp/pdb/HI_PDB_Main.jsp?md=221&cfn=HI_PDB_Announce_Lst';

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
      'selDateDivs'=>0, //공고일자
      'txtDateFrom'=>date('Y/m/d',strtotime('-7 day')),
      'txtDateTo'=>date('Y/m/d'),
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
            ' <td>\d+</td>'. //순번
            ' <td>(?<noticedt>[^<]*)</td>'. //공고일자
            ' <td>(?<status>[^A-Z]*)(?<notinum>[0-9A-Z]{7}-\d{1})<br />(?<g2bno>[^<]*)</td>'. //공고번호
            ' <td>(?<constno>[^<]*)</td>'. //공사번호
            ' <td>(?<constnm>[^<]*)</td>'. //공고명
            ' <td>(?<org>[^<]*)</td>'. //발주처
            ' <td>[^<]*<br />(?<registdt>[^<]*)<br />(?<closedt>[^<]*)<br />(?<constdt>[^<]*)</td>'. //날짜
            ' <td>(?<contract>[^<]*)<br />[^<]*</td>'.
            ' <td>(?<basic>[^<]*)'.
              ' <input type="hidden" name="hidRqstYear" value="(?<hidRqstYear>[^>]*)" />'.
              ' <input type="hidden" name="hidDprtCode" value="(?<hidDprtCode>[^>]*)" />'.
              ' <input type="hidden" name="hidDcsnNumb" value="(?<hidDcsnNumb>[^>]*)" />'.
              ' <input type="hidden" name="hidBidxDate" value="(?<hidBidxDate>[^>]*)" />'.
              ' <input type="hidden" name="hidAnmtDivs" value="(?<hidAnmtDivs>[^>]*)" />'.
              ' <input type="hidden" name="hidAnmtNumb" value="(?<hidAnmtNumb>[^>]*)" />'.
              ' <input[^>]*>'.
              ' <input type="hidden" name="hidRqstDegr" value="(?<hidRqstDegr>[^>]*)" />'.
              '( <input[^>]*>)*'.
            ' </td> </tr>#i';
        $p=str_replace(' ','\s*',$p);
        if(preg_match_all($p,$html,$matches,PREG_SET_ORDER)){
          foreach($matches as $m){
            $data=[
              'noticedt'=>trim($m['noticedt']),
              'status'=>trim($m['status']),
              'notinum'=>trim($m['notinum']),
              'g2bno'=>trim($m['g2bno']),
              'constno'=>trim($m['constno']),
              'constnm'=>trim($m['constnm']),
              'org'=>trim($m['org']),
              'registdt'=>trim($m['registdt']),
              'closedt'=>trim($m['closedt']),
              'constdt'=>trim($m['constdt']),
              'contract'=>trim($m['contract']),
              'basic'=>trim($m['basic']),
              'hidRqstYear'=>trim($m['hidRqstYear']),
              'hidDprtCode'=>trim($m['hidDprtCode']),
              'hidBidxDate'=>trim($m['hidBidxDate']),
              'hidAnmtDivs'=>trim($m['hidAnmtDivs']),
              'hidAnmtNumb'=>trim($m['hidAnmtNumb']),
              'hidRqstDegr'=>trim($m['hidRqstDegr']),
              'hidDcsnNumb'=>trim($m['hidDcsnNumb']),
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
    $html=strip_tags($html,'<tr><td><input><br>');
    $html=str_replace('&nbsp;','',$html);
    $html=preg_replace('/<tr[^>]*>/i','<tr>',$html);
    $html=preg_replace('/<td[^>]*>/i','<td>',$html);
    return $html;
  }
}

