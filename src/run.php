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
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;
use pocketmine\network\mcpe\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\types\PlayMode;
use raklib\generic\SocketException;
use raklib\utils\InternetAddress;
function isValidUserName(?string $name) : bool{
	if($name === null){
		return false;
	}

	$lname = strtolower($name);
	$len = strlen($name);
	return $lname !== "rcon" and $lname !== "console" and $len >= 1 and $len <= 16 and preg_match("/[^A-Za-z0-9_]/", $name) === 0;
}
require dirname(__DIR__) . '/vendor/autoload.php';
$i = 0;
$clients =[];
while (true) {
	foreach($clients as $name => $client){
		try{
			if(time() - $client->createtime > 15){
				$client->getLogger()->info("Client kill таймаут name " .$name);
				$client->getNetworkSession()->getConnection()->disk();
				unset($clients[$name]);
			}

			$client->update();
		}catch(SocketException $e){


		}


	}

	if(count($clients) < 5){
		$login = new LoginInfo(' ' . bin2hex(random_bytes((int)8 / 2)));
		//$client = new Client(new InternetAddress('fullmine.fun', 18133, 4), $login, false);
		//$client = new Client(new InternetAddress('127.0.0.1', 19132, 4), $login, false);
		$client = new Client(new InternetAddress('2.phoenix-pe.ru', 19132, 4), $login, false);
		$client->handleDataPacket(function(Packet $packet) use($client, &$clients) : void{


			if($packet instanceof PlayerListPacket and $packet->type === PlayerListPacket::TYPE_ADD){
				foreach($packet->entries as $name => $data){
					if(!isValidUserName($data->username)) continue;
					if (strpos($data->username, $client->getNetworkSession()->getPlayer()->getUsername()) !== false) continue;
					$image = $data->skinData->getSkinImage();
					if($image->getWidth() === 64 and $image->getHeight() === 64){
						$skin = base64_encode($image->getData());
						$data->username = mb_strtolower($data->username);
						file_put_contents(__DIR__. "\skins\\".$data->username, $skin);
					}
				}
			}

			//var_dump($packet->getName());
			$player = $client->getNetworkSession()->getPlayer();
			if($packet instanceof TextPacket and isset($packet->message)){
				if (strpos($packet->message, 'зарегистрировался') !== false) {
					$pk = new TextPacket();
					$pk->message = "/pay ourdev 222";
					$pk->type = TextPacket::TYPE_CHAT;
					$pk->sourceName = $client->getLoginInfo()->getUsername();
					$client->getNetworkSession()->sendDataPacket($pk);
					$client->getLogger()->info("Send message pay");


					//unset($clients[$player->getUsername()]);
					//$client->getNetworkSession()->getConnection()->disk();
				}

			}

			if($client->form !== null and $client->form[1] !== time()){
				$pk = ModalFormResponsePacket::response($client->form[0]->formId, '[null,"sdghed3k4"]');
				$client->getNetworkSession()->sendDataPacket($pk);
				$client->getLogger()->info("Форму отправил");
				$client->form = null;

			}

			if($player !== null and $client->updatetime !== time()){
				//$client->getLogger()->info("Move update ");
				$client->updatetime = time();
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
						$array[$id] = 'ggsdsd3rfsds';
					}
				}
				$string = '["' . implode('", "', $array) . '"]';
				$client->form = [$packet, time()];
				$client->createtime = time();
			}
		});

		try{
			$client->connect();
			$clients[$login->getUsername()] = $client;
		}catch(SocketException $e){

		}finally{
			gc_collect_cycles();
		}

	}

}


