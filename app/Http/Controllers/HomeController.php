<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {

        $words = DB::table('words')
                    ->orderBy('known', 'asc')
                    ->orderBy('count_words', 'desc') 
                    ->get();

        $total = DB::table('words')
                    ->select(DB::raw('SUM(count_words) as total_words'))
                    ->get();

        View::share('words' , $words);
        View::share('total_words' , $total[0]->total_words);
        return view('home');
    }
}
