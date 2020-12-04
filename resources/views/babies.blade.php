@extends('layouts.admin')

@section('content')
    <div class="card ">
        <div class="card-body">
            <h4 class="card-title">Contacts</h4>
            <table class="table" id="table_id">
                <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Age</th>
                    <th>Device Name</th>
                    <th>Mac Address</th>

                </tr>
                </thead>
                <tbody>
                @foreach($babies as $u)
                <tr>
                    <td><img src="{{$u->image}}"></td>
                    <td>{{$u->name}}</td>
                    <td>{{$u->age}}</td>
                    <td>{{$u->device}}</td>
                    <td>{{$u->macAddress}}</td>
                </tr>
                    @endforeach

                </tbody>
            </table>
        </div>
    </div>

@endsection
