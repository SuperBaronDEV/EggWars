<?php

namespace Enes5519\EggWars\Islemler;

use Enes5519\EggWars\EggWars;
use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
use pocketmine\tile\Sign;
use pocketmine\utils\Config;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;

class TabelaTask extends PluginTask{
    
    private $p;
    public function __construct($p){
        $this->p = EggWars::getInstance();
        parent::__construct($p);
    }

    public function onRun($currentTick){
        $main = $this->p;
        $level = Server::getInstance()->getDefaultLevel();
        $tiles = $level->getTiles();
        foreach ($tiles as $t){
            if($t instanceof Sign){
                $y = $t->getText();
                if($y[0] == $main->tyazi){
                    $arena = TextFormat::clean($y[2]);
                    $ac = new Config($main->getDataFolder()."Arenalar/$arena.yml", Config::YAML);
                    $durum = $ac->get("Durum");
                    $oyuncular = count($main->arenaOyunculari($arena));
                    $fulloyuncu = $ac->get("Takim") * $ac->get("TakimBasinaOyuncu");
                    $d = null;
                    $re = null;
                    $b=$t->getBlock();
                    if($durum == "Lobi"){
                        if($oyuncular >= $fulloyuncu){
                            $d = "§c§lDOLU";
                            $re = 14;
                        }else{
                            $d = "§a§lAKTIF";
                            $re = 5;
                        }
                    }elseif ($durum == "Oyunda"){
                        $d = "§d§lOYUNDA";
                        $re = 1;
                    }elseif($durum == "Bitti"){
                        $d = "§9§lYENILENIYOR";
                        $re = 4;
                    }
                    $ab = $b->getSide(Vector3::SIDE_SOUTH, 1);
                    $ba = $b->getSide(Vector3::SIDE_NORTH, 1);
                    $ca = $b->getSide(Vector3::SIDE_EAST, 1);
                    $ac = $b->getSide(Vector3::SIDE_WEST, 1);
                    $t->setText($y[0], "§f$oyuncular/$fulloyuncu", $y[2], $d);
                    if($ac->getId() == 35){
                        $ac->setDamage($re);
                        $b->getLevel()->setBlock($ac, $ac);
                    }elseif($ca->getId() == 35){
                        $ca->setDamage($re);
                        $b->getLevel()->setBlock($ca, $ca);
                    }elseif($ab->getId() == 35){
                        $ab->setDamage($re);
                        $b->getLevel()->setBlock($ab, $ab);
                    }elseif($ba->getId() == 35){
                        $ba->setDamage($re);
                        $b->getLevel()->setBlock($ba, $ba);
                    }
                }
            }
        }
    }
}