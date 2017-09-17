<?php

namespace Enes5519\EggWars\Komutlar;

use Enes5519\EggWars\EggWars;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;

class EW extends Command{

    public function __construct(){
        parent::__construct("ew", "EggWars by Enes5519");
    }

    public function execute(CommandSender $g, $label, array $args){
        $main = EggWars::getInstance();
        if($g->hasPermission("enes5519.eggwars.komut") && $g instanceof Player){
            if(!empty($args[0])){
                if($args[0] == "yardim"){
                    $g->sendMessage("§6----- §fEggwars Yardım Komutları §6-----");
                    $g->sendMessage("§8» §e/ew olustur".' <arena> <takim> <tbo> '."§6Arena oluşturursunuz!");
                    $g->sendMessage("§8» §e/ew ayarla".' <arena> <takim> '."§6Takım konumunu ayarlarsınız!");
                    $g->sendMessage("§8» §e/ew lobi".' <arena> '."§6Bekleme lobisini ayarlarsınız!");
                    $g->sendMessage("§8» §e/ew kaydet".' <arena> '."§6Doğulacak mapi kaydedersiniz!");
                    $g->sendMessage("§8» §e/ew market "."§6Köylü Oluşturursunuz!");
                }elseif ($args[0] == "olustur"){
                    if(!empty($args[1])){
                        if(!empty($args[2]) && is_numeric($args[2])){
                            if(!empty($args[3]) && is_numeric($args[3])){
                                $main->arenaOlustur($args[1], $args[2], $args[3], $g);
                            }else{
                                $g->sendMessage("§8» §c/ew olustur ".'<arena> <takım> <takımbaşınaoyuncu>');
                            }
                        }else{
                            $g->sendMessage("§8» §c/ew olustur ".'<arena> <takım> <takımbaşınaoyuncu>');
                        }
                    }else{
                        $g->sendMessage("§8» §c/ew olustur ".'<arena> <takım> <takımbaşınaoyuncu>');
                    }
                }elseif ($args[0] == "ayarla"){
                    if(!empty($args[1])){
                        if(!empty($args[2])){
                            $main->arenaAyarla($args[1], $args[2], $g);
                        }else{
                            $g->sendMessage("§8» §c/ew ayarla ".'<arena> <takım>');
                        }
                    }else{
                        $g->sendMessage("§8» §c/ew ayarla ".'<arena> <takım>');
                    }
                }elseif ($args[0] == "lobi"){
                    if(!empty($args[1])){
                        if($main->arenaKontrol($args[1])){
                            $ac = new Config($main->getDataFolder()."Arenalar/$args[1].yml", Config::YAML);
                            $ac->setNested("Lobi.X", $g->getFloorX());
                            $ac->setNested("Lobi.Y", $g->getFloorY());
                            $ac->setNested("Lobi.Z", $g->getFloorZ());
                            $ac->setNested("Lobi.Yaw", $g->getYaw());
                            $ac->setNested("Lobi.Pitch", $g->getPitch());
                            $ac->setNested("Lobi.Dunya", $g->getLevel()->getFolderName());
                            $ac->save();
                            $g->sendMessage("§8» §a$args[1] arenasının lobisi ayarlandı.");
                        }else{
                            $g->sendMessage("§8» §c$args[1] isminde bir arena yok.");
                        }
                    }else{
                        $g->sendMessage("§8» §c/ew lobi ".'<arena>');
                    }
                }elseif($args[0] == "kaydet"){
                    if(!empty($args[1])){
                        if($main->arenaKontrol($args[1])) {
                            if ($g->getLevel() !== $g->server->getDefaultLevel()) {
                                $ac = new Config($main->getDataFolder()."Arenalar/$args[1].yml", Config::YAML);
                                $ac->set("Dunya", $g->getLevel()->getFolderName());
                                $ac->save();
                                $main->kopyalama(Server::getInstance()->getDataPath()."worlds/".$g->getLevel()->getFolderName(), $main->getDataFolder()."Yedekler/".$g->getLevel()->getFolderName());
                                $g->sendMessage("§8» §a$args[1] arenası kaydedildi.");
                            } else {
                                $g->sendMessage("§8» §cKaydedeceğin map spawn olmamalıdır!");
                            }
                        }else{
                            $g->sendMessage("§8» §c$args[1] isminde arena yok!");
                        }
                    }else{
                        $g->sendMessage("§8» §c/ew kaydet ".'<arena>');
                    }
                }elseif($args[0] == "market"){
                    $this->marketOlustur($g->x, $g->y, $g->z, $g->yaw, $g->pitch, $g->getLevel());
                }elseif($args[0] == "baslat"){
                    if($main->oyuncuArenadami($g->getName())){
                        $arena = $main->oyuncuArenadami($g->getName());
                        $ac = new Config($main->getDataFolder()."Arenalar/$arena.yml", Config::YAML);
                        if($ac->get("Durum") == "Lobi"){
                            $ac->set("BaslamaSuresi", 6);
                            $ac->save();
                            $g->sendMessage($main->b."§bOyun başlatılıyor...");
                        }
                    }else{
                        $g->sendMessage($main->b."§cŞuan bir oyunda değilsin.");
                    }
                }
            }else{
                $g->sendMessage("§8» §c/ew yardim §7EggWars Yardım Komutları");
            }
        }else{
            $g->sendMessage("§8» §6EggWars Plugin By §eEnes5519!\n§8» §aIletişim(Contact) : Enes Yıldırım (FaceBook)");
        }
    }

    public function marketOlustur($x, $y, $z, $yaw, $pitch, Level $dunya){
        $nbt = new CompoundTag;
        $nbt->Pos = new ListTag("Pos", [
            new DoubleTag("", $x),
            new DoubleTag("", $y),
            new DoubleTag("", $z)
        ]);
        $nbt->Rotation = new ListTag("Rotation", [
            new DoubleTag("", $yaw),
            new DoubleTag("", $pitch)
        ]);
        $nbt->Motion = new ListTag("Motion", [
            new DoubleTag("", 0),
            new DoubleTag("", 0)
        ]);
        $nbt->Health = new ShortTag("Health", 10);
        $nbt->CustomName = new StringTag("CustomName", "§6EGGWars §fMarket");
        $nbt->CustomNameVisible = new ByteTag("CustomNameVisible", 1);
        $dunya->loadChunk($x >> 4, $z >> 4);
        $koylu = Entity::createEntity("Villager", $dunya, $nbt);
        $koylu->spawnToAll();
    }
}
