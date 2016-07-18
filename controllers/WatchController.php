<?php
namespace d2b\controllers;

use yii\helpers\Console;

use d2b\WatchEvent;

use d2b\watchers\HI_PEB_Announce_Lst;
use d2b\watchers\HI_PEB_OpenNego_Lst;

use d2b\watchers\HI_PEB_BidResult_Lst;
use d2b\watchers\HI_PEB_OpenNegoResult_Lst;
use d2b\watchers\HI_PDB_BidResult_Lst;
use d2b\watchers\HI_PDB_OpenNegoResult_Lst;

use d2b\models\BidKey;

class WatchController extends \yii\console\Controller
{
  public function actionBid(){
    $peb=new HI_PEB_Announce_Lst;
    $peb->on(WatchEvent::EVENT_ROW,[$this,'onBidPebRow']);

    $peb2=new HI_PEB_OpenNego_Lst;
    $peb2->on(WatchEvent::EVENT_ROW,[$this,'onBidPeb2Row']);

    while(true){
      try {
        $this->stdout("==시설공사 입찰공고==\n",Console::FG_YELLOW);
        $peb->watch();
        $this->stdout("==시설공사 공개협상계획==\n",Console::FG_YELLOW);
        $peb2->watch();
      }
      catch(\Exception $e){
        $this->stdout($e.PHP_EOL,Console::FG_RED);
        \Yii::error($e,'d2b');
      }
      $this->stdout(sprintf("[%s] Peak memory usage: %s MB\n",
        date('Y-m-d H:i:s'),
        (memory_get_peak_usage(true)/1024/1024)),
        Console::FG_GREY
      );
      \d2b\Http::sleep();
    }
  }

  /**
   * 시설공사 경쟁입찰 공고 row
   */
  public function onBidPebRow($event){
    $row=$event->row;
    $out[]="[D2B] %g{$row['notinum']}%n %c{$row['constno']}%n {$row['constnm']} [{$row['status']}]";

    $bidkey=BidKey::find()->where([
      'whereis'=>'10',
      'notinum'=>$row['notinum'],
      'notinum_ex'=>$row['constno'],
      'state'=>['Y','N'],
    ])->orderBy('bidid desc')->limit(1)->one();
    if($bidkey===null){
      if($row['contract']!='지명경쟁'){
        $out[]="%rNEW%n";
      }
    }else $out[]="({$bidkey->bidproc})";
    $this->stdout(Console::renderColoredString(join(' ',$out)).PHP_EOL);
  }

  /**
   * 시설공사 공개협상 공고 row
   */
  public function onBidPeb2Row($event){
    $row=$event->row;
    $out[]="[D2B] %c{$row['constno']}%n {$row['constnm']} [{$row['bidproc']}]";

    $bidkey=BidKey::find()->where([
      'whereis'=>'10',
      'notinum'=>$row['hidAnmtNumb'].'-'.$row['degree'],
      'notinum_ex'=>$row['constno'],
      'state'=>['Y','N'],
    ])->orderBy('bidid desc')->limit(1)->one();
    if($bidkey===null){
      $out[]="%rNEW%n";
    }else{
      $out[]="({$bidkey->bidproc})";
      if($row['bidproc']=='공개협상취소' and $bidkey->bidproc!=='C'){
        $out[]="%r취소%n";
      }
    }
    $this->stdout(Console::renderColoredString(join(' ',$out)).PHP_EOL);
  }

  public function actionSuc(){
    $peb=new HI_PEB_BidResult_Lst;
    $peb->on(WatchEvent::EVENT_ROW,[$this,'onSucPebRow']);

    $peb2=new HI_PEB_OpenNegoResult_Lst;
    $peb2->on(WatchEvent::EVENT_ROW,[$this,'onSucPebRow2']);
    
    $pdb=new HI_PDB_BidResult_Lst;
    $pdb->on(WatchEvent::EVENT_ROW,[$this,'onSucPdbRow']);

    $pdb2=new HI_PDB_OpenNegoResult_Lst;
    $pdb2->on(WatchEvent::EVENT_ROW,[$this,'onSucPdb2Row']);

    while(true){
      try{
        $this->stdout("==시설공사 경쟁입찰 결과==\n",Console::FG_YELLOW);
        $peb->watch();
        $this->stdout("==시설공사 공개수의협상 결과==\n",Console::FG_YELLOW);
        $peb2->watch();
        $this->stdout("==국내조달 경쟁입찰 결과==\n",Console::FG_YELLOW);
        $pdb->watch();
        $this->stdout("==국내조달 공개수의협상 결과==\n",Console::FG_YELLOW);
        $pdb2->watch();
      }
      catch(\Exception $e){
        $this->stdout($e.PHP_EOL,Console::FG_RED);
        \Yii::error($e,'d2b');
      }
      $this->stdout(sprintf("[%s] Peak memory usage: %s MB\n",
        date('Y-m-d H:i:s'),
        (memory_get_peak_usage(true)/1024/1024)),
        Console::FG_GREY
      );
      \d2b\Http::sleep();
    }
  }

  public function onSucPebRow($event){
    $row=$event->row;
    $out[]=Console::renderColoredString(
      " %g{$row['notinum']}%n %c{$row['constno']}%n {$row['constnm']} ({$row['bidproc']})"
    );

    $bidkey=BidKey::findOne([
      'whereis'=>'10',
      'notinum'=>$row['notinum'],
      'notinum_ex'=>$row['constno'],
      'state'=>'Y',
    ]);
    if($bidkey!==null){
      if($row['bidproc']==='유찰'){
        if($bidkey->bidproc==='F' or $bidkey->bidproc==='M'){
          $out[]='PASS';
        }else{
          $out[]=Console::renderColoredString("%yNEW%n");
        }
      }
      else if($bidkey->bidproc==='S'){
        $out[]='PASS';
      }
      else{
        $out[]=Console::renderColoredString("%yNEW%n");
      }
    }else{
      $out[]=Console::renderColoredString("%rMISS%n");
    }

    $this->stdout(join(' ',$out).PHP_EOL);
  }

  public function onSucPebRow2($event){
    $row=$event->row;
    $out[]=Console::renderColoredString(
      " %c{$row['constno']}%n %g{$row['degree']}%n {$row['constnm']} ({$row['bidproc']})"
    );

    $bidkey=BidKey::find()->where(['whereis'=>'10','notinum_ex'=>$row['constno'],'state'=>'Y'])
      ->andWhere("notinum like '{$row['hidDprtCode']}%-{$row['degree']}'")
      ->one();
    if($bidkey!==null){
      if($row['bidproc']==='유찰'){
        if($bidkey->bidproc==='F' or $bidkey->bidproc==='M'){
          $out[]='PASS';
        }else{
          $out[]=Console::renderColoredString("%yNEW%n");
        }
      }
      else if($bidkey->bidproc==='S'){
        $out[]='PASS';
      }
      else{
        $out[]=Console::renderColoredString("%yNEW%n");
      }
    }else{
      $out[]=Console::renderColoredString("%rMISS%n");
    }

    $this->stdout(join(' ',$out).PHP_EOL);
  }

  //국내조달 경쟁입찰 개찰결과 row
  public function onSucPdbRow($event){
    $row=$event->row;
    $out[]=Console::renderColoredString(
      " %g{$row['notinum']}%n %c{$row['constno']}%n {$row['constnm']} ({$row['bidproc']})"
    );

    $bidkey=BidKey::findOne([
      'whereis'=>'10',
      'notinum'=>$row['notinum'],
      'notinum_ex'=>$row['constno'],
      'state'=>'Y',
    ]);
    if($bidkey!==null){
      if($row['bidproc']==='유찰'){
        if($bidkey->bidproc==='F' or $bidkey->bidproc==='M'){
          $out[]='PASS';
        }else{
          $out[]=Console::renderColoredString("%yNEW%n");
        }
      }
      else if($bidkey->bidproc==='S'){
        $out[]='PASS';
      }
      else{
        $out[]=Console::renderColoredString("%yNEW%n");
      }
    }else{
      $out[]=Console::renderColoredString("%rMISS%n");
    }

    $this->stdout(join(' ',$out).PHP_EOL);
  }

  //국내조달 공개수의협정 개찰결과 row
  public function onSucPdb2Row($event){
    $row=$event->row;
    $out[]=Console::renderColoredString(
      " %c{$row['constno']}%n %g{$row['degree']}%n {$row['constnm']} ({$row['bidproc']})"
    );

    $bidkey=BidKey::findOne([
      'whereis'=>'10',
      'notinum'=>$row['hidAnmtNumb'].'-'.$row['degree'],
      'notinum_ex'=>$row['constno'],
      'state'=>'Y'
    ]);
    if($bidkey!==null){
      if($row['bidproc']==='유찰'){
        if($bidkey->bidproc==='F' or $bidkey->bidproc==='M'){
          $out[]='PASS';
        }else{
          $out[]=Console::renderColoredString("%yNEW%n");
        }
      }
      else if($bidkey->bidproc==='S'){
        $out[]='PASS';
      }
      else{
        $out[]=Console::renderColoredString("%yNEW%n");
      }
    }else{
      $out[]=Console::renderColoredString("%rMISS%n");
    }

    $this->stdout(join(' ',$out).PHP_EOL);
  }
}

