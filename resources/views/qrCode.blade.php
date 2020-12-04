@extends('layouts.admin')


@section('content')
    <div class="row">
        <div class="col-md-12 d-flex align-items-stretch grid-margin">
            <div class="row flex-grow">
                <div class="col-12 stretch-card">
                    <div class="card">
                        <div class="card-body">

                            @if ($message = Session::get('error'))
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <p>{{ $message }}</p>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif
                                @if(Session::has('success'))
                                    <p class="alert alert-success">{{ session()->get('success') }}</p>
                                @endif
                            <h4 class="card-title">Create QrCode</h4>
                            <form class="forms-sample"  method="post" action="/downloadQrcode">
                                {{ csrf_field() }}
                                <div class="form-group row">
                                    <label for="exampleInputEmail2" class="col-sm-3 col-form-label">Enter Mac Address</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control"  name='mac' id="exampleInputEmail2" placeholder="Enter mac address with colon"  required>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-success mr-2">Download</button>

                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>




@endsection
