<?php

ini_set('log_errors','off');  //ログを取るか
ini_set('error_log','php.log');  //ログの出力ファイルを指定
session_start(); //セッション使う

// 性別クラス
class Sex{
  const MAN = 1;
  const WOMAN = 2;
}

abstract class Creature{
  protected $name;
  protected $hp;
  protected $attackMin;
  protected $attackMax;
  protected $targetImage;
  abstract public function sayCry();

  public function setName($name){
    $this->name = $name;
  }

  public function getName(){
    return $this->name;
  }

  public function setHp($hp){
    $this->hp = $hp;
  }

  public function getHp(){
    return $this->hp;
  }

  public function setImage($targetImage){
    $this->targetImage = $targetImage;
  }

  public function getImage(){
    return $this->targetImage;
  }

  public function setAttackMin($attackMin){
    $this->attackMin = $attackMin;
  }

  public function getAttackMin(){
    return $this->attackMin;
  }

  public function setAttackMax($attackMax){
    $this->attackMax = $attackMax;
  }

  public function getAttackMax(){
    return $this->attackMax;
  }

  public function attack($targetObj){
    $attackPoint = mt_rand($this->attackMin, $this->attackMax);
    if(!mt_rand(0,9)){ //10分の1の確率でクリティカル
      $attackPoint = $attackPoint * 1.5;
      $attackPoint = (int)$attackPoint;
      History::set($this->getName().'のクリティカルヒット!!');
    }
    $targetObj->setHp($targetObj->getHp()-$attackPoint);
    History::set($attackPoint.'ポイントのダメージ！');
  }
}

class Human extends Creature {
  protected $sex;
  protected $rolename;
  protected $tp;
  protected $special_attack;

  public function __construct($name,$human_img,$sex, $rolename, $hp, $tp, $special_attack_name, $attackMin, $attackMax) {
    $this->setName($name);
    $this->setImage($human_img);
    $this->setSex($sex);
    $this->setHp($hp);
    $this->setTp($tp);
    $this->setRole($rolename);
    $this->setSpecialAttackName($special_attack_name);
    $this->setAttackMin($attackMin);
    $this->setAttackMax($attackMax);
  }

  public function setSex($num){
    $this->sex = $num;
  }

  public function getSex(){
    return $this->sex;
  }

  public function setTp($tp){
    $this->tp = $tp;
  }

  public function getImg(){
    return $this->human_img;
  }

  public function getTp(){
    return $this->tp;
  }

  public function setRole($rolename){
    $this->rolename = $rolename;
  }

  public function getRole(){
    return $this->rolename;
  }

  public function setSpecialAttackName($special_attack_name){
    $this->special_attack_name = $special_attack_name;
  }

  public function getSpecialAttackName(){
    return $this->special_attack_name;
  }

  public function special_attack($special_attack_name,$targetObj){
    $special_attack_point = 200 * mt_rand(10,100);
    $targetObj->setHp($targetObj->getHp()- $special_attack_point);
    $this->setTp($this->getTp()-100);
    History::set($special_attack_name.'を発動！'.$special_attack_point.'ポイントのダメージ！');
  }

  public function use_item($item_name,$targetObj){
    $_SESSION['itemcount'] -= 1;
    if ($_SESSION['itemcount'] <= 0) {
      History::set('もう使用できない');
      goto end;
    }
    switch ($item_name) {
      case '回復薬':
        $_SESSION['human']->setHp($_SESSION['human']->getHp()+500);
        History::set($item_name.'を使った！ HPを500ポイント回復！');
      break;
      case '爆弾':
        $_SESSION[$targetObj]->setHp($_SESSION[$targetObj]->getHp()-1000);
        History::set($item_name.'を使った！ 1000ポイントのダメージだ!');
      break;
      case '毒針':
        $_SESSION['poison_count'] = 5;
        History::set($item_name.'を使った！'.$_SESSION['poison_count'].'ターンの間継続ダメージを与える!');
      break;
    }
    end:
  }

  public function sayCry(){
    History::set($this->name.'が叫ぶ！');
    switch($this->sex){
      case Sex::MAN :
        History::set('グハッ');
        break;
      case Sex::WOMAN :
        History::set('きゃっ！');
        break;
    }
  }
}

class Monster extends Creature{
  protected $mp;
  protected $monster_element;

  public function __construct($name, $monster_img, $monster_element, $hp, $mp, $attackMin, $attackMax) {
    $this->setName($name);
    $this->setHp($hp);
    $this->setElement($monster_element);
    $this->setMp($mp);
    $this->setImage($monster_img);
    $this->setAttackMin($attackMin);
    $this->setAttackMax($attackMax);
  }

  public function setElement($monster_element){
    $this->monster_element = $monster_element;
  }

  public function getElement(){
    return $this->monster_element;
  }

  public function setMp($mp){
    $this->mp = $mp;
  }

  public function getMp(){
    return $this->mp;
  }

  public function element_attack($monster_element,$targetObj){
    switch ($monster_element) {
      case '炎':
      case '氷':
      case '雷':
      case '水':
      case '風':
      $element_attack_point = 700;
      break;

      case '闇':
      case '光':
      $element_attack_point = 300 * mt_rand(3,10);
      break;
    }
    $targetObj->setHp($targetObj->getHp()- $element_attack_point);
    $this->setMp($this->getMp()-100);
    History::set($monster_element.'属性攻撃を発動！'.$element_attack_point.'ポイントのダメージ！');
  }

  public function sayCry(){
    History::set($this->name.'が叫ぶ！');
    History::set('グルァ');
  }

}

interface HistoryInterface{
  public static function set($str);
  public static function clear();
}

// 履歴管理クラス
class History implements HistoryInterface{
  public static function set($str){
    // セッションhistoryが作られてなければ作る
    if(empty($_SESSION['history'])) $_SESSION['history'] = '';
    // 文字列をセッションhistoryへ格納
    $_SESSION['history'] .= $str.'<br>';
  }
  public static function clear(){
    unset($_SESSION['history']);
  }
}

//人間インスタンス作成
$humans = array();
$humans[] = new  Human('主人公', 'img/human/braver.png',Sex::MAN, '勇者', 10000, 400, 'エクスカリバー', 100, 1000);
$humans[] = new  Human('ヒロイン', 'img/human/heroin.png', Sex::WOMAN, '聖女', 6000, 900, 'ホーリー', 50,500);
$humans[] = new  Human('格闘家', 'img/human/fighter.png', Sex::MAN, '格闘家', 8000, 500, 'ブレイズナックル', 120, 600);
$humans[] = new  Human('騎士', 'img/human/night.png', Sex::MAN, '騎士', 9000, 300, 'カイザーブレイド', 90, 900);
$humans[] = new  Human('踊り子', 'img/human/odoriko.png', Sex::WOMAN, '踊り子', 7000, 1200, 'バーンサークル', 70, 500);
$humans[] = new  Human('暗殺者', 'img/human/killer.png', Sex::MAN, '暗殺者', 4000, 400, 'キラークイーン', 200, 1500);

function createHuman(){
  global $humans;
  $human = $humans[mt_rand(0,count($humans)-1)];
  $_SESSION['human'] =  $human;
  $_SESSION['itemcount'] = 5;
}

function createMonster(){
  //モンスター画像ランダム表示
  $monster_image_array  = glob ("img/monster/*.png");
  $arraymax = count($monster_image_array);
  $num_list = range(1,$arraymax);
  $monster_img = $monster_image_array[array_rand($num_list)];
  //モンスター名取得
  $monster_name = basename($monster_img,'.png');
  //モンスターインスタンス生成
  $monster_elements = array('炎','氷','雷','水','風','闇','光');
  $monster = new Monster($monster_name,$monster_img,$monster_elements[mt_rand(0, count($monster_elements)-1)] ,mt_rand(10,10000), mt_rand(100,1000),mt_rand(100,500), mt_rand(600,1000));
  History::set($monster->getName().'が現れた！');
  $_SESSION['monster'] =  $monster;
}

function init(){
  History::clear();
  History::set('初期化します！');
  $_SESSION['knockDownCount'] = 0;
  //背景画像ランダム表示
  $image_rand = array(
      "img/background/forest.jpg",
      "img/background/cave.jpg",
      "img/background/load.jpg",
      "img/background/beach.jpg"
  );
  $_SESSION['background-image'] = $image_rand[mt_rand(0, count($image_rand)-1)];
  createHuman();
  createMonster();
}

function gameOver(){
  unset($_SESSION);
}

if (!empty($_POST)) {
  $attackFlg = (!empty($_POST['attack'])) ? true : false;
  $startFlg = (!empty($_POST['start'])) ? true : false;
  $specialAttackFlg = (!empty($_POST['special_attack'])) ? true : false;
  $itemFlg = (!empty($_POST['item'])) ? true : false;
  if($startFlg){
    History::set('ゲームスタート！');
    init();
  }else if($attackFlg){ // 攻撃するを押した場合
      // モンスターに攻撃を与える
      History::set($_SESSION['human']->getName().'の攻撃！');
      $_SESSION['human']->attack($_SESSION['monster']);
      $_SESSION['monster']->sayCry();
      // モンスターが攻撃をする
      History::set($_SESSION['monster']->getName().'の攻撃！');
      if($_SESSION['monster']->getMp() != 0 && !mt_rand(0,3)){ //4分の1の確率で属性攻撃
        $_SESSION['monster']->element_attack($_SESSION['monster']->getElement(),$_SESSION['human']);
      }else{
      $_SESSION['monster']->attack($_SESSION['human']);
      }
      $_SESSION['human']->sayCry();
      if (!empty($_SESSION['poison_count'])) {
        $_SESSION['poison_count'] -=1;
        $_SESSION['monster']->setHp($_SESSION['monster']->getHp()-100);
        History::set('毒針の継続効果! 100ダメージ!');
        History::set($_SESSION['poison_count'].'ターンの間継続ダメージを与える!');
      }
  }else if($specialAttackFlg){// 特殊攻撃を押した場合
    if($_SESSION['human']->getTp() <= 0){
      History::set('TPが足りないッ!!');
      goto end;
    }
    // モンスターに攻撃を与える
    History::set($_SESSION['human']->getName().'の特殊攻撃！');
    $_SESSION['human']->special_attack($_SESSION['human']->getSpecialAttackName(),$_SESSION['monster']);
    $_SESSION['monster']->sayCry();
    // モンスターが攻撃をする
    History::set($_SESSION['monster']->getName().'の攻撃！');
    if($_SESSION['monster']->getMp() != 0 && !mt_rand(0,3)){ //4分の1の確率で属性攻撃
      $_SESSION['monster']->element_attack($_SESSION['monster']->getElement(),$_SESSION['human']);
    }else{
      $_SESSION['monster']->attack($_SESSION['human']);
    }
    $_SESSION['human']->sayCry();
    if (!empty($_SESSION['poison_count'])) {
      $_SESSION['poison_count'] -=1;
      if ($_SESSION['poison_count'] !=0 ) {
        $_SESSION['monster']->setHp($_SESSION['monster']->getHp()-100);
        History::set('毒針の継続効果! 100ダメージ!');
        History::set($_SESSION['poison_count'].'ターンの間継続ダメージを与える!');
      }
    }
  }else if($itemFlg && $_POST['item'] != 'どうぐ'){
    $item_name = $_POST['item'];
    $_SESSION['human']->use_item($item_name,'monster');
  }else{
    History::set('逃げた！');
    createMonster();
  }
  // 自分のhpが0以下になったらゲームオーバー
  if($_SESSION['human']->getHp() <= 0){
    gameOver();
  }else{
    // hpが0以下になったら、別のモンスターを出現させる
    if($_SESSION['monster']->getHp() <= 0){
      unset($_SESSION['poison_count']);
      History::set($_SESSION['monster']->getName().'を倒した！');
      createMonster();
      $_SESSION['knockDownCount'] = $_SESSION['knockDownCount']+1;
    }
  }
  end:
  $_POST = array();
}

 ?>
<!DOCTYPE html>
<html>
  <head>
    <link rel="stylesheet" type="text/css" href="style.css">
    <meta charset="utf-8">
    <title>MonsterFight</title>
  </head>
  <body>
    <article>
      <h1>Monster Fight</h1>
      <div class="main-area">
        <?php
          if(empty($_SESSION)){
         ?>
        <img src="img/background/mainimg.jpg" style="height:500px;">
        <form method="post">
          <input type="submit" class="btn-square btn-start" name="start" value="▶ゲームスタート">
        </form>
        <?php
          }else{
         ?>
         <div class="enemy-monster">
           <div class="enemy-name" style="color:#ffffff;">
             <h2>名前<br><?php echo $_SESSION['monster']->getName();?></h2>
           </div>
           <img class="enemy-img" src="<?php echo $_SESSION['monster']->getImage(); ?>" alt="">
           <div class="flex-parent-enemy">
             <div class="enemy-role">
               <p>属性<br><?php echo $_SESSION['monster']->getElement();?></p>
             </div>
             <div class="enemy-hp">
               <p>HP<br><?php echo $_SESSION['monster']->getHp(); ?></p>
             </div>
             <div class="enemy-mp">
               <p>MP<br><?php echo $_SESSION['monster']->getMp(); ?></p>
             </div>
             <div class="enemy-attack-min">
               <p>攻撃力(最小)<br><?php echo $_SESSION['monster']->getAttackMin(); ?></p>
             </div>
             <div class="enemy-attack-max">
               <p>攻撃力(最大)<br><?php echo $_SESSION['monster']->getAttackMax(); ?></p>
             </div>
           </div>
         </div>
        <img class="background-img" src="<?php echo $_SESSION['background-image']; ?>">
        <div class="ally-area flex-parent">
          <form method="post" class="command-area">
            <input type="submit" class="btn-square" name="attack"  value="たたかう">
            <input type="submit" class="btn-square" name="special_attack"  value="とくしゅこうげき">
            <select class="btn-square" name="item" onchange="submit(this.form)">
              <option value="どうぐ" selected>どうぐ</option>
              <option value="回復薬">回復薬</option>
              <option value="爆弾">爆弾</option>
              <option value="毒針">毒針</option>
            </select>
            <input type="submit" class="btn-square" name="escape" value="にげる">
          </form>
          <div class="character-area">
            <div class="character-img" style="margin-right:20px;">
              <img src="<?php echo $_SESSION['human']->getImage(); ?>" alt="">
            </div>
            <div class="character-name" style="margin-right:20px;">
              <p>名前<br><?php echo $_SESSION['human']->getName();?></p>
            </div>
            <div class="character-role" style="margin-right:20px;">
              <p>クラス<br><?php echo $_SESSION['human']->getRole();?></p>
            </div>
            <div class="character-hp" style="margin-right:20px;">
              <p>HP<br><?php echo $_SESSION['human']->getHp(); ?></p>
            </div>
            <div class="character-tp" style="margin-right:20px;">
              <p>TP<br><?php echo $_SESSION['human']->getTp(); ?></p>
            </div>
            <div class="knockdown-count" style="margin-right:20px;">
              <p>討伐数<br><?php echo $_SESSION['knockDownCount']; ?></p>
            </div>
          </div>
        </div>
      </div>
      <div class="history-area">
        <p><?php echo (!empty($_SESSION['history'])) ? $_SESSION['history'] : ''; ?></p>
      </div>
        <?php
        }
         ?>
    </article>
    <script src="js/jquery-3.4.1.min.js"></script>
    <script src="js/jquery-ui.min.js"></script>
    <script src="js/monsterfight.js"></script>
  </body>
</html>
