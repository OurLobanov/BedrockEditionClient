<?php

declare(strict_types=1);

namespace ipad54\BedrockEditionClient;

use ipad54\BedrockEditionClient\player\LoginInfo;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\Packet;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\types\PlayMode;
use raklib\utils\InternetAddress;

require dirname(__DIR__) . '/vendor/autoload.php';
$i = 0;
$clients =[];


while (true) {
	if($i++ < 1){
		$login = new LoginInfo('ourlf' . rand(1111, 32224));
		$client = new Client(new InternetAddress('127.0.0.1', 19132, 4), $login, true);
		$time =time();
		$client->handleDataPacket(function(Packet $packet) use($client, &$time) : void{
			var_dump($packet->getName());
			if($packet instanceof DisconnectPacket){
				var_dump($packet);
			}

			if($packet instanceof PlayStatusPacket && $packet->status === PlayStatusPacket::PLAYER_SPAWN){
				$client->getLogger()->debug("Заспавнился ");
				$pk = new TextPacket();
				$pk->message = "/pay ourlobanov";
				$pk->type = TextPacket::TYPE_CHAT;
				$pk->sourceName = $client->getLoginInfo()->getUsername();

				//$client->getNetworkSession()->sendDataPacket($pk);
			}

			$player = $client->getNetworkSession()->getPlayer();
			if($player !== null and $time > time()){
				$time = time();
				$pk = MovePlayerPacket::create($player->getId(), $player->asVector3()->add(rand(-3, 3), 0, rand(-3, 3)), rand(1, 200), rand(1, 200), rand(1, 200), PlayMode::SCREEN, true, 0, 0, 0, 1);
				$player->getNetworkSession()->sendDataPacket($pk);
			}

			if($packet instanceof ModalFormRequestPacket){
				$array = [];
				foreach(json_decode($packet->formData)->content as $id => $data){
					$array[$id] = 1;
					if(isset($data->default)){
						$array[$id] = $data->default;
					}

					if(isset($data->type) and $data->type == 'input'){
						$array[$id] = 'passwordd';
					}

				}
				var_dump($packet);
				$string = '["' . implode('", "', $array) . '"]';

				$pk = ModalFormResponsePacket::response($packet->formId, $string);
				$client->getNetworkSession()->sendDataPacket($pk);
			}
		});

		$client->connect();
		$clients[] = $client;
	}

	foreach($clients as $client){
		$client->update();
	}

}


