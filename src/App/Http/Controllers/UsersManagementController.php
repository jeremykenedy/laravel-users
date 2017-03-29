<?php

namespace jeremykenedy\laravelusers\App\Http\Controllers;

use Illuminate\Foundation\Auth\RegistersUsers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests;
use Validator;
use App\User;
use Auth;

class UsersManagementController extends Controller
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
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::all();

        return View('laravelusers::usersmanagement.show-users', compact('users'));

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
            $user               = new User;
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
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        $user = User::find($id);

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
        $user = User::findOrFail($id);
        return view('laravelusers::usersmanagement.edit-user')->withUser($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $currentUser = Auth::user();
        $user        = User::find($id);
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
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        $currentUser = Auth::user();
        $user = User::findOrFail($id);

        if ($currentUser != $user) {
            $user->delete();
            return redirect('users')->with('success', 'Successfully deleted the user!');
        }
        return back()->with('error', 'You cannot delete yourself!');

    }
}
