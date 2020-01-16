<?php

namespace SuperBaronDEV\EggWars;

use Enes5519\EggWars\Komutlar\{Hub, EW};
use Enes5519\EggWars\Islemler\{
    OyunTask, TabelaTask
};
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\{CompoundTag, IntTag, ListTag, StringTag}; 
use pocketmine\tile\{Tile, Chest};
use pocketmine\entity\Entity;

class EggWars extends PluginBase{

    private static $ins;
    public $ky = array();
    public $sb = '§6EGGWars> ';
    public $tyazi = '§8§l» §r§6EGG §fWars §l§8«';
    public $b = '§6EGG§f Wars §8» ';
    public $mk = [];

    public $market =
        [
            [
                "baslik" => "§eBloklar",
                "item" => Item::BRICKS_BLOCK,
                "itemler" =>
                    [ // item:id:miktar:ödeme id:ödeme adeti
                        Item::SANDSTONE.":0:4:265:1", Item::END_STONE.":0:4:265:4", Item::GLASS.":0:2:265:1", Item::GLOWSTONE.":0:2:265:3", Item::OBSIDIAN.":0:1:266:4"
                    ]
            ],
            [
                "baslik" => "§eSilahlar",
                "item" => Item::GOLDEN_SWORD,
                "itemler" =>
                    [ // item:id:miktar:ödeme id:ödeme adeti
                        Item::WOODEN_SWORD.":0:1:265:5", Item::STONE_SWORD.":0:1:265:32", Item::IRON_SWORD.":0:1:266:16", Item::IRON_SWORD.":0:1:266:16", Item::DIAMOND_SWORD.":0:1:264:12", Item::BOW.":0:1:264:15", Item::ARROW.":0:2:266:2"
                    ]
            ],
            [
                "baslik" => "§eZırhlar",
                "item" => Item::IRON_CHESTPLATE,
                "itemler" =>
                    [ // item:id:miktar:ödeme id:ödeme adeti
                        Item::LEATHER_CAP.":0:1:265:1", Item::LEATHER_TUNIC.":0:1:265:1", Item::LEATHER_PANTS.":0:1:265:1", Item::LEATHER_BOOTS.":0:1:265:1", Item::CHAIN_HELMET.":0:1:266:10", Item::CHAIN_CHESTPLATE.":0:1:266:10", Item::CHAIN_LEGGINGS.":0:1:266:10", Item::CHAIN_BOOTS.":0:1:266:10", Item::IRON_HELMET.":0:1:264:5", Item::IRON_CHESTPLATE.":0:1:264:5", Item::IRON_LEGGINGS.":0:1:264:5", Item::IRON_BOOTS.":0:1:264:5"
                    ]
            ],
            [
				"baslik" => "§eYemekler",
                "item" => Item::CARROT,
                "itemler" =>
                    [ // item:id:miktar:ödeme id:ödeme adeti
                        Item::CARROT.":0:5:265:1", Item::STEAK.":0:1:265:1", Item::CAKE.":0:1:265:4", Item::GOLDEN_APPLE.":0:5:265:1", Item::GOLDEN_CARROT.":0:1:266:1"
					]
            ],
			[
				"baslik" => "§eKazmalar",
                "item" => Item::DIAMOND_PICKAXE,
                "itemler" =>
                    [ // item:id:miktar:ödeme id:ödeme adeti
                        Item::WOODEN_PICKAXE.":0:1:265:1", Item::STONE_PICKAXE.":0:1:265:32", Item::IRON_PICKAXE.":0:1:266:5", Item::DIAMOND_PICKAXE.":0:1:264:12"
					]
			]
        ];

    public function __construct(){
        self::$ins = $this;
    }

    public function onEnable(){
        @mkdir($this->getDataFolder());
        @mkdir($this->getDataFolder()."Arenalar/");
        @mkdir($this->getDataFolder()."Yedekler/");

        $cfg = new Config($this->getDataFolder()."config.yml", Config::YAML, [
            "BaslamaSuresi" => 61,
            "BitisSuresi" => 16,
            "KirilanBloklar" => [24, 20, 89, 49, 54, 92, 121]
        ]);
        $cfg->save();

        Server::getInstance()->getPluginManager()->registerEvents(new EventListener(), $this);
        Server::getInstance()->getScheduler()->scheduleRepeatingTask(new TabelaTask($this), 20);
        Server::getInstance()->getScheduler()->scheduleRepeatingTask(new OyunTask($this), 20);
        Server::getInstance()->getCommandMap()->register("ew", new EW());
        Server::getInstance()->getCommandMap()->register("hub", new Hub());

        $this->arenalariHazirla(); 
    }

    public static function getInstance(){
        return self::$ins;
    }
    
    public function arenalariHazirla(){
        foreach($this->arenalar() as $arena){
            if($this->arenaHazirmi($arena)){
                $this->arenaYenile($arena);
            }
        }
    }

    public function arenaOyunculari($arena){
        $ac = new Config($this->getDataFolder()."Arenalar/$arena.yml", Config::YAML);
        if($ac->exists("Oyuncular") && is_array($ac->get("Oyuncular"))){
            $oyuncular = $ac->get("Oyuncular");
        }else{
            $ac->set("Oyuncular", []);
            $ac->save();
            $oyuncular = [];
        }
        $o = array();
        foreach ($oyuncular as $olar) {
            $go = Server::getInstance()->getPlayer($olar);
            if($go instanceof Player){
                 $o[] = $olar;
            }else{
                $this->arenadanOyuncuKaldir($arena, $olar, 1);
            }
        }
        return $o;
    }

    public function arenadanOyuncuKaldir($arena, $isim, $oa = 0){
        $ac = new Config($this->getDataFolder()."Arenalar/$arena.yml", Config::YAML);
        $oyuncular = $ac->get("Oyuncular");
        if(@in_array($isim, $oyuncular)){
            $o = Server::getInstance()->getPlayer($isim);
            if($o instanceof Player && $oa != 1){
                $o->setNameTag($o->getName());
                $o->getInventory()->clearAll();
                $o->setHealth(20);
                $o->setFood(20);
                $o->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn());
            }
            $key = array_search($isim, $oyuncular);
            unset($oyuncular[$key]);
            $ac->set("Oyuncular", $oyuncular);
            $ac->save();
        }
    }

    public function arenayaOyuncuEkle($arena, $isim){
        $ac = new Config($this->getDataFolder()."Arenalar/$arena.yml", Config::YAML);
        $oyuncular = $ac->get("Oyuncular");
        if(!in_array($isim, $oyuncular)){
            $o = Server::getInstance()->getPlayer($isim);
            if($o instanceof Player){
                $o->setNameTag($o->getName());
                $o->setGamemode(0);
                $o->getInventory()->clearAll();
                $o->setHealth(20);
                $o->setFood(20);
                $o->removeAllEffects();
            }
            $oyuncular[] = $isim;
            $ac->set("Oyuncular", $oyuncular);
            $ac->save();
        }
    }

    public function arenalar(){
        $arenalar = array();
        $d = opendir($this->getDataFolder()."Arenalar");
        while($dosya = readdir($d)){
            if($dosya != "." && $dosya != ".."){
                $arena = str_replace(".yml", "", $dosya);
                if($this->arenaHazirmi($arena)){
                    $arenalar[] = $arena;
                }
            }
        }

        return $arenalar;
    }

    public function takimlar(){
        $takimlar = array(
            "TURUNCU" => "§6",
            "YESIL" => "§a",
            "SARI" => "§e",
            "ACIK-MAVI" => "§b",
            "KIRMIZI" => "§c",
            "MOR" => "§d",
            "GRI" => "§7",
            "MAVI" => "§9"
        );
        return $takimlar;
    }

    public function takimYunCevirici(){
        $tyc = array(
            "TURUNCU" => 1,
            "MOR" => 2,
            "ACIK-MAVI" => 3,
            "SARI" => 4,
            "YESIL" => 5,
            "GRI" => 8,
            "MAVI" => 11,
            "KIRMIZI" => 14
        );
        return $tyc;
    }
    

    public function arenaKontrol($arena){
        if(file_exists($this->getDataFolder()."Arenalar/$arena.yml")){
            return true;
        }else{
            return false;
        }
    }

    public function arenaHazirmi($arena){
        $ac = new Config($this->getDataFolder()."Arenalar/$arena.yml", Config::YAML);
        if($ac->get("Dunya")){
            if(file_exists($this->getDataFolder()."Yedekler/".$ac->get("Dunya")."/")){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    public function oyuncuArenadami($isim){
        $arenalar = $this->arenalar();
        $a = null;
        foreach ($arenalar as $arena){
            $ac = new Config($this->getDataFolder()."Arenalar/$arena.yml", Config::YAML);
            $oyuncular = $ac->get("Oyuncular");
            if(in_array($isim, $oyuncular)){
                $a = $arena;
                break;
            }
        }
        if($a != null){
            return $a;
        }else{
            return false;
        }
    }

    public function arenaDurum($arena){
        $ac = new Config($this->getDataFolder()."Arenalar/$arena.yml", Config::YAML);
        $durum = $ac->get("Durum");
        return $durum;
    }

    public function arenaOlustur($arena, $takim, $tbo, Player $o){
        if(!$this->arenaKontrol($arena)){
            if($takim <= 8) {
                if($tbo <= 8) {
                    $ac = new Config($this->getDataFolder() . "Arenalar/$arena.yml", Config::YAML);
                    $cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
                    $ac->set("Durum", "Lobi");
                    $ac->set("BaslamaSuresi", $cfg->get("BaslamaSuresi"));
                    $ac->set("BitisSuresi", $cfg->get("BitisSuresi"));
                    $ac->set("Takim", (int) $takim);
                    $ac->set("TakimBasinaOyuncu", (int) $tbo);
                    $ac->set("Oyuncular", array());
                    $ac->save();
                    $o->sendMessage($this->sb."§a$arena arenası basarıyla oluşturuldu!");
                }else{
                    $o->sendMessage("§8» §cTakım Başına Oyuncu sayısı 8 veya daha az olmalıdır.");
                }
            }else{
                $o->sendMessage("§8» §cTakım sayısı 8 veya daha az olmalıdır.");
            }
        }else{
            $o->sendMessage("§8» §c$arena diye bir arena zaten var.");
        }
    }

    public function arenaTakimlari($arena){
        if($this->arenaKontrol($arena)){
            $ac = new Config($this->getDataFolder() . "Arenalar/$arena.yml", Config::YAML);
            $takimlar = array();
            foreach ($this->takimlar() as $takim => $renk){
                if(!empty($ac->getNested($takim.".X"))){
                    $takimlar[] = $takim;
                }
            }
            return $takimlar;
        }else{
            return false;
        }
    }

    public function arenaAyarla($arena, $takim, Player $o){
        if($this->arenaKontrol($arena)){
            $ac = new Config($this->getDataFolder() . "Arenalar/$arena.yml", Config::YAML);
            if(!empty($this->takimlar()[$takim])){
                if(count($this->arenaTakimlari($arena)) == $ac->get("Takim")){
                    if($ac->getNested("$takim.X")){
                        $ac->setNested("$takim.X", $o->getFloorX());
                        $ac->setNested("$takim.Y", $o->getFloorY());
                        $ac->setNested("$takim.Z", $o->getFloorZ());
                        $ac->save();
                        $o->sendMessage("§8» §a$takim takımı başarıyla güncellendi!");
                    }else{
                        $o->sendMessage("§8» §cTüm takımlar ayarlandı sadece olan takımları değiştirebilirsin!");
                    }
                }else{
                    $ac->setNested("$takim.X", (int) $o->getFloorX());
                    $ac->setNested("$takim.Y", (int) $o->getFloorY());
                    $ac->setNested("$takim.Z", (int) $o->getFloorZ());
                    $ac->save();
                    $o->sendMessage("§8» ".$this->takimlar()[$takim]."$takim takımı başarıyla ayarlandı!");
                }
            }else{
                $takim = null;
                foreach ($this->takimlar() as $takim => $renk){
                    $takim .= $renk.$takim." ";
                }
                $o->sendMessage("§8» §fKullanabileceğin Takımlar: \n$takim");
            }
        }else{
            $o->sendMessage("§8» §cBöyle bir arena yok.");
        }
    }

    public function kopyalama($kaynak, $hedef){
        $dizin = opendir($kaynak);
        @mkdir($hedef);
        while (false !== ($dosya = readdir($dizin))){
            if ($dosya != "." && $dosya != "..") {
                if (is_dir($kaynak.'/'.$dosya)) {
                    $this->kopyalama($kaynak.'/'.$dosya, $hedef.'/'.$dosya);
                } else {
                    copy($kaynak.'/'.$dosya, $hedef.'/'.$dosya);
                }
            }
        }
        closedir($dizin);
    }

    public function mapReset($arena){
        $ac = new Config($this->getDataFolder()."Arenalar/$arena.yml");
        $dunya = $ac->get("Dunya");
        $level = Server::getInstance()->getLevelByName($dunya);
        if($level instanceof Level){
            Server::getInstance()->unloadLevel($level);
        }
        $this->kopyalama($this->getDataFolder()."Yedekler/".$dunya, $this->getServer()->getDataPath()."worlds/".$dunya);
        Server::getInstance()->loadLevel($dunya);
    }

    public function itemSayi(Player $o, $id){
        $items = 0;
        for($i=0; $i<36; $i++){
            $item = $o->getInventory()->getItem($i);
            if($item->getId() == $id){
                $items += $item->getCount();
            }
        }
        return $items;
    }

    public function durum($arena){
        $durum = array();
        $arti = "§8[§a+§8]";
        $eksi = "§8[§c-§8]";
        foreach($this->arenaTakimlari($arena) as $at){
            if(!@in_array($at, $this->ky[$arena])){
                $durum[] = $this->takimlar()[$at].$at.$arti." ";
            }else{
                $durum[] = $this->takimlar()[$at].$at." ".$eksi." ";
            }
        }
        return $durum;
    }

    public function arenaMesaj($arena, $mesaj){
        $oyuncular = $this->arenaOyunculari($arena);
        foreach($oyuncular as $olar){
            $o = $this->getServer()->getPlayer($olar);
            if($o instanceof Player){
                $o->sendMessage($mesaj);
            }
        }
    }

    public function oyuncuHangiTakimda(Player $o){
        $takimrenk = substr($o->getNameTag(), 0, 3);
        if(strstr($takimrenk, "§")){
            $anahtar = array_search($takimrenk, $this->takimlar());
            return $anahtar;
        }else{
            return false;
        }
    }

    public function musaitTakimlar($arena){
        $oyuncular = $this->arenaOyunculari($arena);
        $takimsayi = 0;
        $cfg = new Config($this->getDataFolder()."Arenalar/$arena.yml", Config::YAML);
        $musaittakim = array();
        foreach($this->arenaTakimlari($arena) as $takim){
            foreach($oyuncular as $olar){
                $o = $this->getServer()->getPlayer($olar);
                if($o instanceof Player){
                    if($this->oyuncuHangiTakimda($o) === $takim){
                        $takimsayi++;
                    }
                }
            }

            if($takimsayi < $cfg->get("TakimBasinaOyuncu")){
                $musaittakim[] = $takim;
            }
            $takimsayi = 0;
        }

        return $musaittakim;
    }

    public function musaitRastTakim($arena){
        $mt = $this->musaitTakimlar($arena);
        $karisik = array_rand($mt);
        return $this->takimlar()[$mt[$karisik]];
    }

    public function yunleriVer($arena, Player $o){
        foreach($this->arenaTakimlari($arena) as $at){
            $meta = $this->takimYunCevirici()[$at];
            $renk = $this->takimlar()[$at];
            $item = Item::get(35);
            $item->setDamage($meta);
            $item->setCustomName("§r§8» ".$renk.$at."§8 «");
            $o->getInventory()->addItem($item);
        }
    }

    public function yumurtaKirildimi($arena, $takim){
        if(empty($this->ky[$arena])){
            return false;
        }else{
            if(@in_array($takim, $this->ky[$arena])){
                return true;
            }else{
                return false;
            }
        }
    }
    
    public function arenaYenile($arena){
        $ac = new Config($this->getDataFolder()."Arenalar/$arena.yml", Config::YAML);
        $cfg = new Config($this->getDataFolder()."config.yml", Config::YAML);
        $lobi = Server::getInstance()->getLevelByName($ac->getNested("Lobi.Dunya"));
        if(!$lobi instanceof Level){
            Server::getInstance()->loadLevel($ac->getNested("Lobi.Dunya"));
        }
        $ac->set("Durum", "Lobi");
        $ac->set("BaslamaSuresi", (int) $cfg->get("BaslamaSuresi"));
        $ac->set("BitisSuresi", (int) $cfg->get("BitisSuresi"));
        $ac->set("Oyuncular", array());
        $ac->save();
        unset($this->ky[$arena]);
        $this->mapReset($arena);
    }

    public function tekTakimKaldimi($arena){
        $oyuncular = $this->arenaOyunculari($arena);
        $takimlar = array();
        foreach ($oyuncular as $ol){
            $o = Server::getInstance()->getPlayer($ol);
            if($o instanceof Player){
                $takim = $this->oyuncuHangiTakimda($o);
                if(!in_array($takim, $takimlar)){
                    $takimlar[] = $takim;
                }
            }
        }
        if(count($takimlar) == 1){
            return true;
        }else{
            return false;
        }
    }
    
    public function marketAc(Player $o){
        $o->getLevel()->setBlock(new Vector3($o->getFloorX(), $o->getFloorY() - 4, $o->getFloorZ()), Block::get(Block::CHEST));
        $nbt = new CompoundTag("", [ 
            new ListTag("Items", []), 
            new StringTag("id", Tile::CHEST), 
            new IntTag("x", $o->getFloorX()), 
            new IntTag("y", $o->getFloorY() - 4),
            new IntTag("z", $o->getFloorZ()),
            new StringTag("CustomName", "§6EGGWars §fMarket")
        ]);
        $nbt->Items->setTagType(NBT::TAG_Compound); 
        $tile = Tile::createTile("Chest", $o->getLevel(), $nbt); 
        if($tile instanceof Chest) {
            $env = $tile->getInventory();
            $env->clearAll();
            $marketitemler = $this->market;
            foreach($marketitemler as $slot => $marketitem){
                $mitem = Item::fromString($marketitem["item"])->setCustomName("§r".$marketitem["baslik"]);
                $env->setItem($slot, $mitem);
            }
            $o->addWindow($tile->getInventory());
            $this->mk[$o->getName()] = 1;
        }
    }
    
    public static function yildirimOlustur($x, $y, $z, Level $level){
        $yildirim = new AddEntityPacket();
        $yildirim->metadata = array();
        $yildirim->type = 93;
        $yildirim->eid = Entity::$entityCount++;
        $yildirim->speedX = 0;
        $yildirim->speedY = 0;
        $yildirim->speedZ = 0;
        $yildirim->x = $x;
        $yildirim->y = $y;
        $yildirim->z = $z;
        Server::getInstance()->broadcastPacket($level->getPlayers(), $yildirim);
    }
}
