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
                    <th>Email</th>
                    <th>Contact</th>

                </tr>
                </thead>
                <tbody>
                @foreach($contacts as $u)
                <tr>
                    <td><img src="{{$u->image}}"></td>
                    <td>{{$u->name}}</td>
                    <td>{{$u->email}}</td>
                    <td>{{$u->contact}}</td>
                </tr>
                    @endforeach

                </tbody>
            </table>
        </div>
    </div>

@endsection
