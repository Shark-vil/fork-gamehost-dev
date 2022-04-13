<?php

namespace App\Console\Commands;

use App\Session;
use Illuminate\Console\Command;
use phpseclib\Net\SSH2;
use phpseclib\Crypt\RSA;
use phpseclib\File\ANSI;

class GameCheckCmd extends Command
{
    protected $signature = 'game:check';
    protected $description = 'Checks existing sessions and deletes them if necessary';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $ssh = new SSH2('127.0.0.1');

        if ($ssh->login(env('SSH_USERNAME'), env('SSH_PASSWORD'))) {
            $games = Session::get();

            foreach($games as &$game)
            {
                $response = $ssh->exec("./check-session.sh {$game->game_name}\n");

                if (strpos($response, "session-not-exists") !== false)
                {
                    Session::where('port', $game->port)->delete();
                    echo "The session '{$game->game_name}' has been deleted from the database";
                }
            }
        }
    }
}