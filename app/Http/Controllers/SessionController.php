<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use App\Session;
use Illuminate\Http\Request;
use phpseclib\Net\SSH2;
use phpseclib\Crypt\RSA;
use phpseclib\File\ANSI;

class SessionController extends Controller
{
    public function __construct()
    {
    }

    public function Create(Request $request)
    {
        $password = $request->input('password');
        $map_name = $request->input('map_name');
        $max_connections = $request->input('max_connections');
        $max_connections = (!isset($max_connections) || empty($max_connections)) ? 4 : $max_connections;
        $game_port = 7000;
        
        while(true)
        {
            $checkGame = Session::where('port', $game_port)->first();
            if ($checkGame)
                $game_port++;
            else
                break;
            
            if ($game_port >= 8000)
                return response([
                    'success' => false,
                    'content' => [
                        'error' => 'Server is overloaded'
                    ]
                ]);
        }

        $game_id = strtotime("now");
        $game_name = "game_{$game_id}";
        $invite_key = str_shuffle(Str::random(6) . $game_id);
        $ip = $_SERVER['SERVER_ADDR'];
        $password = (isset($password) && !empty($password)) ? $password : '';

        $game = Session::insert([
            'game_id' => $game_id,
            'game_name' => $game_name,
            'invite_key' => $invite_key,
            'ip' => $ip,
            'port' => $game_port
        ]);

        $ssh = new SSH2('127.0.0.1');

        if ($ssh->login(env('SSH_USERNAME'), env('SSH_PASSWORD'))) {
            $ssh->exec("tmux new-session -d -x 80 -y 23 -s {$game_name} ./server.sh {$ip} {$game_port} {$map_name} {$max_connections} {$password}\n");
        }

        if (!$game)
            return response([
                'success' => false,
                'content' => [
                    'error' => 'Failed to write data to database'
                ]
            ]);

        return response([
            'success' => true,
            'content' => [
                'invite_key' => $invite_key,
                'invite_link' => env('APP_URL') . '/session/getinfo/' .  $invite_key
            ]
        ]);
    }

    public function GetInfo(string $invite_key)
    {
        $game = Session::where('invite_key', $invite_key)->first();

        if (!$game)
            return response([
                'success' => false,
                'content' => [
                    'error' => 'No such session exists'
                ]
            ]);

        return response([
            'success' => true,
            'content' => $game
        ]);
    }
}
