<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Timeframe;
use App\Pair;
use App\Accumulation;
use App\Trade;
use App\Ticker;
use DB;
use Binance;
use Storage;
class PairController extends Controller
{
    public function index(){
        $timeframes = Timeframe::all();
        $pairs = Pair::all();
        //dd($rsi);
        return view('pares', compact("timeframes","pairs"));
    }

    public function savePair(Request $req){        
        try {
            DB::beginTransaction();
            $pair = new Pair;
            $pair->name = $req->pair;
            $pair->initial_capital = $req->initial_capital;
            $pair->current_capital = $req->initial_capital;
            $pair->initial_parts = $req->initial_parts;
            $pair->rsi_min = $req->rsi_min;
            $pair->on = 0;
            $pair->max_periods = $req->max_periods;
            $pair->distance = $req->distance;
            $pair->timeframe_id = $req->timeframe;
            $pair->rentability = $req->rentability;
            $pair->save();
            //dd($pair);
            DB::commit();
            return back()->withInput(['guardado' => true]);            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput(['error' => true]);
        }
    }

    public function prueba(){
        $pairs = Pair::where('on', 1)->get();
        #Obtenemos y guardamos la información de cada par
        foreach ($pairs as $pair) {
            $array_ema = [];
            $rsi_array =[];
            $ema_array = [];
            $api = new Binance\API(env("API_KEY"),env("SECRET"));
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
                $array_tickers = Ticker::where('pair_id',$pair->id)->select('close')->orderBy('id','desc')->limit(199)->get();
                foreach ($array_tickers as $val) {
                     $array_ema[] = $val->close;
                }
                $array_ema = $array_ema->reverse();
                $array_ema[] = $aux["close"];
                $rsi_array = trader()->rsi($array_ema,14);
                //$ema_array = trader()->ema($array_ema,200)??0;
                //$ticker->ema200 = count($array_ema)>= 200? $ema_array[count($ema_array)+199]:0;
                $ticker->rsi = count($array_ema)>= 14? $rsi_array[count($rsi_array)+13]:0;
                $ticker->code = $aux["openTime"];
                if(count($array_ema)>= 200){
                    $ema_array =  trader()->ema($array_ema,200);
                    $ticker->ema200 = $ema_array[count($ema_array)+199];
                }else{
                    $ticker->ema200 = 0;
                }
                if(count($array_ema)>= 34){
                    $data_macd =  trader()->macd($array_ema,12,26,9,13);
                    $ticker->macd = $data_macd[0][count($data_macd[0])+32];
                    $ticker->signal_macd = $data_macd[1][count($data_macd[1])+32];
                    $ticker->histogram_macd = $data_macd[2][count($data_macd[2])+32];
                }else{
                    $ticker->macd =0;
                    $ticker->signal_macd = 0;
                    $ticker->histogram_macd = 0;
                }
                
                $ticker->save();
            }
        }   
        #Revisemos la situación de cada par
        foreach ($pairs as $pair) {
            $last_ticker = Ticker::where('pair_id',$pair->id)->order_by('id', 'desc')->limit(3)->get()->toArray();
            $pre = $last_ticker[2]->histogram_macd;
            $post = $last_ticker[0]->histogram_macd;
            $center = $last_ticker[1]->histogram_macd;
            $act_rsi = $last_ticker[0]->rsi;
            $pre_rsi = $last_ticker[1]->rsi;
            $close = $last_ticker[0]->close;
            $ema200 = $last_ticker[0]->ema200;
            $buy = $post<0 and $center<$post and $pre>$center and $act_rsi<35 and $pre_rsi<25 and $close<$ema200;
            $accumulation = Acumulation::where('pair_id', $pair->id)->where('status',1)->first();
            if($buy){                
                # revisar si hay un accumulate activo para el par,                 
                if($accumulation){
                    # si hay un acc activo, se revisa si se paso el máximo de trades por par, 
                    $periods = count($accumulation->trades);                    
                }else{
                    $accumulation = new Acumulation;
                    $periods = 0;
                    $accumulation->pair_id = $pair->id;
                    $accumulation->status = 1;
                    $accumulation->candles = 0;
                    $accumulation->hold = 0;
                }
                #falta definir algunos valores de accumulation
                $divisor_actual = getDivisor($pair->initial_parts, 1/$pair->max_periods, $periods);
                $monto_entrada = $pair->current_capital/$divisor_actual;
                if($pair->current_capital > $monto_entrada){
                    #si hay saldo disponible, compra!! ntp, el indice garantiza q se cumplan los max_periods del par
                    $cantidad = $monto_entrada/$close;
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
                        $new_trade->order_id = $compra['order_id'];
                        $new_trade->open_date = epochToDatetime($compra['transactTime']);
                        $new_trade->active = 1;
                        $new_trade->status = 1;
                        $new_trade->save();
                        $accumulation->avg_entry_price = newAverage($accumulation->avg_entry_price, $compra["fills"][0]["price"], $compra["cummulativeQuoteQty"], $accumulation->total_amount);
                        $accumulation->total_quantity += $new_trade->quantity;
                        $accumulation->total_amount += $new_trade->amount;
                        $accumulation->sell_target = $accumulation->avg_entry_price * $pair->rentability;
                        $pair->current_capital = $pair->current_capital - $compra["cummulativeQuoteQty"];
                        $pair->save();
                        $accumulation->save();
                    }                     
                }
            }
            $sell = ($post > 0 and $center > $post and $pre < $center) and $close > $ema200 and $close > $accumulation->sell_target;
            if($sell){
                #si es momento de vender, VENDE TODO
                $accumulation = Accumulation::where('pair_id', $pair->id)->where('status',1)->first();
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
                    $pair->capital = $pair->current_capital;
                    $pair->save();
                }                
            }
        }
    }

    public function loadData($id){
        try {
            $pair = Pair::find($id);
            Ticker::where('pair_id',$id)->delete();            
            $array = [];
            $array_vol = [];
            $data_macd = [];
            $x = 0;
            $api = new Binance\API(env("API_KEY"),env("SECRET"));
            $data =  $api->candlesticks($pair->name, $pair->timeframe->name);
            //dd($data[$api->first($data)]);
            foreach ($data as $key => $value) {
                $ticker = new Ticker;
                $ticker->pair_id = $pair->id;
                //$aux = $data[$api->first($data)];
                $ticker->open = $value["open"];
                $ticker->close = $value["close"];
                $ticker->high = $value["high"];
                $ticker->low = $value["low"];
                $ticker->volume = $value["volume"];
                $ticker->code = $value["openTime"];
                $ticker->open_date = epochToDatetime($value["openTime"]);
                $array[] = $value["close"];
                $array_vol[] = $value["volume"];
                $x += 1;
                //$x>=14? dd($array):"";
                $ticker->rsi = $x > 14? trader()->rsi(array_slice($array,-15),14)[14]:0;
                $ticker->ema200 = $x > 200? trader()->ema($array,200)[$x-1]:0;
                $ticker->avg_volume = $x > 14? trader()->ma($array_vol, 14)[$x-1]:0;
                if($x > 34){
                    $data_macd =  trader()->macd($array,12,26,9,13);
                    $ticker->macd = $data_macd[0][$x-1];
                    $ticker->signal_macd = $data_macd[1][$x-1];
                    $ticker->histogram_macd = $data_macd[2][$x-1];
                }else{
                    $ticker->macd =0;
                    $ticker->signal_macd = 0;
                    $ticker->histogram_macd = 0;
                }
                $ticker->save();
            }
            Ticker::where('pair_id',$id)->orderBy('id','desc')->first()->delete();
            
            return Response()->json(["success" => true]);
        } catch (\Throwable $th) {
            dd($th);
            return Response()->json([
                "success" => false,
                "message" => $th
              ]);
        }
    }

    public function onPair($id){
        try{
            $pair = Pair::findOrFail($id);
            $pair->on = $pair->on == '0' ? '1' : '0' ;
            $pair->save();

            return Response()->json([
                "success" => true,
                "data" => null,
            ]);
        }catch (\Exception $e){
            return Response()->json([
                "success" => false,
                "data" => null,
            ]);
        }
    }
    public function save(Request $req){
        if(true){
            Storage::append("public/archivo.txt", $req);
        }
        return true;
    }
}
