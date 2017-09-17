<?php

namespace Enes5519\EggWars\Komutlar;

use Enes5519\EggWars\EggWars;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;

class Hub extends Command{

    public function __construct(){
        parent::__construct("hub", "Huba ışınlanırsınız");
        $this->setAliases(array("lobi", "lobby", "spawn"));
    }

    public function execute(CommandSender $g, $label, array $args){
        $main = EggWars::getInstance();
        if(!$g instanceof Player){
            return;
        }
        if($main->oyuncuArenadami($g->getName())){
            $arena = $main->oyuncuArenadami($g->getName());
            $ac = new Config($main->getDataFolder()."Arenalar/$arena.yml", Config::YAML);
            $durum = $ac->get("Durum");
            if($durum == "Lobi"){
                $main->arenadanOyuncuKaldir($arena, $g->getName());
                $g->sendMessage("§8» §aHuba ışınlandınız!");
            }else{
                $g->sendMessage("§8» §cOyundayken huba ışınlanamazsınız!");
            }
        }else{
            $g->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn());
            $g->sendMessage("§8» §aHuba ışınlandınız!");
        }
    }
}