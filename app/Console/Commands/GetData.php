<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Pair;
use App\Ticker;
use App\Accumulation;
use App\Trade;
use App\Timeframe;
use DB;
use Binance;
class GetData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'obtiene y guarda el OHLCV del par definido';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $api = new Binance\API(env("API_KEY"),env("SECRET"));
            $pairs = Pair::where('on', 1)->get();
            foreach ($pairs as $pair) {
                $array= [];
                $array_vol = [];
                $data_macd = [];
                $x=0;            
                $data =  $api->candlesticks($pair->name, $pair->timeframe->name,2);
                //dd($data[$api->first($data)]);
                if (!($ticker = Ticker::where('pair_id',$pair->id)->where('code',$api->first($data))->first())){
                    $ticker = new Ticker;
                    $ticker->pair_id = $pair->id;
                    $aux = $data[$api->first($data)];
                    $ticker->open = $aux["open"];
                    $ticker->close = $aux["close"];
                    $ticker->high = $aux["high"];
                    $ticker->low = $aux["low"];
                    $ticker->volume = $aux["volume"];
                    $ticker->code = $aux["openTime"];
                    $ticker->open_date = epochToDatetime($aux["openTime"]);
                    $array_tickers = Ticker::where('pair_id',$pair->id)->select('close','volume')->get();
                    foreach ($array_tickers as $val) {
                        $array[] = $val->close;
                        $array_vol[] = $val->volume;
                        $x += 1;
                    }
                    $array[] = $aux["close"];
                    $array_vol[] = $aux["volume"];
                    $ticker->rsi = $x > 14? trader()->rsi($array,14)[$x]:0;
                    $ticker->ema200 = $x > 200? trader()->ema($array,200)[$x]:0;
                    $ticker->avg_volume = $x > 14? trader()->ma($array_vol, 14)[$x]:0;
                    //$rsi_array = trader()->rsi($array_ema,14);
                    //$ema_array = trader()->ema($array_ema,200)??0;
                    //$ticker->ema200 = count($array_ema)>= 200? $ema_array[count($ema_array)+199]:0;
                    //$ticker->rsi = count($array_ema)>= 14? $rsi_array[count($rsi_array)+13]:0;
                    
                    if($x > 34){
                        $data_macd =  trader()->macd($array,12,26,9,13);
                        $ticker->macd = $data_macd[0][$x];
                        $ticker->signal_macd = $data_macd[1][$x];
                        $ticker->histogram_macd = $data_macd[2][$x];
                    }else{
                        $ticker->macd =0;
                        $ticker->signal_macd = 0;
                        $ticker->histogram_macd = 0;
                    }                
                    $ticker->save();
                    $pair->distance += 1;
                    $pair->save();                    
                }            
            }   
            $pairs = Pair::where('on', 1)->get();     
            foreach ($pairs as $pair) {
                $last_ticker = Ticker::where('pair_id',$pair->id)->orderBy('id', 'desc')->limit(3)->get()->toArray();
                
                $pre = $last_ticker[2]["histogram_macd"];
                $post = $last_ticker[0]["histogram_macd"];
                $center = $last_ticker[1]["histogram_macd"];
                $act_rsi = $last_ticker[0]["rsi"];
                $pre_rsi = $last_ticker[1]["rsi"];
                $close = $last_ticker[0]["close"];
                $ema200 = $last_ticker[0]["ema200"];
                $accumulation = Accumulation::where('pair_id', $pair->id)->where('status',1)->first();
                if($post<0 and $center<$post and $pre>$center and $act_rsi<35 and $pre_rsi<25 and $close<$ema200){                
                    # revisar si hay un accumulate activo para el par,                 
                    if($pair->distance>=8){
                        if($accumulation){
                            # si hay un acc activo, se revisa si se paso el máximo de trades por par, 
                            $periods = count($accumulation->trades);                    
                        }else{
                            $accumulation = new Accumulation;
                            $periods = 0;
                            $accumulation->pair_id = $pair->id;
                            $accumulation->status = 1;
                            $accumulation->candles = 0;
                            $accumulation->hold = 0;
                            $accumulation->save();
                        }
                        #falta definir algunos valores de accumulation
                        $divisor_actual = getDivisor($pair->initial_parts, 1/$pair->max_periods, $periods);
                        $monto_entrada = $pair->current_capital/$divisor_actual;
                        if($pair->current_capital > $monto_entrada){
                            #si hay saldo disponible, compra!! ntp, el indice garantiza q se cumplan los max_periods del par
                            $cantidad = round($monto_entrada/$close,4);
                            //dd($cantidad);
                            $compra = $api->marketBuy($pair->name, $cantidad);
                            if($compra['status'] == "FILLED"){
                                #luego de comprar hay q actualizar datos de accumulate, del current capital etc
                                //$array[] = $compra["fills"][0]["price"];
                                //$array[] = $compra["fills"][0]["qty"];
                                $new_trade = new Trade;
                                $new_trade->accumulation_id = $accumulation->id;
                                $new_trade->entry_price = $compra["fills"][0]["price"];
                                $new_trade->quantity = $compra["origQty"];
                                $new_trade->amount = $compra["cummulativeQuoteQty"];
                                $new_trade->period = $periods+1;
                                $new_trade->order_id = $compra['orderId'];
                                $new_trade->open_date = epochToDatetime($compra['transactTime']);
                                $new_trade->active = 1;
                                $new_trade->status = 1;
                                $new_trade->save();
                                $accumulation->avg_entry_price = newAverage($accumulation->avg_entry_price, $compra["fills"][0]["price"], $compra["cummulativeQuoteQty"], $accumulation->total_amount);
                                $accumulation->total_quantity += $new_trade->quantity;
                                $accumulation->total_amount += $new_trade->amount;
                                $accumulation->sell_target = $accumulation->avg_entry_price * $pair->rentability;
                                $pair->current_capital = $pair->current_capital - $compra["cummulativeQuoteQty"];
                                $pair->distance = 0;
                                $pair->save();
                                $accumulation->save();
                            }                     
                        }
                    }
                    
                }            
                if(($post > 0 and $center > $post and $pre < $center) and $close > $ema200 and $close > $accumulation->sell_target){
                    #si es momento de vender, VENDE TODO
                    $accumulation = Accumulation::where('pair_id', $pair->id)->where('status',1)->first();
                    //dd($accumulation);
                    $trades = Trade::where('accumulation_id',$accumulation->id)->where('active',1)->where('status',1)->get();
                    $venta = $api->marketSell($pair->name, $accumulation->total_quantity);
                    if($venta['status'] =="FILLED"){
                    #luego hay que actualizar el estado de accumulate, cerrar los trades y actualizar los capitales de pair.
                        foreach ($trades as $trade) {
                            $trade->active = 0;
                            $trade->status = 0;
                            $trade->closed_date = epochToDatetime($venta['transactTime']);
                            $trade->save();
                        }
                        $accumulation->sell_date = epochToDatetime($venta['transactTime']);
                        $accumulation->status = 0;
                        $accumulation->save();
                        $pair->current_capital += ($venta["fills"][0]["price"]* $venta["executedQty"]);
                        $pair->initial_capital = $pair->current_capital;
                        $pair->save();
                    }                
                }
            }
        } catch (\Throwable $th) {
            Storage::put('attempt1.txt', $th);
           //File::append(path('public') . 'logs/' . date('Y-m-d') . '.log', $th);
        }
        
    }
}
