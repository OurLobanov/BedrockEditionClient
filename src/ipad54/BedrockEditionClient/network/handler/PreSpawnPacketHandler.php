<?php
declare(strict_types=1);

namespace ipad54\BedrockEditionClient\network\handler;

use ipad54\BedrockEditionClient\network\NetworkSession;
use pocketmine\entity\Location;
use pocketmine\network\mcpe\compression\ZlibCompressor;
use pocketmine\network\mcpe\handler\PacketHandler;
use pocketmine\network\mcpe\protocol\ClientToServerHandshakePacket;
use pocketmine\network\mcpe\protocol\EmoteListPacket;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\MoveActorDeltaPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\NetworkSettingsPacket;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;
use pocketmine\network\mcpe\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\mcpe\protocol\ResourcePacksInfoPacket;
use pocketmine\network\mcpe\protocol\ServerToClientHandshakePacket;
use pocketmine\network\mcpe\protocol\SetLocalPlayerAsInitializedPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\TickSyncPacket;
use pocketmine\network\mcpe\protocol\types\CompressionAlgorithm;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use Ramsey\Uuid\Uuid;

final class PreSpawnPacketHandler extends PacketHandler{

	public function __construct(private NetworkSession $networkSession){}

	public function handleServerToClientHandshake(ServerToClientHandshakePacket $packet) : bool{
		$this->networkSession->startEncryption($packet->jwt);

		$this->networkSession->sendDataPacket(ClientToServerHandshakePacket::create());
		return true;
	}

	public function handleResourcePacksInfo(ResourcePacksInfoPacket $packet) : bool{
		$this->networkSession->sendDataPacket(ResourcePackClientResponsePacket::create(ResourcePackClientResponsePacket::STATUS_COMPLETED, []));
		return true;
	}

	public function handleStartGame(StartGamePacket $packet) : bool{
		$this->networkSession->createPlayer($packet);
		$this->networkSession->getPlayer()->location = new Location($packet->playerPosition->getX(), $packet->playerPosition->getY(), $packet->playerPosition->getZ(), null, $packet->yaw, $packet->pitch);
		$this->networkSession->sendDataPacket(RequestChunkRadiusPacket::create(8, 28));
		$this->networkSession->sendDataPacket(TickSyncPacket::request(0));

		return true;
	}

	public function handleText(TextPacket $packet) : bool{
		$this->networkSession->TextPacket($packet);
		return true;
	}

	public function handlePlayStatus(PlayStatusPacket $packet) : bool{
		if($packet->status === PlayStatusPacket::PLAYER_SPAWN){
			$this->networkSession->sendDataPacket(SetLocalPlayerAsInitializedPacket::create($this->networkSession->getClient()->getId()));
			$this->networkSession->getPlayer()->setSpawned(true);

			//$this->networkSession->setHandler(null);

			$this->networkSession->getClient()->getLogger()->info("Player was spawned");
			$pk = new InteractPacket();
			$pk->action = InteractPacket::ACTION_MOUSEOVER;
			$pk->targetActorRuntimeId = 0;
			$pk->x = $pk->y = $pk->z = 0.0;
			$this->networkSession->sendDataPacket($pk);

			$this->networkSession->sendDataPacket(MobEquipmentPacket::create($this->networkSession->getPlayer()->getId(), new ItemStackWrapper(0, ItemStack::null()), 2, 2, 0));

			$emoteIds = [
				Uuid::fromString('17428c4c-3813-4ea1-b3a9-d6a32f83afca'),
				Uuid::fromString('ce5c0300-7f03-455d-aaf1-352e4927b54d'),
				Uuid::fromString('9a469a61-c83b-4ba9-b507-bdbe64430582'),
				Uuid::fromString('4c8ae710-df2e-47cd-814d-cc7bf21a3d67')
			];

			$pk = EmoteListPacket::create(
				$this->networkSession->getPlayer()->getId(),
				$emoteIds
			);

			$this->networkSession->sendDataPacket($pk);

		}

		return true;
	}

	public function handleNetworkSettings(NetworkSettingsPacket $packet) : bool{
		if($packet->getCompressionAlgorithm() !== CompressionAlgorithm::ZLIB){
			throw new \InvalidArgumentException("Unsupported compression algorithm");
		}

		$this->networkSession->setCompressor(new ZlibCompressor(ZlibCompressor::DEFAULT_LEVEL, ZlibCompressor::DEFAULT_THRESHOLD, PHP_INT_MAX));
		$this->networkSession->processLogin();
		return true;
	}
}