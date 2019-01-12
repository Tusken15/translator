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
use Illuminate\Support\Facades\Auth;
use App\User;

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
                    ->leftJoin('words_known', 'words.ID', '=', 'words_known.word_id')
                    ->where('words_known.user_id', Auth::id())
                    ->where('words_known.known', 'no')
                    ->orderBy('words_known.count_words', 'desc') 
                    ->whereNull('words_known.deleted_at')
                    ->select('words.ID', 'words.word', 'words.translation', 'words_known.count_words', 'words_known.known')
                    ->limit(1000)
                    ->get();

        $known = DB::table('words')
                    ->leftJoin('words_known', 'words.ID', '=', 'words_known.word_id')
                    ->where('words_known.user_id', Auth::id())
                    ->where('words_known.known','yes')
                    ->whereNull('words_known.deleted_at')
                    ->count();

        $total = DB::table('words_known')
                    ->select(DB::raw('SUM(count_words) as total_words, COUNT(id) AS words_count'))
                    ->where('words_known.user_id', Auth::id())
                    ->whereNull('deleted_at')
                    ->get();

        $countWords = $total[0]->words_count;
        $totalWords = $total[0]->total_words;
        $percentage = ($countWords != 0) ? round(($known/$countWords)*100,2) : '0,00';

        View::share('words' , $words);
        View::share('known' , $known.' / '.$countWords.' ('.$percentage.'%)');
        View::share('total_words' , $totalWords);
        return view('home');
    }

    public function translateWords() {

        ini_set('max_execution_time', 6000);

        $words = DB::table('words')
                    ->where('translation','')
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
        $updated    = [];
        $imported   = 0;
       
        foreach($captions as $caption){

            // sanitize words
            $orig    = strip_tags($caption->text);
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
            $caption = str_replace(':',' ', $caption);
            $caption = trim(preg_replace('/\s+/', ' ', $caption));

            // split to words
            $words  = explode(' ', $caption);


            // through each word
            foreach($words AS $word) {

                $search = ucfirst(trim(trim($word),'\'')); 
                
                // dont save numbers or empty strings or small words
                if(empty($search) || is_numeric($word) || strlen($word) < 2) {
                    continue;
                }

                // check if word exists in database
                $rec = Word::where('word', $search)
                            ->first();

                if(empty($rec)) {

                    // word doesnt exist in database
                    $insData    = [
                        'word'          => $search,
                        'translation'   => '',
                    ];
                    $id = Word::insertGetId($insData);
                    $imported++;

                    // insert words detail
                    $this->insertWordDetail($id);
                } else {
                    // word exist in database
                    // check if user imported the word already
                    $wordDetail = DB::table('words_known')
                                    ->where('word_id', $rec['ID'])
                                    ->where('user_id', Auth::id())
                                    ->first();

                    if(!empty($wordDetail)) {
                        // word exists in users database -> update his count
                        $uptData = [
                            'count_words'   => $wordDetail->count_words+1
                        ];
                        DB::table('words_known')
                            ->where('word_id', $rec['ID'])
                            ->where('user_id', Auth::id())
                            ->update($uptData);
                        
                        if(!isset($updated[$rec['ID']])) {
                            $updated[$rec['ID']] = true;
                        }  
                        
                    } else {
                        // words doesnt exist in users database -> create new record
                        $this->insertWordDetail($rec['ID']);
                        $imported++;
                    }

                }
            }
        }

        $errors = new MessageBag();
        $errors->add('custom_error',    'File: '.$request->file('file')->getClientOriginalName());
        $errors->add('custom_error2',   'Imported: '.$imported.' Updated: '.count($updated));
        return redirect('/home')->withErrors($errors);
    }

    private function insertWordDetail($wordID) {
        $insData    = [
            'user_id'       => Auth::id(),
            'word_id'       => $wordID,
            'known'         => 'no',
            'count_words'   => 1,
        ];
        DB::table('words_known')
            ->insert($insData);
    }

}
