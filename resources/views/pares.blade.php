@extends('layouts.app')

@section('content')
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>
              Pares
              <small>usando la estrategia</small>
            </h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Modals & Alerts</li>
            </ol>
          </div>
        </div>
      </div>
    </section>
    @if(old('guardado'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
      <h5><i class="icon fas fa-check"></i> Bien!</h5>
      El registro fue añadido de manera correcta.
    </div>
    @endif
    @if(old('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
      <h5><i class="icon fas fa-ban"></i> Alerta!</h5>
      No se pudo guardar el registro.
    </div>
    @endif
    <section class="content">
        <div class="container-fluid">
          <div class="row">
            <div class="col-md-12">
              <div class="card card-primary">
                <div class="card-header">
                  <h3 class="card-title">
                    <i class="fas fa-tools"></i>
                    Opciones
                  </h3>
                </div>
                <div class="card-body text-right">
                  
                  <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal-lg">
                    Nuevo par
                  </button>
                </div>
              </div>

              <div class="card">
                  <div class="card-header">
                    <h3 class="card-title">Lista de pares</h3>
                  </div>
                  <!-- /.card-header -->
                  <div class="card-body p-0">
                    <table class="table table-striped">
                      <thead>
                        <tr>
                          <th style="width: 10px">#</th>
                          <th>Par</th>
                          <th>Timeframe</th>
                          <th>Cap. Inicial</th>
                          <th>Cap. Actual</th>
                          <th>Partes</th>
                          <th>Profit Venta</th>
                          <th>RSI</th>
                          <th>Distance</th>
                          <th style="width: 40px">% Var. Cap</th>
                          <th style="width: 80px">Encendido</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach ($pairs as $pair)
                          <tr>
                            <td>{{$loop->index+1}}.</td>
                            <td>{{$pair->name}}</td>
                            <td>{{$pair->timeframe->name}}</td>
                            <td>{{$pair->initial_capital}}</td>
                            <td>{{$pair->current_capital}}</td>
                            <td>{{$pair->initial_parts}}</td>
                            <td>{{$pair->rentability}}</td>
                            <td>{{$pair->rsi_min}}</td>
                            <td>{{$pair->distance}}</td>
                            <td>{{(($pair->current_capital-$pair->initial_capital)/$pair->initial_capital)*100}}%</td>
                            <td><div class="custom-control custom-switch">
                              <input type="checkbox" class="custom-control-input" name="on"
                                id="on{{ $loop->index }}" {{$pair->on ? 'checked' : ''}}
                              data-id="{{$pair->id}}" value="1">
                              <label class="custom-control-label"
                                for="on{{ $loop->index }}"></label>
                            </div>
                            <button type="button" data-id="{{$pair->id}}" class="btn btn-primary cargardatos">
                              Cargar Datos
                            </button>
                          </td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                  <!-- /.card-body -->
              </div>
              
            </div>
          </div>
        </div>
    </section>
    <div class="modal fade" id="modal-lg">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form action="{{route('nuevo.par')}}" id="form_incidente" class="needs-validation" novalidate method="post">
            @csrf
            <div class="modal-header">
              <h4 class="modal-title">Nuevo par</h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div class="card">
                <!-- /.card-header -->
                <div class="card-body">
                    <div class="row">
                      <div class="col-sm-6">
                        <!-- text input -->
                        <div class="form-group">
                          <label>Par</label>
                          <input type="text" class="form-control" name="pair" placeholder="BTCUSDT">
                        </div>
                      </div>
                      <div class="col-sm-6">
                        <!-- text input -->
                        <div class="form-group">
                          <label>Capital inicial</label>
                          <input type="text" class="form-control" name="initial_capital" placeholder="1000">
                        </div>
                      </div>
                      <div class="col-sm-6">
                        <!-- text input -->
                        <div class="form-group">
                          <label>Partición Inicial</label>
                          <input type="text" class="form-control" name="initial_parts" placeholder="50">
                        </div>
                      </div>
                      <div class="col-sm-6">
                        <!-- text input -->
                        <div class="form-group">
                          <label>Nivel RSI mínimo</label>
                          <input type="text" class="form-control" name="rsi_min" placeholder="25">
                        </div>
                      </div>
                      <div class="col-sm-6">
                        <!-- text input -->
                        <div class="form-group">
                          <label>Máx numero de compras</label>
                          <input type="text" class="form-control" name="max_periods" placeholder="20">
                        </div>
                      </div>
                      <div class="col-sm-6">
                        <!-- text input -->
                        <div class="form-group">
                          <label>Rentabilidad</label>
                          <input type="text" class="form-control" name="rentability" placeholder="1.1">
                        </div>
                      </div>
                      <div class="col-sm-6">
                        <!-- text input -->
                        <div class="form-group">
                          <label>Distancia</label>
                          <input type="text" class="form-control" name="distance" placeholder="1.1">
                        </div>
                      </div>
                      <div class="col-sm-6">
                        <div class="form-group">
                          <label>Timeframe</label>
                          <select class="form-control" name="timeframe">
                            @foreach ($timeframes as $timeframe)
                              <option value="{{$timeframe->id}}">{{$timeframe->name}}</option>    
                            @endforeach
                          </select>
                        </div>
                      </div>
                    </div>
                  
                </div>
                <!-- /.card-body -->
              </div>
            </div>
            <div class="modal-footer justify-content-between">
              <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
          </form>
        </div>
        <!-- /.modal-content -->
      </div>
      <!-- /.modal-dialog -->
    </div>

@endsection
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.24.0/axios.min.js"></script>
<script>
async function loadTickers(id) {
    let res = await axios.get(`/pares/cargar/tickers/${id}`);
    return res.data;
  }
$(document).ready(function () {
 
 window.setTimeout(function() {
     $(".alert").fadeTo(1000, 0).slideUp(1000, function(){
         $(this).remove(); 
     });
 }, 5000);
  $(".cargardatos").on('click', async function (event){
    const id = $(this).attr('data-id');
    data = await loadTickers(id);
    console.log(data);
  })
  $('.custom-switch .custom-control-input').click(function(e){
              e.preventDefault();
              let checked = !this.checked;
              let id = this.dataset.id;              
              axios.patch(`/par/${id}/estado`)
                .then(res => {
                  this.checked = !checked;
                }).catch(err => {
                  console.log(err);
                })
        });
 });
 
</script>

