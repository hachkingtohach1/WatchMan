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

use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\player\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

/**
 * Modules: Nuker, Filler, ChestStealer(WIP),
 * Speed, Timer, KillAura, AutoClick(WIP), Reach, TapToTP
*/
class WatchMan extends PluginBase{
	/*@var array*/
	public array $players = [];
	/*@var static*/
	private static $instance;

	public function onLoad() :void{
        self::$instance = $this;
	}
	
	/**
	 * @return MatchMan
	 */
    public static function getInstance(): MatchMan{
        return self::$instance;
    }

    /**
	 * calls when plugin enable
	 */
	public function onEnable() :void{
		//upload listener
		$listener = new EventListener($this);
		$this->getServer()->getPluginManager()->registerEvents($listener, $this);
	}
	
	/**
	 * @param Player $player
	 * @return array
	 */
	public function getPlayer(Player $player) :array{
		if(isset($this->players[$player->getXuid()])){
			return $this->players[$player->getXuid()];
		}
		return [];
	}

    /**
     * @param CommandSender $sender
     * @param Command $command
     * @param string $label
     * @param array $args
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool{
        if($command->getName() == "wm"){
            if(!isset($args[0]) or !isset($args[1])){
                $sender->sendMessage("/wm <player> <modules>");
                return false;
            }
            $hacker = false;
            foreach($this->getServer()->getOnlinePlayers() as $player){
                if(strtolower($player->getName()) == strtolower($args[0])){
                    $hacker = $player;
                }
            }
            if($hacker == false){
                $sender->sendMessage(TextFormat::RED."This player is not online!");
                return false;
            }
            $modules = [
                "fly","nuker","fastbreak","fastplace","eatfast","cheststealer","fillblock",
                "rapidbuild","clicktotp","autoclick","killaura","forcefield","hitbox","angle",
                "reach","noclip","airjump","speed","nofall","jesus","highjump","glide","antivoid",
                "noweb","editionfaker","timer","infiniteaura","antibot","jetpack", "steps",
                "antiknockback", "antikb", "badpitchpacket", "invalidcreativetransaction", "transcontinent"
            ];
            if(!isset($modules[$args[1]])){
                $sender->sendMessage(TextFormat::RED."I do not understand you, sorry!");
                return false;
            }
            if(isset($this->players[$hacker->getXuid()])){
                for($i = 1; $i <= 100; $i++){
                    if(isset($args[$i])){
                        $this->players[$hacker->getXuid()]["target"][$args[$i]] = $args[$i];
                    }
                }
                $sender->sendMessage(TextFormat::GREEN."Thanks for your Cheating report. We understand your concerns and it will be reviewed as soon as possible.");
            }
            return true;
        }
        return false;
    }
}