<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AjaxController extends Controller
{
    public function post(Request $request){

        $word = DB::table('words_known')
                    ->where('word_id', $request->id)
                    ->where('user_id', Auth::id())
                    ->first();

        $status = $word->known == 'yes' ? 'no' : 'yes';

        DB::table('words_known')
            ->where('word_id', $request->id)
            ->where('user_id', Auth::id())
            ->limit(1)
            ->update(['known' => $status]); 

        $response = array(
            'status'    => $status,
        );
        return response()->json($response); 
     }

    public function delete(Request $request) {
        $word = DB::table('words_known')
            ->where('word_id', $request->id)
            ->where('user_id', Auth::id())
            ->update(['deleted_at' => Carbon::now()]);

        $response = array(
            'status' => 'success',
        );
        return response()->json($response); 
    }

    public function changeTranslation(Request $request) {

        DB::table('words')
            ->where('ID', $request->id)
            ->limit(1)
            ->update(['translation' => $request->value]); 

        $response = array(
            'status' => 'success',
        );

        return response()->json($response); 
    }

}
