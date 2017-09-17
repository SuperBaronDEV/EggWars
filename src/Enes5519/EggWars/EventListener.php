<?php

namespace Enes5519\EggWars;

use pocketmine\entity\Villager;
use pocketmine\event\entity\{
    EntityDamageByEntityEvent, EntityDamageEvent
};
use pocketmine\event\player\{
    PlayerDeathEvent, PlayerInteractEvent, PlayerChatEvent
};
use pocketmine\event\block\{
    SignChangeEvent, BlockBreakEvent, BlockPlaceEvent
};
use pocketmine\event\inventory\{
    InventoryTransactionEvent, InventoryCloseEvent
};
use pocketmine\item\Item;
use pocketmine\event\Listener;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\tile\{Sign, Chest};
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;
use pocketmine\inventory\{
    ChestInventory
};

class EventListener implements Listener{

    public $sd = array();
    public function __construct(){
    }
    
    public function sohbet(PlayerChatEvent $e){
        $o = $e->getPlayer();
        $m = $e->getMessage();
        $main = EggWars::getInstance();

        if($main->oyuncuArenadami($o->getName())){
            $renk = "";
            $is = substr($m, 0, 1);
            $takim = $main->oyuncuHangiTakimda($o);
            $arena = $main->oyuncuArenadami($o->getName());
            $ac = new Config($main->getDataFolder()."Arenalar/$arena.yml", Config::YAML);
            if($ac->get("Durum") == "Lobi"){
                $oyuncular = $main->arenaOyunculari($arena);
			         foreach($oyuncular as $olar){
			             $to = $main->getServer()->getPlayer($olar);
			             if($to instanceof Player){
			                 $to->sendMessage("§f".$o->getName()." §8» §7".$m);
			             }
			         }
            }
            if(!empty($main->takimlar()[$takim])){
                $renk = $main->takimlar()[$takim];
            }
			     if($is == "!"){
			         $msil = substr($m, 1);
			         $main->arenaMesaj($arena, "§8[§c!§8] ".$renk.$o->getName()." §8» §7$msil");
			     }else{
			         $oyuncular = $main->arenaOyunculari($arena);
			         foreach($oyuncular as $olar){
			             $to = $main->getServer()->getPlayer($olar);
			             if($to instanceof Player){
			                 $totakim = $main->oyuncuHangiTakimda($to);
			                 if($takim == $totakim){
			                     $mesaj = "§8[".$renk."Takım§8] ".$renk.$o->getName()." §8» §7$m";
			                     $to->sendMessage($mesaj);
			                 }
			             }
			         }
			     }
			     return;
        }
    }

    public function oyunaKatil(PlayerInteractEvent $e){
        $o = $e->getPlayer();
        $b = $e->getBlock();
        $t = $o->getLevel()->getTile($b);
        $main = EggWars::getInstance();
        if($t instanceof Sign){
            $yazilar = $t->getText();
            if($yazilar[0] == $main->tyazi){
                $arena = str_ireplace("§e", "", $yazilar[2]);
                $durum = $main->arenaDurum($arena);
                if($durum == "Lobi"){
                    if(!$main->oyuncuArenadami($o->getName())){
                        $ac = new Config($main->getDataFolder()."Arenalar/$arena.yml", Config::YAML);
                        $oyuncular = count($main->arenaOyunculari($arena));
                        $fulloyuncu = $ac->get("Takim") * $ac->get("TakimBasinaOyuncu");
                        if($oyuncular >= $fulloyuncu){
                            $o->sendPopup("§8» §cOyun Dolu §8«");
                            return;
                        }
                        $main->arenayaOyuncuEkle($arena, $o->getName());
                        $o->teleport(new Position($ac->getNested("Lobi.X"), $ac->getNested("Lobi.Y"), $ac->getNested("Lobi.Z"), $main->getServer()->getLevelByName($ac->getNested("Lobi.Dunya"))));
                        $o->sendPopup("§aBaşarıyla oyuna katıldın!");
                        $main->yunleriVer($arena, $o);
                        $main->arenaMesaj($arena, $main->b."§e".$o->getName()." §boyuna katıldı.");
                    }else{
                        $o->sendPopup("§cZaten bir oyundasın!");
                    }
                }elseif ($durum == "Oyunda"){
                    $o->sendPopup("§8» §dOyun hala devam ediyor!");
                }elseif ($durum == "Bitti"){
                    $o->sendPopup("§8» §eArena yenileniyor...");
                }
                $e->setCancelled();
            }
        }
    }

    public function generatorYukselt(PlayerInteractEvent $e){
        $o = $e->getPlayer();
        $b = $e->getBlock();
        $tabela = $o->getLevel()->getTile($b);
        $main = EggWars::getInstance();
        if($tabela instanceof Sign){
            $y = $tabela->getText();
            if($y[0] == "§fDemir" || $y[0] == "§6Altın" || $y[0] == "§bElmas"){
                $tip = $y[0];
                $level = (int) explode(" ", $y[1])[1];
                switch($level){
                    case 0:
                        switch ($tip){
                            case "§6Altın":
                                if($main->itemSayi($o, Item::GOLD_INGOT) >= 5){
                                    $o->getInventory()->removeItem(Item::get(Item::GOLD_INGOT,0,5));
                                    $tabela->setText($y[0], "§eSeviye 1", "§b8 saniye", $y[3]);
                                    $o->sendMessage("§8» §aAltın generator aktif edildi!");
                                }else{
                                    $o->sendMessage("§8» §65 altın ile açabilirsin!");
                                }
                            break;
                            case "§bElmas":
                                if($main->itemSayi($o, Item::DIAMOND) >= 5){
                                    $o->getInventory()->removeItem(Item::get(Item::DIAMOND,0,5));
                                    $tabela->setText($y[0], "§eSeviye 1", "§b10 saniye", $y[3]);
                                    $o->sendMessage("§8» §aElmas generator aktif edildi!");
                                }else{
                                    $o->sendMessage("§8» §b5 elmas ile açabilirsin!");
                                }
                            break;
                        }
                    break;
                case 1:
                    switch ($tip){
                        case "§fDemir":
                            if($main->itemSayi($o, Item::IRON_INGOT) >= 10){
                                $o->getInventory()->removeItem(Item::get(Item::IRON_INGOT,0,10));
                                $tabela->setText($y[0], "§eSeviye 2", "§b2 saniye", $y[3]);
                                $o->sendMessage("§8» §a2. seviyeye yükseltildi!");
                            }else{
                                $o->sendMessage("§8» §f10 demir ile açabilirsin!");
                            }
                        break;
                        case "§6Altın":
                            if($main->itemSayi($o, Item::GOLD_INGOT) >= 10){
                                $o->getInventory()->removeItem(Item::get(Item::GOLD_INGOT,0,10));
                                $tabela->setText($y[0], "§eSeviye 2", "§b6 saniye", $y[3]);
                                $o->sendMessage("§8» §a2. seviyeye yükseltildi!");
                            }else{
                                $o->sendMessage("§8» §610 altın ile yükseltebilirsin!");
                            }
                        break;
                        case "§bElmas":
                            if($main->itemSayi($o, Item::DIAMOND) >= 10){
                                $o->getInventory()->removeItem(Item::get(Item::DIAMOND,0,10));
                                $tabela->setText($y[0], "§eSeviye 2", "§b8 saniye", $y[3]);
                                $o->sendMessage("§8» §a2. seviyeye yükseltildi!");
                            }else{
                                $o->sendMessage("§8» §b10 elmas ile açabilirsin!");
                            }
                        break;
                    }
                break;
                case 2:
                    switch ($tip){
                        case "§fDemir":
                            if($main->itemSayi($o, Item::GOLD_INGOT) >= 10){
                                $o->getInventory()->removeItem(Item::get(Item::GOLD_INGOT,0,10));
                                $tabela->setText($y[0], "§eSeviye 3", "§b1 saniye", "§c§lMAKSIMUM");
                                $o->sendMessage("§8» §aMaksimum seviyeye yükseltildi!");
                            }else{
                                $o->sendMessage("§8» §610 altın ile açabilirsin!");
                            }
                        break;
                        case "§6Altın":
                            if($main->itemSayi($o, Item::DIAMOND) >= 10){
                                $o->getInventory()->removeItem(Item::get(Item::DIAMOND,0,10));
                                $tabela->setText($y[0], "§eSeviye 3", "§b4 saniye", "§c§lMAKSIMUM");
                                $o->sendMessage("§8» §aMaksimum seviyeye yükseltildi!");
                            }else{
                                $o->sendMessage("§8» §b10 elmas ile açabilirsin!");
                            }
                        break;
                        case "§bElmas":
                            if($main->itemSayi($o, Item::DIAMOND) >= 20){
                                $o->getInventory()->removeItem(Item::get(Item::DIAMOND,0,20));
                                $tabela->setText($y[0], "§eSeviye 3", "§b6 saniye", "§c§lMAKSIMUM");
                                $o->sendMessage("§8» §aMaksimum seviyeye yükseltildi!");
                            }else{
                                $o->sendMessage("§8» §b20 elmas ile açabilirsin!");
                            }
                        break;
                    }
                break;
                default:
                    $o->sendMessage("§8» §cMaksimum seviyede zaten!");
                break;
                }
            }
        }
    }
    
    public function yumurtaKir(PlayerInteractEvent $e){
        $o = $e->getPlayer();
        $b = $e->getBlock();
        $main = EggWars::getInstance();
        if($main->oyuncuArenadami($o->getName())){
            if($b->getId() == 122){
                $yun = $b->getLevel()->getBlock(new Vector3($b->x, $b->y - 1, $b->z));
                if($yun->getId() == 35){
                    $renk = $yun->getDamage();
                    $takim = array_search($renk, $main->takimYunCevirici());
                    $oht = $main->oyuncuHangiTakimda($o);
                    if($oht == $takim){
                        $o->sendPopup("§8»§c Kendi takımının yumurtasını kıramazsın!");
                    }else{
                        $b->getLevel()->setBlock(new Vector3($b->x, $b->y, $b->z), Block::get(0));
                        $main->yildirimOlustur($b->x, $b->y, $b->z, $o->getLevel());
                        $arena = $main->oyuncuArenadami($o->getName());
                        $main->ky[$arena][] = $takim;
                        $o->sendPopup("§8» ".$main->takimlar()[$takim]." $takim takımının yumurtasını kırdın!");
                        $main->arenaMesaj($main->oyuncuArenadami($o->getName()), "§8» ".$o->getNameTag()." oyuncusu ".$main->takimlar()[$takim]."$takim ".$main->takimlar()[$oht]."takımın yumurtasını kırdı!");
                    }
                }
            }
        }
    }
        
    public function tabelaOlustur(SignChangeEvent $e){
        $o = $e->getPlayer();
        $main = EggWars::getInstance();
        if($o->isOp()){
            if($e->getLine(0) == "eggwars"){
                if(!empty($e->getLine(1))){
                    if($main->arenaKontrol($e->getLine(1))){
                        if($main->arenaHazirmi($e->getLine(1))){
                            $arena = $e->getLine(1);
                            $e->setLine(0, $main->tyazi);
                            $e->setLine(1, "§f0/0");
                            $e->setLine(2, "§e$arena");
                            $e->setLine(3, "§l§bYukleniyor");
                            for($i=0; $i<=3; $i++){
                                $o->sendMessage("§8» §a$i".$e->getLine($i));
                            }
                        }else{
                            $e->setLine(0, "§cHATA");
                            $e->setLine(1, "§7".$e->getLine(1));
                            $e->setLine(2, "§7arenası");
                            $e->setLine(3, "§7tam değil!");
                        }
                    }else{
                        $e->setLine(0, "§cHATA");
                        $e->setLine(1, "§7".$e->getLine(1));
                        $e->setLine(2, "§7arenası");
                        $e->setLine(3, "§7bulunamadı");
                    }
                }else{
                    $e->setLine(0, "§cHATA");
                    $e->setLine(1, "§7Arena");
                    $e->setLine(2, "§7bolumu");
                    $e->setLine(3, "§7boş!");
                }
            }elseif ($e->getLine(0) == "generator"){
                if(!empty($e->getLine(1))){
                    switch ($e->getLine(1)){
                        case "demir":
                            $e->setLine(0, "§fDemir");
                            $e->setLine(1, "§eSeviye 1");
                            $e->setLine(2, "§b4 saniye");
                            $e->setLine(3, "§a§lYUKSELT");
                            break;
                        case "altin":
                            if($e->getLine(2) != "kirik") {
                                $e->setLine(0, "§6Altın");
                                $e->setLine(1, "§eSeviye 1");
                                $e->setLine(2, "§b8 saniye");
                                $e->setLine(3, "§a§lYUKSELT");
                            }else{
                                $e->setLine(0, "§6Altın");
                                $e->setLine(1, "§eSeviye 0");
                                $e->setLine(2, "KIRIK");
                                $e->setLine(3, "§a§lKILIDI AÇ");
                            }
                            break;
                        case "elmas":
                            if($e->getLine(2) != "kirik") {
                                $e->setLine(0, "§bElmas");
                                $e->setLine(1, "§eSeviye 1");
                                $e->setLine(2, "§b10 saniye");
                                $e->setLine(3, "§a§lYUKSELT");
                            }else{
                                $e->setLine(0, "§bElmas");
                                $e->setLine(1, "§eSeviye 0");
                                $e->setLine(2, "KIRIK");
                                $e->setLine(3, "§a§lKILIDI AÇ");
                            }
                            break;
                    }
                }else{
                    $e->setLine(0, "§cHATA");
                    $e->setLine(1, "§7generator");
                    $e->setLine(2, "§7tipi");
                    $e->setLine(3, "§7belirtilmemiş!");
                }
            }
        }
    }

    public function olme(PlayerDeathEvent $e){
        $o = $e->getPlayer();
        $main = EggWars::getInstance();
        if($main->oyuncuArenadami($o->getName())){
            $e->setDeathMessage("");
            $sondarbe = $o->getLastDamageCause();
            if($sondarbe instanceof EntityDamageByEntityEvent){
                $e->setDrops(array());
                $olduren = $sondarbe->getDamager();
                if($olduren instanceof Player){
                    $olduren->sendMessage("§8» §a".$o->getNameTag()." kişisini öldürdün!");
                    $main->arenaMesaj($main->oyuncuArenadami($o->getName()), "§8» ".$o->getNameTag()." ".$olduren->getNameTag()." tarafından gebertildi!");
                }
            }else{
                $e->setDrops(array());
                if(!empty($this->sd[$o->getName()])){
                    $olduren = $main->getServer()->getPlayer($this->sd[$o->getName()]);
                    if($olduren instanceof Player){
                        $olduren->sendMessage("§8» §a".$o->getNameTag()." kişisini öldürdün!");
                        $main->arenaMesaj($main->oyuncuArenadami($o->getName()), "§8» ".$o->getNameTag()." ".$olduren->getNameTag()." tarafından gebertildi!");
                    }
                }else{
                    $main->arenaMesaj($main->oyuncuArenadami($o->getName()), "§8» ".$o->getNameTag()." geberdi!");
                }
            }
        }
    }

    public function hasarAlma(EntityDamageEvent $e){
        $o = $e->getEntity();
        $main = EggWars::getInstance();
        if($e instanceof EntityDamageByEntityEvent){
            $d = $e->getDamager();
            if($o instanceof Villager && $d instanceof Player){
                if($o->getNameTag() == "§6EGGWars §fMarket"){
                    $e->setCancelled();
                    $main->marketAc($d);
                    return;
                }
            }
            if($o instanceof Player && $d instanceof Player){
                if($main->oyuncuArenadami($o->getName())){
                    $arena = $main->oyuncuArenadami($o->getName());
                    $ac = new Config($main->getDataFolder()."Arenalar/$arena.yml", Config::YAML);
                    $takim = $main->oyuncuHangiTakimda($o);
                    if($ac->get("Durum") == "Lobi"){
                        $e->setCancelled();
                    }else{
                        $td = substr($d->getNameTag(), 0, 3);
                        $to = substr($o->getNameTag(), 0, 3);
                        if($td == $to){
                            $e->setCancelled();
                        }else{
                            $this->sd[$o->getName()] = $d->getName();
                        }
                    }
                    if($e->getDamage() >= $e->getEntity()->getHealth()){
                        $e->setCancelled();
                        $o->setHealth(20);
                        if($main->yumurtaKirildimi($arena, $takim)){
                            $main->arenadanOyuncuKaldir($arena, $o->getName());
                        }else{
                            $o->teleport(new Position($ac->getNested("$takim.X"), $ac->getNested("$takim.Y"), $ac->getNested("$takim.Z"), $main->getServer()->getLevelByName($ac->get("Dunya"))));
                            $main->arenaMesaj($arena, "§8» ".$o->getNameTag().", ".$d->getNameTag()." §ctarafından öldürüldü!");
                        }
                        $o->getInventory()->clearAll();
                    }
                }
            }
        }else{
            if($o instanceof Player){
                if($main->oyuncuArenadami($o->getName())){
                    $arena = $main->oyuncuArenadami($o->getName());
                    $ac = new Config($main->getDataFolder()."Arenalar/$arena.yml", Config::YAML);
                    if($ac->get("Durum") == "Lobi"){
                        $e->setCancelled();
                    }
                    $takim = $main->oyuncuHangiTakimda($o);
                    $mesaj = null;
                    if(!empty($this->sd[$o->getName()])){
                        $sd = $main->getServer()->getPlayer($this->sd[$o->getName()]);
                        if($sd instanceof Player){
                            unset($this->sd[$o->getName()]);
                            $mesaj = "§8» ".$o->getNameTag().", ".$sd->getNameTag()." §ctarafından öldürüldü!";
                        }else{
                            $mesaj = "§8» ".$o->getNameTag()." geberdi!";
                        }
                    }else{
                        $mesaj = "§8» ".$o->getNameTag()." geberdi!";
                    }
                    if($e->getDamage() >= $e->getEntity()->getHealth()){
                        $e->setCancelled();
                        $o->setHealth(20);
                        if($main->yumurtaKirildimi($arena, $takim)){
                            $main->arenadanOyuncuKaldir($arena, $o->getName());
                        }else{
                            $o->teleport(new Position($ac->getNested("$takim.X"), $ac->getNested("$takim.Y"), $ac->getNested("$takim.Z"), $main->getServer()->getLevelByName($ac->get("Dunya"))));
                            $main->arenaMesaj($arena, $mesaj);
                        }
                        $o->getInventory()->clearAll();
                    }
                }
            }
        }
    }
    
    public function envKapat(InventoryCloseEvent $e){
        $main = EggWars::getInstance();
        $o = $e->getPlayer();
        $env = $e->getInventory();
        if($env instanceof ChestInventory){
            if(!empty($main->mk[$o->getName()])){
                $o->getLevel()->setBlockIdAt($o->getFloorX(), $o->getFloorY() - 4, $o->getFloorZ(), 0);
                unset($main->mk[$o->getName()]);
            }
        }
    }
    
    public function alisverisYapma(InventoryTransactionEvent $e){
        $main = EggWars::getInstance();
        foreach ($e->getQueue()->getTransactions() as $t) {
            $env = $t->getInventory();
            if ($env instanceof ChestInventory) {
                foreach ($env->getViewers() as $o) {
                    if(empty($main->mk[$o->getName()])) return;
                    $sandik = $env->getHolder(); // item:id:miktar:ödeme id:ödeme adeti
                    if ($sandik instanceof Chest) {
                        $marketitemler = $main->market;
                        $item = $env->getItem($t->getSlot());
                        if(!($item instanceof Item)) return;
                        if($env->getItem(26)->getId() == 0){ // başlangıç menü
                            foreach ($marketitemler as $marketitem){
                                $mitem = Item::fromString($marketitem["item"]);
                                if($mitem->getId() == $item->getId()){
                                    $env->clearAll();
                                    foreach($marketitem["itemler"] as $slot => $gitem){
                                        $parcala = explode(":", $gitem);
                                        $env->setItem($slot * 2, Item::get($parcala[0], $parcala[1], $parcala[2]));
                                        $env->setItem($slot * 2 + 1, Item::get($parcala[3], 0, $parcala[4]));
                                    }
                                    $env->setItem(26, Item::get(Item::WOOL, 14, 1)->setCustomName("§r§cGeri"));
                                }
                            }
                        }else{
                            $illegal = [264,265,266];
                            if(in_array($item->getId(), $illegal)){
                                $e->setCancelled();
                            }else{
                                $slot = $t->getSlot();
                                if($slot == 26){
                                    $env->clearAll();
                                    foreach($marketitemler as $slot => $marketitem){
                                        $mitem = Item::fromString($marketitem["item"])->setCustomName("§r".$marketitem["baslik"]);
                                        $env->setItem($slot, $mitem);
                                    }
                                }else{
                                    if($o->getInventory()->contains($env->getItem($slot + 1))){
                                        $o->getInventory()->addItem($env->getItem($slot));
                                        $o->getInventory()->removeItem($env->getItem($slot + 1));
                                        $o->sendMessage("§8» §aEşya alındı.");
                                    }else{
                                        $o->sendMessage("§8» §cÜcret eksik.");
                                    }
                                }
                            }
                        }
                        $e->setCancelled();
                    }
                }
            }
        }
    }
    
    public function blokKirma(BlockBreakEvent $e){
        $o = $e->getPlayer();
        $b = $e->getBlock();
        $main = EggWars::getInstance();
        if($main->oyuncuArenadami($o->getName())){
            $cfg = new Config($main->getDataFolder()."config.yml", Config::YAML);
            $ad = $main->arenaDurum($main->oyuncuArenadami($o->getName()));
            if($ad == "Lobi"){
                $e->setCancelled(true);
                return;
            }
            $bloklar = $cfg->get("KirilanBloklar");
            foreach($bloklar as $blok){
                if($b->getId() != $blok){
                    $e->setCancelled();
                }else{
                    $e->setCancelled(false);
                    break;
                }
            }
        }else{
            if(!$o->isOp()){
                $e->setCancelled(true);
            }
        }
    }
    
    public function blokYerlestirme(BlockPlaceEvent $e){
        $o = $e->getPlayer();
        $b = $e->getBlock();
        $main = EggWars::getInstance();
        $cfg = new Config($main->getDataFolder()."config.yml", Config::YAML);
        if($main->oyuncuArenadami($o->getName())){
            $ad = $main->arenaDurum($main->oyuncuArenadami($o->getName()));
            if($ad == "Lobi"){
                if($b->getId() == 35){
                    $arena = $main->oyuncuArenadami($o->getName());
                    $tyun = array_search($b->getDamage() ,$main->takimYunCevirici());
                    $marena = $main->musaitTakimlar($arena);
                    if(in_array($tyun, $marena)){
                        $renk = $main->takimlar()[$tyun];
                        $o->setNameTag($renk.$o->getName());
                        $o->sendPopup("§8» $renk"."$tyun takımına geçtin!");
                    }else{
                        $o->sendPopup("§8» §cTakım eşitliği sağlanmıyor!");
                    }
                    $e->setCancelled();
                }
                return;
            }
            
                $bloklar = $cfg->get("KirilanBloklar");
                foreach($bloklar as $blok){
                    if($b->getId() != $blok){
                        $e->setCancelled();
                    }else{
                        $e->setCancelled(false);
                        break;
                    }
                }
        }else{
            if(!$o->isOp()){
                $e->setCancelled(true);
            }
        }
    }
    
}   
