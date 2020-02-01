<?php
declare(strict_types = 1);

namespace JackMD\CPS;

use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

class CPS extends PluginBase{

    const SWISH_SOUNDS = [
        LevelSoundEventPacket::SOUND_ATTACK,
        LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE,
        LevelSoundEventPacket::SOUND_ATTACK_STRONG
    ];
	
	/** @var array */
	private $clicks = [];

	/** @var array */
	private $deviceInput = [];

	/** @var array */
	private $actions = [];
	
	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
		$this->getLogger()->info("CPS Plugin Enabled.");
	}

    /**
     * @param string $player
     * @param int $input
     *
     * Sets the player's device input.
     */
    public function setDeviceInput(string $player, int $input) : void {
        $this->deviceInput[$player] = $input;
    }

    /**
     * @param Player $player
     * @return int
     *
     * Gets the player's device input.
     */
    public function getDeviceInput(Player $player) : int {
        if(!isset($this->deviceInput[$player->getLowerCaseName()])) {
            return -1;
        }
        return $this->deviceInput[$player->getLowerCaseName()];
    }

	
	/**
	 * @param Player $player
	 * @return int
	 */
	public function getClicks(Player $player): int {

		$clicks = $this->updateClicks($player);

		return count($clicks);
	}


    /**
     * @param Player $player
     * @param Position|null $pos
     * @param int
     *
     * Adds a click to the player.
     */
    public function addClick(Player $player, Position $pos = null): void{

	    if(!isset($this->clicks[$player->getLowerCaseName()])){
	        $this->clicks[$player->getLowerCaseName()] = [];
        }

	    $time = (int)round(microtime(true) * 1000);
	    $clicks = $this->updateClicks($player, false);

	    if($pos !== null) {
	        $actions = $this->getAction($player);
	        $lastAction = (int)$actions['previous']['action']; $lastActionTime = $actions['previous']['time'];
	        $currentActionTime = $actions['current']['time'];
	        if($lastAction === PlayerActionPacket::ACTION_ABORT_BREAK) {
	            $difference = $currentActionTime - $lastActionTime;
	            if($difference > 5) {
	                $clicks[$time] = true;
	                $this->clicks[$player->getLowerCaseName()] = $clicks;
                }
            }
	        return;
        }

	    $clicks[$time] = true;
	    $this->clicks[$player->getLowerCaseName()] = $clicks;
	}

    /**
     * @param Player $player
     * @param bool $update
     * @return array
     *
     * Updates the clicks.
     */
    public function updateClicks(Player $player, bool $update = true) : array {

	    if(!isset($this->clicks[$player->getLowerCaseName()])) {
	        return [];
        }

        $clicks = $this->clicks[$player->getLowerCaseName()];
        $removedClicks = [];

        $currentMilliseconds = (int)round(microtime(true) * 1000);

        foreach($clicks as $millis => $value) {
            $difference = $currentMilliseconds - $millis;
            if($difference >= 1000) {
                $removedClicks[$millis] = true;
            }
        }

        $clicks = array_diff_key($clicks, $removedClicks);
        if($update) {
            $this->clicks[$player->getLowerCaseName()] = $clicks;
        }

        return $clicks;
    }


    /**
     * @param Player $player
     * @param int $action
     *
     * Sets the action of the player.
     */
    public function setAction(Player $player, int $action) : void {

        $time = round(microtime(true) * 1000);

        if(!isset($this->actions[$player->getLowerCaseName()])) {

            $this->actions[$player->getLowerCaseName()] = [
                'previous' => [
                    'action' => $action,
                    'time' => $time
                ],
                'current' => [
                    'action' => $action,
                    'time' => $time
                ]
            ];

        } else {

            $actions = $this->actions[$player->getLowerCaseName()];
            $previous = $actions['current'];

            $this->actions[$player->getLowerCaseName()] = [
                'previous' => $previous,
                'current' => [
                    'action' => $action,
                    'time' => $time
                ]
            ];
        }
    }


    /**
     * @param Player $player
     * @return array
     *
     * Gets the actions of the player.
     */
    private function getAction(Player $player) {
        if(!isset($this->actions[$player->getLowerCaseName()])) {
            return [];
        }
        return $this->actions[$player->getLowerCaseName()];
    }
}