<?php
/*
Copyright (c) 2018 Thunder33345

Permission to use, copy, modify, and distribute this software for any
purpose without fee is hereby granted, provided that the above
copyright notice and this permission notice appear in all copies.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
*/
/** Created By Thunder33345 **/
declare(strict_types=1);
namespace Thunder33345\StaffChat;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\level\sound\AnvilFallSound;
use pocketmine\level\sound\EndermanTeleportSound;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use Thunder33345\StaffChat\Commands\StaffChatCommand;

class StaffChat extends PluginBase implements Listener
{

  const permChat = 'staffchat.chat';
  const permRead = 'staffchat.read';
  private $console = true;
  private $prefix = '.';
  private $format = '';
  private $pluginFormat;
  private $consolePrefix;
  private $chatting = [];

  private $joinMsg = '';
  private $leaveMsg = '';


    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getPluginManager();
    $this->getServer()->getCommandMap()->register("StaffChat", new StaffChatCommand($this));
    if(!file_exists($this->getDataFolder())) $this->getDataFolder();
    $this->saveDefaultConfig();
    $this->console = (bool)$this->getConfig()->get('auto-attach', true);
    $this->prefix = $this->getConfig()->get('prefix', ".");
    $this->consolePrefix = $this->getConfig()->get('console-prefix', '[StaffChat] ');
    $this->format = $this->replaceColour($this->getConfig()->get('player-format'));
    $this->pluginFormat = $this->replaceColour($this->getConfig()->get('plugin-format'));
    if($this->getConfig()->get('joinleave')){
      $this->joinMsg = $this->replaceColour($this->getConfig()->get('join'));
      $this->leaveMsg = $this->replaceColour($this->getConfig()->get('leave'));
    }
  }

  private function rawBroadcast($message)
  {
    foreach($this->getReadPlayers() as $player) $player->sendMessage($message);
    if($this->console) $this->getServer()->getLogger()->info($this->consolePrefix.$message);
  }

  private function playerBroadcast(Player $player, $message)
  {
    if(strlen($this->format) <= 0) $this->format = $this->replaceColour($this->getConfig()->get('player-format'));
    $formatted = str_replace('%player%', $player->getName(), $this->format);
    if($this->getConfig()->get('functions', false) == true) $message = $this->phraseFunctions($player, $message);
    $formatted = str_replace('%msg%', $this->replaceColour($message), $formatted);
    $this->rawBroadcast($formatted);
  }


  private function phraseFunctions(Player $player, $message)
  {
    $functions = ['$pos', '$ping', '$near'];
    foreach($functions as $function){
      if(strpos($message, $function) === false) continue;
      switch($function){
        case '$pos':
          $vec = $player->floor();
          $pos = "Level: ".$player->getLevel()->getName().' X: '.$vec->x.' Y: '.$vec->y.' Z: '.$vec->x;
          $message = str_replace('$pos', $pos, $message);
          break;
        case '$ping':
          foreach($this->getReadPlayers() as $notify){
            $notify->getLevel()->addSound(new EndermanTeleportSound($notify), $notify);
            $notify->getLevel()->addSound(new AnvilFallSound($notify), $notify);
          }
          $message = str_replace('$ping', TextFormat::BOLD.TextFormat::GREEN.'$ping'.TextFormat::RESET, $message);
          break;
        case '$near':
          preg_match_all('/\$near([0-9]+)\$/', $message, $matches);
          foreach($matches[0] as $key => $match){
            if(strpos($message, $match) === false) continue;
            $distance = $matches[1][$key];
            $players = [];
            foreach($player->getLevel()->getPlayers() as $other){
              if(($dist = $other->distance($player)) > $distance) continue;
              $players[] = $other->getName().' (GM:'.$this->getGamemode($other->getGamemode()).' Dist:'.$dist.')';
              $result = 'Near me('.count($players).'): '.implode(', ', $players);
              $message = str_replace($match, $result, $message);
            }
          }
          break;
      }
    }
    return $message;
  }

  public function onJoin(PlayerJoinEvent $event)
  {
    if(!$event->getPlayer()->hasPermission(self::permRead) AND !$event->getPlayer()->hasPermission(self::permChat)) return;
    if(!(bool)$this->getConfig()->get('joinleave')) return;
    if(strlen($this->joinMsg) <= 0) $this->joinMsg = $this->replaceColour($this->getConfig()->get('join'));
    $msg = str_replace('%staff%', $event->getPlayer()->getName(), $this->joinMsg);
    $this->rawBroadcast($msg);
  }

  public function onLeave(PlayerQuitEvent $event)
  {
    if(!$event->getPlayer()->hasPermission(self::permRead) AND !$event->getPlayer()->hasPermission(self::permChat)) return;
    if(!(bool)$this->getConfig()->get('joinleave')) return;
    if(strlen($this->leaveMsg) <= 0) $this->leaveMsg = $this->replaceColour($this->getConfig()->get('leave'));
    $msg = str_replace('%staff%', $event->getPlayer()->getName(), $this->leaveMsg);
    $this->rawBroadcast($msg);
  }

  public function onChat(PlayerCommandPreprocessEvent $event)
  {
    $message = $event->getMessage();
    $player = $event->getPlayer();
    $sub = strtolower(substr($message, 0, strlen($this->prefix)));
    if(substr($message, 0, 1) === "/") return;
    if($sub === $this->prefix){
      $event->setCancelled(true);
      if(!$player->hasPermission(self::permChat)){
        return;
      }
      $message = substr($message, strlen($this->prefix));
      $this->playerBroadcast($player, $message);
    }elseif($this->isChatting($player)){
      if(!$player->hasPermission(self::permChat)){
        $this->setChatting($player, false);
        return;
      }
      $event->setCancelled(true);
      $this->playerBroadcast($player, $message);
    }
  }

  private function getGamemode($mode)
  {
    switch((int)$mode){
      case Player::SURVIVAL:
        return "S";
      case Player::CREATIVE:
        return "C";
      case Player::ADVENTURE:
        return "A";
      case Player::SPECTATOR:
        return "SPEC";
    }
    return "UNKNOWN";
  }

  /**
   * Replaces colour, just some cool hax inspired by PocketMine's @ priority tag detection system
   *
   * @param string $string
   * Full message string
   *
   * @param string $trigger
   * Anything before and after * denote the trigger, *(wildcard) which would denote colour code
   * example ![*] means ![colourcode] ![BLACK]
   *
   * @return string
   * Formatted String
   */
  private function replaceColour($string, $trigger = "![*]"):string
  {
    preg_match('/(.*)\*(.*)/', $trigger, $trim);
    preg_match_all('/'.preg_quote($trim[1]).'([A-Z a-z \_]*)'.preg_quote($trim[2]).'/', $string, $matches);
    foreach($matches[1] as $key => $colourCode){
      if(strpos($string, $matches[0][$key]) === false) continue;
      $colourCode = strtoupper($colourCode);
      if(defined(TextFormat::class."::".$colourCode)){
        $code = constant(TextFormat::class."::".$colourCode);
        $string = str_replace($matches[0][$key], $code, $string);
      }
    }
    return $string;
  }

  /*
   * Public APIs
   * for plugin integrations
   */

  /**
   * checks if player is using chatting mode
   *
   * @param $player string|Player|CommandSender Player to check
   *
   * @return bool player is chatting status
   */
  public function isChatting($player):bool
  {
    if($player instanceof CommandSender) if($player instanceof Player) $player = $player->getName();else return false;
    $player = strtolower($player);
    if(isset($this->chatting[$player])) return $this->chatting[$player];else return false;
  }

  /**
   * Get Chatting array directly
   *
   * @param bool $sort Sort to remove values with false
   *
   * @return array
   */
  public function getChatting($sort = true)
  {
    if($sort){
      foreach($this->chatting as $player => $chatting) if($chatting == false) unset ($this->chatting[$player]);
    }
    return $this->chatting;
  }

  /**
   * sets player to chatting mode
   *
   * @param $player string|Player|CommandSender Player to set
   * @param bool $state
   */
  public function setChatting($player, bool $state)
  {
    if($player instanceof CommandSender) if($player instanceof Player) $player = $player->getName();else return;
    $player = strtolower($player);
    if($state == true) $this->chatting[$player] = $state;else unset($this->chatting[$player]);
  }

  /**
   * get readable player chatting state
   *
   * @param $player string|Player|CommandSender
   * @param string $true what to return if true
   * @param string $false what to return if false
   *
   * @return string result in string
   */
  public function getReadableState($player, string $true = "On", string $false = "Off"):string
  {
    return $this->readableTrueFalse($this->isChatting($player), $true, $false);
  }

  /**
   * sets console attachment state
   *
   * @param bool $state weather console is attached
   */
  public function setConsoleState(bool $state){ $this->console = $state; }

  /**
   * gets if console is attached to staff chat
   *
   * @return bool
   */
  public function getConsoleState():bool{ return $this->console; }

  /**
   * get readable console attachment state
   *
   * @param string $true what to return if true
   * @param string $false what to return if false
   *
   * @return string result in string
   */
  public function getReadableConsoleState(string $true = "Attached", string $false = "Detached"):string
  {
    return $this->readableTrueFalse($this->console, $true, $false);
  }

  /**
   * readable true false
   *
   * @param bool $statement
   * @param string $true what to return if true
   * @param string $false what to return if false
   *
   * @return string result in string
   */
  public function readableTrueFalse(bool $statement, $true = 'true', $false = 'false')
  {
    if($statement) return $true;else return $false;
  }

  /**
   * Gets all players that can read staff chat
   *
   * @return Player[]
   */
  public function getReadPlayers()
  {
    $players = [];
    foreach($this->getServer()->getOnlinePlayers() as $player) if($player->hasPermission(self::permRead)) $players[] = $player;
    return $players;
  }

  /**
   * Flush config and chatting
   *
   * @param string $message Message to be sent to people attatched in staffchat
   * @param bool $notify do you want to notify players in staffchat
   */

  public function flush(string $message = '', bool $notify = true)
  {
    $this->getConfig()->reload();
    $this->prefix = $this->getConfig()->get('prefix', ".");
    $this->console = (bool)$this->getConfig()->get('auto-attach', true);
    $this->consolePrefix = $this->getConfig()->get('console-prefix', '[StaffChat] ');
    $this->format = $this->replaceColour($this->getConfig()->get('player-format'));
    $this->pluginFormat = $this->replaceColour($this->getConfig()->get('plugin-format'));
    $this->joinMsg = $this->replaceColour($this->getConfig()->get('join'));
    $this->leaveMsg = $this->replaceColour($this->getConfig()->get('leave'));

    if($notify){
      if(strlen($message) == 0) $message = TextFormat::RED.'Staff Chat> All message will now go into NORMAL chat';
      foreach($this->getChatting() as $player => $chatting){
        $this->getServer()->getPlayerExact($player)->sendMessage($message);
      }
    }
    $this->chatting = [];
  }


}