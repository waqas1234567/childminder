@extends('layouts.admin')

@section('content')
    <div class="card ">
        <div class="card-body">
            <h4 class="card-title">Users</h4>
            <table class="table" id="table_id">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($users as $u)
                    @if($u->isAdmin!=1)
                <tr >
                    <td>{{$u->name}}</td>
                    <td>{{$u->email}}</td>
                    <td>{{$u->contact}}</td>
                    @if($u->active==1)
                    <td>
                        <label class="badge badge-success">Active</label>
                    </td>
                    @else
                        <td>
                            <label class="badge badge-danger">Inactive</label>
                        </td>
                        @endif
                    <td>
                           <a href="/contacts/{{$u->id}}" class="btn btn-primary">Contacts</a>
                           <a href="/profiles/{{$u->id}}" class="btn btn-info">Profiles</a>
                           <button id="del{{$u->id}}" onclick="deleteUser({{$u->id}})"   class="btn btn-danger">Delete</button>

                    </td>
                </tr>
                    @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>

@endsection

