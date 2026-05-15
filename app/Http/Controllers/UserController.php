<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;

class UserController extends Controller
{
    /** Список пользователей.
     * @return Response
     */
    public function index()
    {
        return Inertia::render('Users/Index', [
            'users' => User::select('id', 'name', 'email', 'role', 'created_at')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /** Создание пользователя.
     * @return RedirectResponse
     */
    public function store(StoreUserRequest $request)
    {
        User::create([
            ...$request->safe()->except('password'),
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('users.index');
    }

    /** Обновление пользователя.
     * @return RedirectResponse
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->safe()->except('password');

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('users.index');
    }

    /** Удаление пользователя.
     * @return RedirectResponse
     */
    public function destroy(User $user)
    {
        abort_if($user->id === auth()->id(), 403);

        $user->delete();

        return redirect()->route('users.index');
    }
}
