<?php

namespace App\Http\Controllers\DataTable;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\DataTable\DataTableController;

class UserController extends DataTableController
{
    protected $allowCreation = false;

    public function __construct()
    {
        Parent::__construct();
        $this->middleware('auth');
    }

    public function builder()
    {
    	return User::query();
    }

    public function update($id, Request $request)
    {
        $user = User::find($id);

    	$this->validate($request, [
    		'name' => 'required',
    		'email' => 'required|email|unique:users,email,' . $user->id,
    	]);

    	$this->builder->find($id)->update($request->only($this->getUpdatableColumns()));
    }

/*    public function getDisplayableColumns()
    {
    	return [
    		'id', 'name', 'email', 'created_at'
    	];
    }*/

    public function getUpdatableColumns()
    {
    	return [
    		'name', 'email', 'updated_at'
    	];
    }
}
