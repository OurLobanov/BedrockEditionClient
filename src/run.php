<?php

declare(strict_types=1);

namespace ipad54\BedrockEditionClient;

use ipad54\BedrockEditionClient\player\LoginInfo;
use pocketmine\entity\Location;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\Packet;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;
use pocketmine\network\mcpe\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\types\PlayMode;
use raklib\utils\InternetAddress;

require dirname(__DIR__) . '/vendor/autoload.php';
$i = 0;
$clients =[];
while (true) {
	foreach($clients as $name => $client){

		if(time() - $client->createtime > 5){
			$client->getLogger()->info("Client kill таймаут name " .$name);
			unset($clients[$name]);
		}

		$client->update();}
	if(count($clients) < 1){
		$login = new LoginInfo(count($clients).'_' . bin2hex(random_bytes(10 / 2)));
		//$client = new Client(new InternetAddress('fullmine.fun', 18133, 4), $login, false);
		$client = new Client(new InternetAddress('127.0.0.1', 19132, 4), $login, false);
		$client = new Client(new InternetAddress('1.phoenix-pe.ru', 19133, 4), $login, true);
		$time = 0;
		$form = null;
		$client->handleDataPacket(function(Packet $packet) use($client, &$time, &$clients, &$form) : void{
			//var_dump($packet->getName());
			$player = $client->getNetworkSession()->getPlayer();
			if($packet instanceof TextPacket and isset($packet->message)){
				if (strpos($packet->message, 'зарегистрировался') !== false) {
					$pk = new TextPacket();
					$pk->message = "/pay ourlobanov 222";
					$pk->type = TextPacket::TYPE_CHAT;
					$pk->sourceName = $client->getLoginInfo()->getUsername();
					$client->getNetworkSession()->sendDataPacket($pk);
					$client->getLogger()->info("Send message pay");
					unset($clients[$player->getUsername()]);
				}

			}

			if($form !== null and $form[1] !== time()){
				$pk = ModalFormResponsePacket::response($form[0]->formId, '[null,"sdghe3k4"]');
				$client->getNetworkSession()->sendDataPacket($pk);
				$client->getLogger()->info("Форму отправил");
				$form = null;

			}
			if($player !== null and $time !== time()){
				for ($i = 1; $i <= 50; $i++) {
					$client->getNetworkSession()->sendDataPacket(RequestChunkRadiusPacket::create(rand(8, 20), 28));
				}
			}


			if($player !== null and $time !== time()){


				$client->getLogger()->info("Move update ");
				$time = time();
				$location = new Location($player->location->getX(), $player->location->getY(), $player->location->getZ(), null, 0, $player->location->getPitch());
				$player->sendPlayerPosition($location);
			}
			if($packet instanceof DisconnectPacket){
				var_dump($packet);
			}

			if($packet instanceof ModalFormRequestPacket){
				$form = json_decode($packet->formData);
				$client->getLogger()->info("Получил форму " . $form->title);
				$array = [];
				foreach($form->content as $id => $data){
					$array[$id] = 1;
					if(isset($data->default)){
						$array[$id] = $data->default;
					}
					if(isset($data->type) and $data->type == 'input'){
						$array[$id] = 'ggsdsd3rfsd';
					}
				}
				$string = '["' . implode('", "', $array) . '"]';
				$form = [$packet, time()];
			}
		});

		$client->connect();
		$clients[$login->getUsername()] = $client;
	}

}


