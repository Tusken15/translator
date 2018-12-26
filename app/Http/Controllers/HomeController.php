<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Benlipp\SrtParser\Parser;
use App\Word;
use Illuminate\Support\MessageBag;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Illuminate\Support\Carbon;

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
                    ->orderBy('count_words', 'desc') 
                    ->where('known','no')
                    ->whereNull('deleted_at')
                    ->get();

        $known = DB::table('words')
                    ->where('known','yes')
                    ->whereNull('deleted_at')
                    ->count();

        $total = DB::table('words')
                    ->select(DB::raw('SUM(count_words) as total_words'))
                    ->whereNull('deleted_at')
                    ->get();

        View::share('words' , $words);
        View::share('notTranslated' , $words);
        View::share('known' , $known.' / '.($known+count($words)).' ('.round((($known/(count($words)+$known)))*100,2).'%)');
        View::share('total_words' , $total[0]->total_words);
        return view('home');
    }

    public function translateWords() {

        ini_set('max_execution_time', 6000);

        $words = DB::table('words')
                    ->where('translation','')
                    ->whereNull('deleted_at')
                    ->orderBy('count_words', 'desc') 
                    ->get();

        $tr = new GoogleTranslate('sk', 'en');

        $proxylist = [
            '92.38.45.45:37988',
            '45.125.61.209:35759',
            '195.122.160.115:3128',
            '95.79.111.193:51442',
            '1.10.186.52:37721',
            '118.174.234.141:52055',
            '35.185.201.225:8080',
            '1.10.189.12:54425',
            '1.20.103.249:53898',
            '89.109.12.82:47972',
            '171.99.189.102:51036',
            '188.0.131.208:46392',
            '163.172.220.221:8888',
            '138.68.240.218:3128',
            '138.68.161.157:3128',
            '173.212.202.65:80',
            '81.163.35.120:23661',
            '1.20.103.135:57831',
            '118.175.176.132:46292',
            '125.25.165.97:35912',
            '110.44.116.189:52928',
            '103.81.13.65:57803',
            '103.192.168.41:23500',
            '92.38.44.182:56820',
            '160.238.136.113:23500',
            '46.209.58.189:32231',
            '103.244.207.77:55867',
            '131.196.141.136:33729',
            '213.91.235.134:8888',
            '109.70.201.97:53517',
            '52.170.94.27:80',
            '176.120.211.176:58035',
            '45.32.117.121:8080',
            '37.53.93.10:49436',
            '58.9.118.95:39654',
            '118.174.232.218:45788',
            '134.196.91.18:41177',
            '18.191.175.174:8080',
            '181.49.24.126:8081',
            '79.134.211.30:41843',
            '182.73.25.217:80',
            '94.21.243.131:38143',
            '109.167.224.198:51919',
            '37.192.10.9:48962',
        ];

        shuffle($proxylist);

        $useProxies = false;

        foreach($words AS $word) {

            if(!$useProxies) {
                $trans = $tr->translate($word->word);

                if($trans == ' ') {
                    $useProxies = true;
                    continue;
                }
            } else {
                $trans = $tr->setOptions(['timeout' => 100, 'proxy' => $proxylist[0]])->translate($word->word);

                $proxies = DB::table('proxies')
                                    ->where('proxy', $proxylist[0])
                                    ->first();

                if(empty($proxies)) {
                    DB::table('proxies')
                        ->insert(['proxy' => $proxylist[0], 'imports' => 0, 'created_at' => Carbon::now()]);
                }

                if($trans == ' ') {
                    unset($proxylist[0]);
                    if(empty($proxylist)) {
                        return false;
                    }
                    $proxylist = array_values($proxylist);
                    continue;
                } else {
                    // set result of proxy
                    $imports = (isset($proxies->imports)) ? $proxies->imports : 0;
                    DB::table('proxies')
                        ->where('proxy',  $proxylist[0])
                        ->update(['imports' => $imports+1, 'updated_at' => Carbon::now()]);
                }
            }

            Word::where('ID', $word->ID)
                ->update(['translation' => $trans]);
        }
    }

    public function import(Request $request){

        // parser
        $parser     = new Parser(); 
        $parser->loadFile($request->file('file'));
        $captions   = $parser->parse();

        // stats
        $updated    = 0;
        $imported   = 0;
       
        foreach($captions as $caption){

            // sanitize words
            $orig    = $caption->text;
            $caption = str_replace('!',' ', $orig);
            $caption = str_replace(',',' ', $caption);
            $caption = str_replace('.',' ', $caption);
            $caption = str_replace('-',' ', $caption);
            $caption = str_replace('?',' ', $caption);
            $caption = str_replace('♪',' ', $caption);
            $caption = str_replace('\n',' ', $caption);
            $caption = str_replace('"',' ', $caption);
            $caption = str_replace('(',' ', $caption);
            $caption = str_replace(')',' ', $caption);
            $caption = str_replace(']',' ', $caption);
            $caption = str_replace('[',' ', $caption);
            $caption = str_replace('~',' ', $caption);
            $caption = str_replace('<i>',' ', $caption);
            $caption = str_replace('</i>',' ', $caption);
            $caption = str_replace('#',' ', $caption);
            $caption = str_replace('&',' ', $caption);
            $caption = trim(preg_replace('/\s+/', ' ', $caption));

            // split to words
            $words  = explode(' ', $caption);

            // through each word
            foreach($words AS $word) {

                $search = trim(trim($word),'\'');

                // dont save numbers or empty strings
                if(empty($search) || is_numeric($word)) {
                    continue;
                }

                // check if 
                $rec = Word::where('word', $search)
                            ->first();

                if(empty($rec)) {

                    $insData    = [
                        'word'          => $search,
                        'translation'   => '',
                        'count_words'   => 1,
                        'known'         => 'no'
                    ];
                    Word::insert($insData);
                    $imported++;

                } else {

                    $uptData = [
                        'count_words'   => $rec['count_words']+1
                    ];
                    Word::where('ID', $rec['ID'])
                        ->update($uptData);
                    $updated++;
                }
            }

        }

        $errors = new MessageBag();
        $errors->add('custom_error',    'File: '.$request->file('file')->getClientOriginalName());
        $errors->add('custom_error2',   'Imported: '.$imported.' Updated: '.$updated);
        return redirect('/home')->withErrors($errors);
    }

}
