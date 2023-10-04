<?php

namespace App\Http\Controllers\Topup;

use App\Models\Player;
use App\Models\Country;
use App\Models\GameList;
use App\Helpers\AllFunction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class GameController extends Controller
{

    public function index(Request $request)
    {
        try {

            $image_url = env('Image_URL');


            $limit = $request->query('limit');

            if ($limit == null) {
                $limit = 5;
            };

            $data =  GameList::select('id', 'game_id', 'game_title', 'color', 'category', 'slug_game', 'cover', 'is_active')->limit($limit)->get();

            if (!$data) {

                return AllFunction::response(404, 'NOT FOUND', 'Data Game List Not Found');
            };


            $result = [];

            foreach ($data as $game) {

                $gm = array(
                    'id' => $game->id,
                    'game_id' => $game->game_id,
                    'game_title' => $game->game_title,
                    'slug_game' => $game->slug_game,
                    'category' => $game->category,
                    'color' => $game->color,
                    'cover' => $image_url . '/cover/' . $game->cover,
                    'status' => ($game->is_active == '0') ? 'maintenance' : 'active'
                );


                array_push($result, $gm);
            };

            return AllFunction::response(200, 'OK', 'Success Get Game List', $result);
        } catch (\Throwable $th) {
            return AllFunction::response(300, 'BAD REQUEST', 'internal server error');
        }
    }

    public function gameDetail(Request $request)
    {
        try {

            $image_url = env('Image_URL');

            $slug = $request->query('game');
            $game = GameList::select('id', 'game_id', 'slug_game', 'game_title', 'description', 'cover', 'banner', 'tooltips')->where('slug_game', $slug)->where('is_active', '1')->first();

            $country = Country::get();

            if (!$game) {
                return AllFunction::response(404, 'NOT FOUND', 'Data Game List Not Found');
            };


            $gm = array(
                'id' => $game['id'],
                'game_id' => $game['game_id'],
                'slug_game' => $game['slug_game'],
                'game_title' => $game['game_title'],
                'description' => $game['description'],
                'banner' => $image_url . '/cover/' . $game['banner'],
                'tooltips' => $image_url . '/cover/' . $game['tooltips'],
                'cover' => $image_url . '/cover/' . $game['cover'],
            );

            $result = array(
                'game_detail' => $gm,
                'country_list' => $country
            );

            return AllFunction::response(200, 'OK', 'Success Get Game Detail', $result);
        } catch (\Throwable $th) {
            return AllFunction::response(300, 'BAD REQUEST', 'internal server error');
        }
    }

    public function getPlayer(Request $request)
    {
        try {

            $game_id = $request->query('game_id');
            $user_id = $request->query('user_id');

            $player = Player::select('user_id', 'player_id', 'game_id')->where('game_id', $game_id)->where('user_id', $user_id)->get();

            return AllFunction::response(200, 'OK', 'Success Get Player', $player);
        } catch (\Throwable $th) {
            return AllFunction::response(300, 'BAD REQUEST', 'internal server error');
        }
    }
}
