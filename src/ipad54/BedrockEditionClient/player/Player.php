<?php
declare(strict_types=1);

namespace ipad54\BedrockEditionClient\player;

use ipad54\BedrockEditionClient\network\NetworkSession;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\types\PlayMode;
use pocketmine\world\Position;
use Ramsey\Uuid\UuidInterface;

class Player{

	private NetworkSession $networkSession;
	private LoginInfo $loginInfo;
	private UuidInterface $uuid;
	private StartGamePacket $startGamePacket;
	private Skin $skin;

	private string $username;

	private int $id;

	private bool $spawned = false;

	public function __construct(NetworkSession $networkSession, LoginInfo $loginInfo, StartGamePacket $startGamePacket, int $id, public Location $location){
		$this->networkSession = $networkSession;
		$this->loginInfo = $loginInfo;
		$this->uuid = $loginInfo->getUuid();
		$this->skin = $loginInfo->getSkin();

		$this->username = $loginInfo->getUsername();

		$this->startGamePacket = $startGamePacket;

		$this->id = $id;
	}

	public function getLoginInfo() : LoginInfo{
		return $this->loginInfo;
	}

	function sendPlayerPosition(Location $location) {
		$this->location = $location;
		$pk = PlayerAuthInputPacket::create(
			position: $location,
			pitch: $location->getPitch(),
			yaw: $location->getYaw(),
			headYaw: $location->getYaw(),
			moveVecX: 0,
			moveVecZ: 0,
			inputFlags: 0,
			inputMode: 1,
			playMode: 2,
			interactionMode: 0,
			vrGazeDirection: null,
			tick: 1,
			delta: new Vector3(0, 0, 0),
			itemInteractionData: null,
			itemStackRequest: null,
			blockActions: [],
			analogMoveVecX: 0.0,
			analogMoveVecZ: 0.0
		);

		$this->getNetworkSession()->sendDataPacket($pk);

	}

	public function getNetworkSession() : NetworkSession{
		return $this->networkSession;
	}

	public function getUuid() : UuidInterface{
		return $this->uuid;
	}

	public function getStartGameInfo() : StartGamePacket{
		return $this->startGamePacket;
	}

	public function getSkin() : Skin{
		return $this->skin;
	}

	public function getUsername() : string{
		return $this->username;
	}

	public function getId() : int{
		return $this->id;
	}

	public function isSpawned() : bool{
		return $this->spawned;
	}

	public function setSpawned(bool $spawned) : void{
		$this->spawned = $spawned;
	}
}