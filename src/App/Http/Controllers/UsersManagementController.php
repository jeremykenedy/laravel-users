<?php

namespace jeremykenedy\laravelusers\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use Auth;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Validator;

class UsersManagementController extends Controller
{
    private $_authEnabled;
    private $_rolesEnabled;
    private $_rolesMiddlware;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->_authEnabled = config('laravelusers.authEnabled');
        $this->_rolesEnabled = config('laravelusers.rolesEnabled');
        $this->_rolesMiddlware = config('laravelusers.rolesMiddlware');

        if ($this->_authEnabled) {
            $this->middleware('auth');
        }

        if ($this->_rolesEnabled) {
            $this->middleware($this->_rolesMiddlware);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $pagintaionEnabled = config('laravelusers.enablePagination');

        if ($pagintaionEnabled) {
            $users = config('laravelusers.defaultUserModel')::paginate(config('laravelusers.paginateListSize'));
        } else {
            $users = config('laravelusers.defaultUserModel')::all();
        }

        $data = [
            'users'             => $users,
            'pagintaionEnabled' => $pagintaionEnabled,
        ];

        return view('laravelusers::usersmanagement.show-users', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('laravelusers::usersmanagement.create-user');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name'      => 'required|max:255',
            'email'     => 'required|email|max:255|unique:users',
            'password'  => 'required|confirmed|min:6'
        ]);

        if ($validator->fails()) {
            $this->throwValidationException(
                $request, $validator
            );
        } else {
            $user               = new config('laravelusers.defaultUserModel');
            $user->name         = $request->input('name');
            $user->email        = $request->input('email');
            $user->password     = bcrypt($request->input('password'));
            $user->save();

            return redirect('users')->with('success', 'Successfully created user!');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = config('laravelusers.defaultUserModel')::find($id);

        return view('laravelusers::usersmanagement.show-user')->withUser($user);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = config('laravelusers.defaultUserModel')::findOrFail($id);

        return view('laravelusers::usersmanagement.edit-user')->withUser($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $currentUser = Auth::user();
        $user        = config('laravelusers.defaultUserModel')::find($id);
        $emailCheck  = ($request->input('email') != '') && ($request->input('email') != $currentUser->email);

        if ($emailCheck) {
            $validator = Validator::make($request->all(), [
                'name'      => 'required|max:255',
                'email'     => 'nullable|email|max:255|unique:users',
                'password'  => 'present|confirmed|min:6'
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'name'      => 'required|max:255',
                'password'  => 'nullable|confirmed|min:6'
            ]);
        }
        if ($validator->fails()) {
            $this->throwValidationException(
                $request, $validator
            );
        } else {
            $user->name = $request->input('name');
            if ($emailCheck) {
                $user->email = $request->input('email');
            }
            if ($request->input('password') != null) {
                $user->password = bcrypt($request->input('password'));
            }
            $user->save();

            return back()->with('success', 'Successfully updated user');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $currentUser = Auth::user();
        $user = config('laravelusers.defaultUserModel')::findOrFail($id);

        if ($currentUser != $user) {
            $user->delete();
            return redirect('users')->with('success', 'Successfully deleted the user!');
        }

        return back()->with('error', 'You cannot delete yourself!');
    }
}
