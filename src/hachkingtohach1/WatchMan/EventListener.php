<?php

/**
 *  Copyright (c) 2022 hachkingtohach1
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 *  SOFTWARE.
 */

namespace hachkingtohach1\WatchMan;

use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;
use pocketmine\Block\Ice;
use pocketmine\entity\effect\Effect;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\effect\EffectManager;
use pocketmine\player\Player;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\inventory\ChestInventory;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;

/**
 * base plugin EventListener, holding events like onPlayerLogin etc
 * 
 */
class EventListener implements Listener{
    /*@var WatchMan*/
    private ?WatchMan $plugin;

    /**
     * base class constructor
     *
     * @param WatchMan $plugin
     */
    public function __construct(WatchMan $plugin){
        $this->plugin = $plugin;
    }

    /**
     * @param Player $player
     * @return array
     */
    private function getDataPlayer(Player $player) :array{
		if(!isset($this->plugin->players[$player->getXuid()])){
			$this->plugin->players[$player->getXuid()] = [
                "violation" => 0,
                "interact-block" => false,
			    "time-attack" => false,
			    "cps" => false,
			    "hack" => false,
			    "open-chest" => false,
                "last-on-ground" => false
		    ];
		}
        return $this->plugin->players[$player->getXuid()];
    }

    /**
     * @param Player $player
     * @return void
     */
    private function addViolation(Player $player){
        $this->plugin->players[$player->getXuid()]["violation"] += 1;
    }
	
	/**
	 * Calls when player comes into game
	 * 
	 * @param PlayerLoginEvent $event
	 */
	public function onPlayerLogin(PlayerLoginEvent $event){
		$player = $event->getPlayer();	
		$this->plugin->players[$player->getXuid()] = [
            "violation" => 0,
            "interact-block" => false,
			"time-attack" => false,
			"cps" => false,
			"hack" => false,
			"open-chest" => false,
            "last-on-ground" => false
		];
	}

    /**
     * @param PlayerInteractEvent $event
     * @return void
     */
    public function onPlayerInteract(PlayerInteractEvent $event) :void{
        $player = $event->getPlayer();
        $locationPlayer = $player->getLocation();
        $block = $event->getBlock();
        $this->plugin->players[$player->getXuid()]["interact-block"] = $block;
    }

    /**
     * @param BlockBreakEvent $event
     * @return void
     */
    public function onBlockBreak(BlockBreakEvent $event) :void{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$locBlock = $block->getPosition();
		$dataPlayer = $this->getDataPlayer($player);
		//antinuker
		if($dataPlayer["interact-block"] != false){
			$lastBlock = ($dataPlayer["interact-block"])->getPosition();
			if((int)$locBlock->x != (int)$lastBlock->x and (int)$locBlock->y != (int)$lastBlock->y and (int)$locBlock->z != (int)$lastBlock->z){
			    $event->cancel();
			}
		}
	}

    /**
     * @param BlockPlaceEvent $event
     * @return void
     */
    public function onBlockPlace(BlockPlaceEvent $event) :void{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$locBlock = $block->getPosition();
		$dataPlayer = $this->getDataPlayer($player);
		//antifillerblock
		if($dataPlayer["interact-block"] != false){
			$lastBlock = ($dataPlayer["interact-block"])->getPosition();
			if((int)$locBlock->x != (int)$lastBlock->x and (int)$locBlock->y != (int)($lastBlock->y + 1) and (int)$locBlock->z != (int)$lastBlock->z){
			    $event->cancel();
			}
		}
	}

    /**
     * @param EntityDamageByEntityEvent $event
     * @return void
     */
    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event) :void{
		$entity = $event->getEntity();
		$damager = $event->getDamager();
        $locationEntity = $entity->getLocation();
        $locationDamager = $damager->getLocation();
        $distance = sqrt(pow($locationEntity->x - $locationDamager->x, 2) + pow($locationEntity->y - $locationDamager->y, 2) + pow($locationEntity->z - $locationDamager->z, 2));
        //reach or killaura or hitbox
        if($distance >= 3){
            $event->cancel();
        }
		if($damager instanceof Player){
			$dataPlayer = $this->getDataPlayer($damager);
			//autoclick or killaura(if attack than 2 players)
			if($dataPlayer["time-attack"] != false){
				$timeDiff = microtime(true) - $dataPlayer["time-attack"];
				if((int)$timeDiff > 1){
					if($dataPlayer["cps"] > 15){
						$this->plugin->players[$damager->getXuid()]["hack"] = "(Autoclick)";
                        $this->addViolation($damager);
                        $event->cancel();
						return;
					}
					$this->plugin->players[$damager->getXuid()]["cps"] = false;
				}else{
					$this->plugin->players[$damager->getXuid()]["cps"] += 1;
				}
			}else{
				$this->plugin->players[$damager->getXuid()]["time-attack"] = microtime(true);
			}
		}
	}

    /**
     * @param InventoryOpenEvent $event
     * @return void
     */
    public function onInventoryOpenEvent(InventoryOpenEvent $event) :void{
        $player = $event->getPlayer();       
		$inventory = $event->getInventory();
		$dataPlayer = $this->getDataPlayer($player);
        if($dataPlayer["open-chest"] != false){
			$this->plugin->players[$player->getXuid()]["open-chest"] = false;
	    }		
		if($inventory instanceof ChestInventory){
			if($dataPlayer["open-chest"] == false){
				$this->plugin->players[$player->getXuid()]["open-chest"] = microtime(true); 
			}
		}
    }

    /**
     * @param InventoryCloseEvent $event
     * @return void
     */
    public function onInventoryCloseEvent(InventoryCloseEvent $event) :void{
        $player = $event->getPlayer();
		$inventory = $event->getInventory();
        $dataPlayer = $this->getDataPlayer($player);
		if($inventory instanceof ChestInventory){
            if($dataPlayer["open-chest"] != false){
                $this->plugin->players[$player->getXuid()]["open-chest"] = false;
            }
		}
    }

    /**
     * @param InventoryTransactionEvent $event
     * @return void
     */
    public function onInventoryTransaction(InventoryTransactionEvent $event) :void{
        $transaction = $event->getTransaction();
        $source = $transaction->getSource();
		//chestaura
        if($source instanceof Player){
            $dataPlayer = $this->getDataPlayer($source);
            foreach($transaction->getInventories() as $inventory){
                if($inventory instanceof ChestInventory) {
                    if($dataPlayer["open-chest"] == false){
                        $this->plugin->players[$source->getXuid()]["open-chest"] = microtime(true);
                    }
                    $timeDiff = microtime(true) - $dataPlayer["open-chest"];
                    if ($timeDiff < 0.3) {
                        $event->cancel();
                        $this->addViolation($source);
                    }
                }
            }
        }
	}

    /**
     * @param PlayerMoveEvent $event
     * @return void
     */
    public function onPlayerMove(PlayerMoveEvent $event) :void{
        $player = $event->getPlayer();
        $locationNow = $player->getLocation();
        $dataPlayer = $this->getDataPlayer($player);
        //speed
        $distX = (double)($event->getTo()->getX() - $event->getFrom()->getX());
        $distZ = (double)($event->getTo()->getZ() - $event->getFrom()->getZ());
        $lastDist = (double)(($distX * $distX) + ($distZ * $distZ));
        $friction = (float)floor(0.91);
        $shiftedLastDist = (double)($lastDist * $friction);
        $equalness = (double)($lastDist - $shiftedLastDist);
        $scaledEqualness = $equalness * 138;
        $maxSpeed = 1000;
        if(!$player->getAllowFlight() and $player->isSurvival() and $scaledEqualness >= $maxSpeed){
            $event->cancel();
        }
        if($player->isOnGround()){
            if($dataPlayer["last-on-ground"] == false){
                $speedLevel = 0;
				$effectManager = new EffectManager($player);
                if($effectManager->has(VanillaEffects::SPEED())){
					if($effectManager->get(VanillaEffects::SPEED())->getEffectLevel() >= 1){
                        $speedLevel += $effectManager->get(VanillaEffects::SPEED())->getEffectLevel();
					}
				}
                $blockDown = $player->getWorld()->getBlock(new Vector3($locationNow->x, $locationNow->y - 0.5, $locationNow->z));
                if($blockDown instanceof Ice){
                    $speedLevel += 2;
                }
                $this->plugin->players[$player->getXuid()]["last-on-ground"] = [
                    "time" => microtime(true),
                    "x" => $player->getLocation()->x,
                    "y" => $player->getLocation()->y,
                    "z" => $player->getLocation()->z,
                    "have-effect" => $speedLevel
                ];
            }else{
                $timeDiff = microtime(true) - $dataPlayer["last-on-ground"]["time"];
                $xLast = $dataPlayer["last-on-ground"]["x"];
                $yLast = $dataPlayer["last-on-ground"]["y"];
                $zLast = $dataPlayer["last-on-ground"]["z"];
                if($timeDiff > 1){
                    $distance = sqrt(pow($locationNow->x - $xLast, 2) + pow($locationNow->y - $yLast, 2) + pow($locationNow->z - $zLast, 2));
                    if($distance >= 6 + (int)$dataPlayer["last-on-ground"]["have-effect"]){
                        $event->cancel();
                        $this->addViolation($player);
                    }
					$this->plugin->players[$player->getXuid()]["last-on-ground"] = false;
                }
            }
        }else{
            if($dataPlayer["last-on-ground"] != false){
                $this->plugin->players[$player->getXuid()]["last-on-ground"] = false;
            }
        }
    }

    /**
     * @param PlayerToggleSneakEvent $event
     * @return void
     */
    public function onPlayerToggleSneakEvent(PlayerToggleSneakEvent $event) :void{
        $player = $event->getPlayer();
        $locationPlayer = $player->getLocation();
        //clickTp
        if($player->isOnGround()){
            $player->teleport(new Vector3($locationPlayer->x, $locationPlayer->y, $locationPlayer->z));
        }
    }
}